"""
converter_service/main.py
Microservicio de conversión de documentos a .docx para Alfa
("Procesamiento de Texto" → Convertir a DocX).

Toma un documento .doc / .rtf / .odt / .docx, detecta su formato REAL leyendo
el contenido interno del archivo (no la extensión), y devuelve un .docx fiel y
bien formado, listo para el pipeline del Anonimizador.

Motor de conversión: LibreOffice headless (`soffice --convert-to docx`).

Endpoints:
    POST /convert  — recibe un documento, devuelve el .docx (base64) + diagnóstico
    GET  /health   — verifica estado del servicio y la versión de LibreOffice
"""

import base64
import io
import logging
import os
import shutil
import subprocess
import tempfile
import uuid
import zipfile
from pathlib import Path

import olefile
from docx import Document
from fastapi import FastAPI, File, HTTPException, UploadFile
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("converter_service")

MAX_FILE_SIZE_MB = int(os.getenv("MAX_FILE_SIZE_MB", "50"))
SOFFICE_TIMEOUT = int(os.getenv("SOFFICE_TIMEOUT", "120"))

# Formatos que el usuario puede subir (validación por extensión declarada).
ALLOWED_EXTENSIONS = {".doc", ".docx", ".rtf", ".odt"}

# Etiquetas legibles por formato real detectado.
FORMAT_LABELS = {
    "doc": "Word 97-2003 (.doc)",
    "docx": "Word (OOXML, .docx)",
    "rtf": "Texto enriquecido (.rtf)",
    "odt": "OpenDocument (.odt)",
}

# Formatos que se convierten con LibreOffice.
CONVERTIBLE = {"doc", "rtf", "odt"}

app = FastAPI(
    title="Alfa Converter Service",
    description="Conversión de documentos a .docx con LibreOffice headless",
    version="1.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["GET", "POST"],
    allow_headers=["*"],
)


# ── Esquemas ──────────────────────────────────────────────────────────────────
class SourceInfo(BaseModel):
    filename: str
    detected_format: str
    format_label: str
    size_bytes: int
    extension_mismatch: bool


class ResultInfo(BaseModel):
    status: str  # "converted" | "passthrough" | "rejected"
    filename: str | None = None
    size_bytes: int | None = None
    docx_base64: str | None = None


class ConvertResponse(BaseModel):
    source: SourceInfo
    result: ResultInfo
    warnings: list[str]
    message: str


class HealthResponse(BaseModel):
    status: str
    libreoffice: str


# ── Detección de formato por contenido ────────────────────────────────────────
OLE2_MAGIC = b"\xd0\xcf\x11\xe0\xa1\xb1\x1a\xe1"


def detect_format(data: bytes) -> str:
    """
    Devuelve el formato REAL del documento a partir de sus bytes:
        "doc" | "docx" | "rtf" | "odt"
        "ooxml_encrypted" | "ooxml_other" | "ole_unknown" | "zip_unknown" | "unknown"
    """
    head = data[:8]

    # RTF — texto plano que empieza con "{\rtf"
    if data[:5] == b"{\\rtf":
        return "rtf"

    # OLE2 / Compound File (Word 97-2003, Excel, PowerPoint, OOXML encriptado…)
    if head == OLE2_MAGIC:
        try:
            with olefile.OleFileIO(io.BytesIO(data)) as ole:
                streams = {"/".join(entry).lower() for entry in ole.listdir()}
            if "worddocument" in streams:
                return "doc"
            if "encryptedpackage" in streams:
                return "ooxml_encrypted"
        except Exception as exc:  # noqa: BLE001
            logger.warning("No se pudo inspeccionar el contenedor OLE2: %s", exc)
        return "ole_unknown"

    # ZIP — DOCX y ODT son ambos contenedores ZIP
    if data[:2] == b"PK":
        try:
            with zipfile.ZipFile(io.BytesIO(data)) as zf:
                if zf.testzip() is not None:
                    return "zip_unknown"
                names = set(zf.namelist())
                mimetype = ""
                if "mimetype" in names:
                    try:
                        mimetype = zf.read("mimetype").decode("ascii", "ignore").strip()
                    except Exception:  # noqa: BLE001
                        mimetype = ""
        except zipfile.BadZipFile:
            return "zip_unknown"
        except Exception as exc:  # noqa: BLE001
            logger.warning("No se pudo inspeccionar el contenedor ZIP: %s", exc)
            return "zip_unknown"

        if mimetype == "application/vnd.oasis.opendocument.text":
            return "odt"
        if mimetype.startswith("application/vnd.oasis.opendocument"):
            return "ooxml_other"  # otro ODF (hoja de cálculo / presentación)
        if "[Content_Types].xml" in names:
            if any(n.startswith("word/") for n in names):
                return "docx"
            if any(n.startswith(("ppt/", "xl/")) for n in names):
                return "ooxml_other"
        return "zip_unknown"

    return "unknown"


REJECTION_MESSAGES = {
    "ooxml_encrypted": (
        "El documento está protegido con contraseña o encriptado. "
        "Quitá la protección desde Word (Archivo → Información → Proteger documento) "
        "y volvé a subirlo."
    ),
    "ooxml_other": (
        "El contenido del archivo no es un documento de texto, sino una hoja de cálculo "
        "o una presentación. Esta herramienta solo convierte documentos de texto."
    ),
    "ole_unknown": (
        "El archivo tiene un formato binario de Office antiguo que no es un documento "
        "de Word (podría ser Excel o PowerPoint). Verificá el archivo original."
    ),
    "zip_unknown": (
        "El archivo parece estar dañado o no es un documento válido "
        "(el contenedor interno no se pudo leer). Volvé a exportarlo desde el programa de origen."
    ),
    "unknown": (
        "No se pudo reconocer el formato del archivo a partir de su contenido. "
        "Asegurate de subir un documento .doc, .docx, .rtf u .odt real."
    ),
}


# ── Validación profunda de un .docx ───────────────────────────────────────────
def validate_docx(path: Path) -> tuple[bool, str]:
    """
    Comprueba que un .docx sea realmente abrible y bien formado.
    Devuelve (ok, motivo_si_falla).
    """
    try:
        with zipfile.ZipFile(path) as zf:
            if zf.testzip() is not None:
                return False, "el contenedor ZIP interno está dañado"
            names = set(zf.namelist())
            if "[Content_Types].xml" not in names or "word/document.xml" not in names:
                return False, "faltan partes obligatorias del formato OOXML"
    except zipfile.BadZipFile:
        return False, "no es un archivo ZIP/OOXML válido"

    try:
        Document(str(path))
    except Exception as exc:  # noqa: BLE001
        return False, f"el documento no se pudo abrir ({exc})"

    return True, ""


# ── Conversión con LibreOffice ───────────────────────────────────────────────
def convert_with_libreoffice(src_path: Path, out_dir: Path) -> Path:
    """
    Convierte `src_path` a .docx dentro de `out_dir` usando soffice headless.
    Cada invocación usa un perfil de usuario propio → seguro para concurrencia.
    """
    profile = f"file://{out_dir}/lo_profile_{uuid.uuid4().hex}"
    cmd = [
        "soffice",
        "--headless",
        "--nologo",
        "--nofirststartwizard",
        f"-env:UserInstallation={profile}",
        "--convert-to",
        "docx:MS Word 2007 XML",
        "--outdir",
        str(out_dir),
        str(src_path),
    ]

    try:
        proc = subprocess.run(
            cmd,
            capture_output=True,
            text=True,
            timeout=SOFFICE_TIMEOUT,
            env={**os.environ, "HOME": "/tmp"},
        )
    except subprocess.TimeoutExpired:
        raise RuntimeError(
            f"La conversión superó el tiempo límite ({SOFFICE_TIMEOUT}s). "
            "El documento puede ser demasiado grande o complejo."
        )

    out_path = out_dir / (src_path.stem + ".docx")
    if proc.returncode != 0 or not out_path.exists() or out_path.stat().st_size == 0:
        detail = (proc.stderr or proc.stdout or "sin detalle").strip()
        raise RuntimeError(f"LibreOffice no pudo convertir el documento: {detail}")

    return out_path


def libreoffice_version() -> str:
    try:
        proc = subprocess.run(
            ["soffice", "--version"], capture_output=True, text=True, timeout=15
        )
        return proc.stdout.strip() or "desconocida"
    except Exception:  # noqa: BLE001
        return "no disponible"


# ── Endpoints ────────────────────────────────────────────────────────────────
@app.get("/health", response_model=HealthResponse)
def health():
    return HealthResponse(status="ok", libreoffice=libreoffice_version())


@app.post("/convert", response_model=ConvertResponse)
async def convert(file: UploadFile = File(...)):
    filename = file.filename or "documento"
    declared_ext = Path(filename).suffix.lower()

    content = await file.read()
    size_bytes = len(content)
    size_mb = size_bytes / (1024 * 1024)

    if size_bytes == 0:
        raise HTTPException(422, "El archivo está vacío.")
    if size_mb > MAX_FILE_SIZE_MB:
        raise HTTPException(
            413,
            f"El archivo supera el límite de {MAX_FILE_SIZE_MB} MB ({size_mb:.1f} MB).",
        )

    detected = detect_format(content)
    warnings: list[str] = []

    # Formato real no soportado → rechazo con mensaje específico.
    if detected in REJECTION_MESSAGES:
        source = SourceInfo(
            filename=filename,
            detected_format=detected,
            format_label="Formato no soportado",
            size_bytes=size_bytes,
            extension_mismatch=False,
        )
        return ConvertResponse(
            source=source,
            result=ResultInfo(status="rejected"),
            warnings=warnings,
            message=REJECTION_MESSAGES[detected],
        )

    # Aviso si la extensión declarada no coincide con el contenido real.
    ext_for_detected = f".{detected}"
    extension_mismatch = declared_ext in ALLOWED_EXTENSIONS and declared_ext != ext_for_detected
    if extension_mismatch:
        warnings.append(
            f"El archivo tenía extensión «{declared_ext}» pero su contenido real es "
            f"{FORMAT_LABELS[detected]}. Se procesó según el contenido real."
        )

    source = SourceInfo(
        filename=filename,
        detected_format=detected,
        format_label=FORMAT_LABELS[detected],
        size_bytes=size_bytes,
        extension_mismatch=extension_mismatch,
    )

    base_name = Path(filename).stem or "documento"
    out_name = f"{base_name}.docx"
    work_dir = Path(tempfile.mkdtemp(prefix="conv_"))

    try:
        # ── .docx de entrada: validar y (si está OK) devolver sin tocar ──────
        if detected == "docx":
            src_path = work_dir / f"input{declared_ext or '.docx'}"
            src_path.write_bytes(content)
            ok, reason = validate_docx(src_path)
            if not ok:
                return ConvertResponse(
                    source=source,
                    result=ResultInfo(status="rejected"),
                    warnings=warnings,
                    message=(
                        f"El archivo tiene formato .docx pero su contenido no es un documento "
                        f"de Word válido ({reason}). Volvé a abrirlo y guardarlo desde Word, "
                        f"o exportá de nuevo el original."
                    ),
                )

            logger.info("convert: passthrough .docx válido (%s, %d bytes)", filename, size_bytes)
            return ConvertResponse(
                source=source,
                result=ResultInfo(
                    status="passthrough",
                    filename=out_name,
                    size_bytes=size_bytes,
                    docx_base64=base64.b64encode(content).decode("ascii"),
                ),
                warnings=warnings,
                message="El archivo ya era un .docx válido; se entrega sin modificaciones.",
            )

        # ── .doc / .rtf / .odt → convertir con LibreOffice ──────────────────
        src_path = work_dir / f"input{ext_for_detected}"
        src_path.write_bytes(content)

        logger.info("convert: %s (%s, %.1f MB) → docx vía LibreOffice", filename, detected, size_mb)
        out_path = convert_with_libreoffice(src_path, work_dir)

        docx_bytes = out_path.read_bytes()

        # Sanidad: el .docx recién generado tiene que ser abrible.
        ok, reason = validate_docx(out_path)
        if not ok:
            raise RuntimeError(f"el .docx generado no pasó la validación ({reason})")

        logger.info("convert: OK (%s → %s, %d bytes)", filename, out_name, len(docx_bytes))
        return ConvertResponse(
            source=source,
            result=ResultInfo(
                status="converted",
                filename=out_name,
                size_bytes=len(docx_bytes),
                docx_base64=base64.b64encode(docx_bytes).decode("ascii"),
            ),
            warnings=warnings,
            message=f"Documento convertido de {FORMAT_LABELS[detected]} a .docx correctamente.",
        )

    except RuntimeError as exc:
        logger.error("convert: fallo de conversión (%s): %s", filename, exc)
        raise HTTPException(422, str(exc))
    finally:
        shutil.rmtree(work_dir, ignore_errors=True)
