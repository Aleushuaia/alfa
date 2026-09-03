"""
pdf_service/main.py
Microservicio de fusión de PDF para Alfa (PDF Tools → Unir PDF).

Combina varios PDF en el orden recibido, agrega una portada con índice
visual (tabla con enlaces internos) y marcadores/outline por documento,
para poder navegar rápido entre los documentos originales dentro del
PDF final. Implementado con `pypdf` (BSD-3) + `reportlab` (BSD).

Endpoints:
    POST /merge   — recibe varios PDF, devuelve el PDF unido (base64) + índice
    GET  /health  — verifica estado del servicio
"""

import base64
import io
import logging
import math
import os
from dataclasses import dataclass

from fastapi import FastAPI, File, HTTPException, UploadFile
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from pypdf import PdfReader, PdfWriter
from reportlab.lib.pagesizes import letter
from reportlab.pdfgen import canvas

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("pdf_service")

MAX_FILE_SIZE_MB = int(os.getenv("MAX_FILE_SIZE_MB", "100"))
MAX_FILES = int(os.getenv("MAX_FILES", "30"))

PAGE_WIDTH, PAGE_HEIGHT = letter
MARGIN = 50
ROW_HEIGHT = 22
ROWS_PER_PAGE = int((PAGE_HEIGHT - 160) // ROW_HEIGHT)

app = FastAPI(
    title="Alfa PDF Service",
    description="Fusión de PDF con índice visual y marcadores (pypdf + reportlab)",
    version="1.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["GET", "POST"],
    allow_headers=["*"],
)


class DocumentInfo(BaseModel):
    filename: str
    title: str
    pages: int
    start_page: int
    end_page: int


class MergeResponse(BaseModel):
    total_pages: int
    documents: list[DocumentInfo]
    pdf_base64: str


class HealthResponse(BaseModel):
    status: str


@dataclass
class SourceDocument:
    filename: str
    title: str
    content: bytes
    pages: int
    start_page: int = 0
    end_page: int = 0


@app.get("/health", response_model=HealthResponse)
def health():
    return HealthResponse(status="ok")


@app.post("/merge", response_model=MergeResponse)
async def merge(files: list[UploadFile] = File(...)):
    if len(files) < 2:
        raise HTTPException(422, "Se requieren al menos dos archivos PDF para unir.")
    if len(files) > MAX_FILES:
        raise HTTPException(422, f"Máximo {MAX_FILES} archivos por fusión.")

    documents = await _read_and_validate(files)

    cover_pages_count = max(1, math.ceil(len(documents) / ROWS_PER_PAGE))
    _assign_page_ranges(documents, offset=cover_pages_count)

    cover_bytes, link_targets = _build_index_cover(documents)

    writer = PdfWriter()
    _append_pdf_pages(writer, cover_bytes)
    for doc in documents:
        _append_pdf_pages(writer, doc.content)

    _add_outline(writer, documents)
    _add_index_links(writer, link_targets)

    output = io.BytesIO()
    writer.write(output)

    total_pages = cover_pages_count + sum(doc.pages for doc in documents)
    logger.info(f"Fusión OK: {len(documents)} documentos, {total_pages} páginas totales.")

    return MergeResponse(
        total_pages=total_pages,
        documents=[
            DocumentInfo(
                filename=doc.filename,
                title=doc.title,
                pages=doc.pages,
                start_page=doc.start_page,
                end_page=doc.end_page,
            )
            for doc in documents
        ],
        pdf_base64=base64.b64encode(output.getvalue()).decode("ascii"),
    )


async def _read_and_validate(files: list[UploadFile]) -> list[SourceDocument]:
    """Lee cada archivo, valida tamaño/formato y cuenta sus páginas."""
    documents: list[SourceDocument] = []

    for upload in files:
        filename = upload.filename or "documento.pdf"
        content = await upload.read()

        size_mb = len(content) / (1024 * 1024)
        if size_mb > MAX_FILE_SIZE_MB:
            raise HTTPException(
                413, f"'{filename}' supera el límite de {MAX_FILE_SIZE_MB} MB ({size_mb:.1f} MB).",
            )

        try:
            reader = PdfReader(io.BytesIO(content))
            page_count = len(reader.pages)
        except Exception as exc:
            raise HTTPException(422, f"'{filename}' no es un PDF válido: {exc}")

        if page_count == 0:
            raise HTTPException(422, f"'{filename}' no contiene páginas.")

        documents.append(SourceDocument(
            filename=filename,
            title=_titleize(filename),
            content=content,
            pages=page_count,
        ))

    return documents


def _titleize(filename: str) -> str:
    name = filename.rsplit(".", 1)[0].replace("_", " ").replace("-", " ").strip()
    name = " ".join(name.split())
    return (name[:70] + "…") if len(name) > 70 else (name or filename)


def _assign_page_ranges(documents: list[SourceDocument], offset: int) -> None:
    """Calcula el rango de fojas (1-indexado) de cada documento en el PDF final."""
    current = offset
    for doc in documents:
        doc.start_page = current + 1
        doc.end_page = current + doc.pages
        current = doc.end_page


def _build_index_cover(documents: list[SourceDocument]):
    """
    Genera la(s) página(s) de portada con la tabla de índice usando reportlab.
    Devuelve los bytes del PDF de portada y la lista de rectángulos clickeables
    (page_index dentro de la portada, rect, página destino en el PDF final).
    """
    buffer = io.BytesIO()
    pdf = canvas.Canvas(buffer, pagesize=letter)
    link_targets = []  # (cover_page_index, rect, target_page_index)

    cover_page_index = 0
    y = _draw_cover_header(pdf, first_page=True)

    for position, doc in enumerate(documents, start=1):
        if y < MARGIN + ROW_HEIGHT:
            pdf.showPage()
            cover_page_index += 1
            y = _draw_cover_header(pdf, first_page=False)

        row_top = y
        pdf.setFont("Helvetica", 9)
        pdf.drawString(MARGIN, y, f"{position}.")
        pdf.drawString(MARGIN + 30, y, doc.title)
        pdf.drawRightString(PAGE_WIDTH - MARGIN - 90, y, f"{doc.pages} pág.")
        pdf.drawRightString(PAGE_WIDTH - MARGIN, y, f"Foja {doc.start_page}-{doc.end_page}")

        rect = (MARGIN - 5, row_top - 6, PAGE_WIDTH - MARGIN, row_top + 12)
        link_targets.append((cover_page_index, rect, doc.start_page - 1))

        y -= ROW_HEIGHT

    pdf.showPage()
    pdf.save()
    return buffer.getvalue(), link_targets


def _draw_cover_header(pdf: canvas.Canvas, first_page: bool) -> float:
    y = PAGE_HEIGHT - MARGIN
    if first_page:
        pdf.setFont("Helvetica-Bold", 16)
        pdf.drawString(MARGIN, y, "Índice del documento unido")
        y -= 30
    else:
        pdf.setFont("Helvetica-Bold", 13)
        pdf.drawString(MARGIN, y, "Índice del documento unido (continuación)")
        y -= 26

    pdf.setFont("Helvetica-Bold", 9)
    pdf.drawString(MARGIN, y, "Nº")
    pdf.drawString(MARGIN + 30, y, "Documento")
    pdf.drawRightString(PAGE_WIDTH - MARGIN - 90, y, "Páginas")
    pdf.drawRightString(PAGE_WIDTH - MARGIN, y, "Fojas")
    y -= 8
    pdf.line(MARGIN, y, PAGE_WIDTH - MARGIN, y)
    y -= 16
    return y


def _append_pdf_pages(writer: PdfWriter, content: bytes) -> None:
    reader = PdfReader(io.BytesIO(content))
    for page in reader.pages:
        writer.add_page(page)


def _add_outline(writer: PdfWriter, documents: list[SourceDocument]) -> None:
    """Marcadores/outline navegables desde el panel lateral del visor de PDF."""
    writer.add_outline_item("Índice", 0)
    for doc in documents:
        writer.add_outline_item(doc.title, doc.start_page - 1)


def _add_index_links(writer: PdfWriter, link_targets) -> None:
    """Enlaces internos clickeables desde cada fila de la tabla de índice."""
    try:
        from pypdf.annotations import Link
        from pypdf.generic import Fit
    except ImportError:
        logger.warning("pypdf.annotations.Link no disponible; se omiten enlaces clickeables (quedan los marcadores).")
        return

    for cover_page_index, rect, target_page_index in link_targets:
        try:
            link = Link(rect=rect, target_page_index=target_page_index, fit=Fit("/Fit"))
            writer.add_annotation(page_number=cover_page_index, annotation=link)
        except Exception as exc:
            logger.warning(f"No se pudo agregar el enlace de índice: {exc}")
