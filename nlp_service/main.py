"""
nlp_service/main.py
Microservicio NLP para análisis de entidades en documentos judiciales.

依赖:
    pip install fastapi uvicorn spacy
    python -m spacy download es_core_news_md
"""

import re
import html as html_module
from typing import Any

import spacy
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel

# ── Carga del modelo ──────────────────────────────────────────────────────────
try:
    nlp = spacy.load("es_core_news_md")
except OSError:
    raise RuntimeError(
        "Modelo spaCy no encontrado. Ejecuta: python -m spacy download es_core_news_md"
    )

app = FastAPI(title="NLP Entity Service", version="1.0.0")

# Permitir llamadas desde Laravel (ajustar origins en producción)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["POST"],
    allow_headers=["*"],
)


# ── Esquemas ─────────────────────────────────────────────────────────────────

class AnalyzeRequest(BaseModel):
    text: str


class EntityResult(BaseModel):
    text: str
    label: str
    start: int
    end: int
    source: str  # "spacy" | "regex"


class AnalyzeResponse(BaseModel):
    html: str
    entities: list[EntityResult]


# ── Mapas de entidades ────────────────────────────────────────────────────────

# spaCy label → clase CSS
SPACY_CLASS: dict[str, str] = {
    "PER":   "person",
    "PERSON":"person",
    "ORG":   "org",
    "LOC":   "location",
    "GPE":   "location",
    "DATE":  "date",
    "MISC":  "misc",
}

# Patrones regex adicionales: (class, label, pattern)
REGEX_PATTERNS: list[tuple[str, str, re.Pattern]] = [
    ("dni",    "DNI",      re.compile(r"\b\d{7,8}\b")),
    ("email",  "EMAIL",    re.compile(r"[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}")),
    ("phone",  "PHONE",    re.compile(r"\+?\d[\d\s\-]{7,14}\d")),
    ("misc",   "PATENTE",  re.compile(r"\b[A-Z]{2,3}\d{3}[A-Z]{2}\b")),
]


# ── Helpers ───────────────────────────────────────────────────────────────────

def _span_tag(text: str, css_class: str, label: str) -> str:
    """Envuelve texto en un <span> con la clase de entidad."""
    safe = html_module.escape(text)
    return (
        f'<span class="entity {css_class}" data-label="{label}" title="{label}">'
        f'{safe}</span>'
    )


def _escape(text: str) -> str:
    """Escapa HTML y convierte saltos de línea en <br> para renderizar en el navegador."""
    return html_module.escape(text).replace("\n", "<br>\n")


def build_annotated_html(text: str, spans: list[dict[str, Any]]) -> str:
    """
    Dado el texto original y una lista de spans ordenados (sin solapamientos),
    produce el HTML con entidades etiquetadas.
    Los saltos de línea del texto se convierten en <br> para preservar
    la estructura del documento en el navegador.
    """
    result = []
    cursor = 0
    for span in sorted(spans, key=lambda s: s["start"]):
        start, end = span["start"], span["end"]
        # Texto entre la posición anterior y esta entidad
        result.append(_escape(text[cursor:start]))
        result.append(_span_tag(text[start:end], span["css"], span["label"]))
        cursor = end
    # Resto del texto
    result.append(_escape(text[cursor:]))
    return "".join(result)


def remove_overlaps(spans: list[dict[str, Any]]) -> list[dict[str, Any]]:
    """Elimina spans solapados, priorizando el de mayor longitud."""
    sorted_spans = sorted(spans, key=lambda s: (s["start"], -(s["end"] - s["start"])))
    clean: list[dict] = []
    last_end = -1
    for s in sorted_spans:
        if s["start"] >= last_end:
            clean.append(s)
            last_end = s["end"]
    return clean


# ── Endpoint principal ────────────────────────────────────────────────────────

@app.post("/analyze", response_model=AnalyzeResponse)
def analyze(req: AnalyzeRequest) -> AnalyzeResponse:
    if not req.text.strip():
        raise HTTPException(status_code=422, detail="El campo 'text' no puede estar vacío.")

    text = req.text
    raw_spans: list[dict[str, Any]] = []
    entities: list[EntityResult] = []

    # 1. Entidades de spaCy
    doc = nlp(text)
    for ent in doc.ents:
        css = SPACY_CLASS.get(ent.label_, "misc")
        raw_spans.append({
            "start":  ent.start_char,
            "end":    ent.end_char,
            "css":    css,
            "label":  ent.label_,
            "source": "spacy",
        })
        entities.append(EntityResult(
            text=ent.text,
            label=ent.label_,
            start=ent.start_char,
            end=ent.end_char,
            source="spacy",
        ))

    # 2. Entidades por regex
    for css, label, pattern in REGEX_PATTERNS:
        for m in pattern.finditer(text):
            raw_spans.append({
                "start":  m.start(),
                "end":    m.end(),
                "css":    css,
                "label":  label,
                "source": "regex",
            })
            entities.append(EntityResult(
                text=m.group(),
                label=label,
                start=m.start(),
                end=m.end(),
                source="regex",
            ))

    # 3. Eliminar solapamientos y construir HTML
    clean_spans = remove_overlaps(raw_spans)
    annotated_html = build_annotated_html(text, clean_spans)

    return AnalyzeResponse(html=annotated_html, entities=entities)


# ── Health check ──────────────────────────────────────────────────────────────

@app.get("/health")
def health():
    return {"status": "ok", "model": "es_core_news_md"}
