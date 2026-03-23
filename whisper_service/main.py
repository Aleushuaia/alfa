"""
whisper_service/main.py
Microservicio de transcripción multimedia basado en openai-whisper.

Endpoints:
    POST /transcribe   — recibe un archivo multimedia, devuelve texto transcripto
    GET  /health       — verifica estado del servicio
"""

import os
import ssl
import tempfile
import logging
from pathlib import Path

# ── Bypass de verificación SSL para entornos con proxy corporativo ───────────
# Necesario para que urllib (usado por Whisper al descargar modelos) acepte
# certificados auto-firmados del proxy.
if os.getenv("PYTHONHTTPSVERIFY", "1") == "0":
    ssl._create_default_https_context = ssl._create_unverified_context

import whisper
from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel

# ── Logging ───────────────────────────────────────────────────────────────────
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("whisper_service")

# ── Configuración ─────────────────────────────────────────────────────────────
# El modelo se puede configurar mediante variable de entorno.
# Opciones: tiny, base, small, medium, large, large-v2, large-v3
WHISPER_MODEL = os.getenv("WHISPER_MODEL", "small")

# Formatos admitidos
ALLOWED_EXTENSIONS = {".mp3", ".wav", ".mp4", ".mkv", ".ogg", ".webm", ".m4a"}
MAX_FILE_SIZE_MB = int(os.getenv("MAX_FILE_SIZE_MB", "200"))

# ── Carga del modelo ──────────────────────────────────────────────────────────
logger.info(f"Cargando modelo Whisper '{WHISPER_MODEL}'…")
model = whisper.load_model(WHISPER_MODEL)
logger.info("Modelo Whisper listo.")

# ── App ───────────────────────────────────────────────────────────────────────
app = FastAPI(
    title="Whisper Transcription Service",
    description="Transcripción de audio y video usando openai-whisper",
    version="1.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["GET", "POST"],
    allow_headers=["*"],
)


# ── Esquemas ──────────────────────────────────────────────────────────────────
class TranscriptionResponse(BaseModel):
    text: str
    language: str
    duration_seconds: float | None = None
    segments_count: int | None = None


class HealthResponse(BaseModel):
    status: str
    model: str


# ── Endpoints ─────────────────────────────────────────────────────────────────
@app.get("/health", response_model=HealthResponse)
def health():
    return HealthResponse(status="ok", model=WHISPER_MODEL)


@app.post("/transcribe", response_model=TranscriptionResponse)
async def transcribe(file: UploadFile = File(...)):
    """
    Recibe un archivo de audio o video, lo transcribe con Whisper y
    devuelve el texto extraído junto con metadatos básicos.
    """
    # Validar extensión
    suffix = Path(file.filename or "").suffix.lower()
    if suffix not in ALLOWED_EXTENSIONS:
        raise HTTPException(
            status_code=422,
            detail=f"Formato no admitido: '{suffix}'. "
                   f"Use: {', '.join(sorted(ALLOWED_EXTENSIONS))}",
        )

    # Leer contenido y verificar tamaño
    content = await file.read()
    size_mb = len(content) / (1024 * 1024)
    if size_mb > MAX_FILE_SIZE_MB:
        raise HTTPException(
            status_code=413,
            detail=f"El archivo supera el límite de {MAX_FILE_SIZE_MB} MB ({size_mb:.1f} MB)",
        )

    # Guardar en archivo temporal para que ffmpeg pueda procesarlo
    with tempfile.NamedTemporaryFile(suffix=suffix, delete=False) as tmp:
        tmp.write(content)
        tmp_path = tmp.name

    try:
        logger.info(f"Transcribiendo '{file.filename}' ({size_mb:.1f} MB) …")
        result = model.transcribe(tmp_path, fp16=False)
        text = result.get("text", "").strip()
        language = result.get("language", "unknown")
        segments = result.get("segments", [])
        duration = segments[-1].get("end") if segments else None

        logger.info(f"Transcripción completada: {len(text)} caracteres, idioma={language}")
        return TranscriptionResponse(
            text=text,
            language=language,
            duration_seconds=duration,
            segments_count=len(segments),
        )
    except Exception as exc:
        logger.error(f"Error al transcribir: {exc}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"Error interno de transcripción: {exc}")
    finally:
        os.unlink(tmp_path)
