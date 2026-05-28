"""
nlp_service/main.py
Microservicio NLP para analisis de entidades en documentos judiciales.

Dependencias:
    pip install fastapi uvicorn spacy
    python -m spacy download es_core_news_md
"""

import re
import html as html_module
import unicodedata
import logging
from typing import Any

import spacy
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel

logger = logging.getLogger("nlp_service")

# Intenta el modelo grande primero (mas preciso); cae al mediano si no esta instalado
_MODELS = ("es_core_news_lg", "es_core_news_md", "es_core_news_sm")
nlp = None
_MODEL_NAME = ""
for _m in _MODELS:
    try:
        nlp = spacy.load(_m)
        _MODEL_NAME = _m
        break
    except OSError:
        continue

if nlp is None:
    raise RuntimeError(
        "Ningun modelo spaCy de espanol encontrado. "
        "Ejecuta: python -m spacy download es_core_news_md"
    )

app = FastAPI(title="NLP Entity Service", version="2.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["POST"],
    allow_headers=["*"],
)


# ── Esquemas ──────────────────────────────────────────────────────────────────

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

SPACY_CLASS: dict[str, str] = {
    "PER":    "person",
    "PERSON": "person",
    "ORG":    "org",
    "LOC":    "location",
    "GPE":    "location",
    "DATE":   "date",
    "MISC":   "misc",
}

# Etiquetas que NO se filtran por POS (numericas / temporales)
NUMERIC_LABELS: frozenset[str] = frozenset({
    "DATE", "TIME", "CARDINAL", "ORDINAL", "MONEY", "QUANTITY", "PERCENT",
})

# POS-tags de spaCy que claramente NO son nombres propios
NON_PROPN_POS: frozenset[str] = frozenset({
    "ADV",   # adverbios:           asimismo, ahora, ademas, incluso
    "ADP",   # preposiciones:       de, en, con, por
    "DET",   # determinantes:       el, la, los, un, una
    "CCONJ", # conjunciones coord:  y, o, pero, ni
    "SCONJ", # conjunciones subord: que, cuando, aunque, si
    "PRON",  # pronombres:          el, ella, esto, ello, se
    "VERB",  # verbos:              dispone, ordena, resuelve
    "AUX",   # auxiliares:          ha, haber, es, fue
    "PART",  # particulas
    "INTJ",  # interjecciones
    "PUNCT", # puntuacion
    "SPACE", # espacios
    "SYM",   # simbolos
    "X",     # desconocidos cortos
})


def _norm(s: str) -> str:
    """Normaliza a minusculas sin diacriticos para comparacion robusta."""
    nfkd = unicodedata.normalize("NFKD", s.strip().lower())
    return "".join(c for c in nfkd if not unicodedata.combining(c))


# Palabras que spaCy confunde como entidades en documentos judiciales argentinos.
# Se normalizan para comparacion case-insensitive y sin tildes.
_JUDICIAL_NOISE_RAW: list[str] = [
    # Adverbios / conectores
    "asimismo", "ademas", "tambien", "tampoco", "incluso", "aun", "todavia",
    "ahora", "ahora bien", "luego", "entonces", "despues", "antes", "siempre",
    "nunca", "jamas", "ya", "asi", "pues", "pues bien", "asi pues", "asi mismo",
    "sin embargo", "no obstante", "por ende", "por tanto", "por lo tanto",
    "en consecuencia", "en efecto", "en cambio", "en definitiva",
    "por otra parte", "por un lado", "por otro lado", "por otro",
    "es decir", "o sea", "a saber", "esto es", "vale decir",
    "mientras", "mientras tanto", "mientras que", "aunque",
    "cuando", "donde", "como quiera", "toda vez",
    "dado que", "en razon de", "en virtud de", "por cuanto",
    "habida cuenta", "atento ello", "atento lo expuesto",
    # Terminos de apertura de resoluciones
    "visto", "vistas", "vistos", "vista", "visto que", "vistos los autos",
    "considerando", "resultando", "atento", "habiendo", "teniendo",
    "teniendo en cuenta", "teniendo presente", "teniendo a la vista",
    "en autos", "en el caso", "en la causa", "en la presente",
    "conforme", "de conformidad", "en conformidad",
    "surge de autos", "de las constancias",
    # Verbos capitalizados en resolutivos
    "resuelve", "dispone", "ordena", "establece", "determina",
    "corresponde", "procede", "cabe", "surge", "resulta",
    "advierte", "observa", "senala", "indica", "expresa",
    "hace saber", "hagase saber", "notifiquese", "registrese",
    "publiquese", "archivese", "cumplase",
    # Sustantivos comunes mal detectados
    "auto", "autos", "causa", "causas", "expediente", "expedientes",
    "actuaciones", "fojas", "presentacion", "sentencia", "resolucion",
    "decreto", "acuerdo", "providencia", "interlocutorio",
    "demanda", "recurso", "apelacion", "casacion", "queja",
    "actor", "actora", "demandado", "demandada", "parte", "partes",
    "juez", "jueza", "tribunal", "camara", "sala", "juzgado",
    "secretaria", "secretario", "ministerio",
    "ciudad", "provincia", "nacion", "republica", "estado",
    "instancia", "grado", "fuero", "jurisdiccion", "competencia",
    # Roles de partes procesales — cuando preceden a un nombre forman un span MISC erroneo
    "acusado", "acusada", "imputado", "imputada",
    "procesado", "procesada", "condenado", "condenada",
    "querellado", "querellada", "querellante",
    "denunciado", "denunciada", "denunciante",
    "detenido", "detenida", "arrestado", "arrestada",
    "testigo", "perito", "perita", "oficial",
    "senor", "senora", "senores", "don", "dona",
    "licenciado", "licenciada", "doctor", "doctora", "dr", "dra",
    # Abreviaturas
    "art", "arts", "inc", "ley", "dec", "res", "cfr", "idem",
    # Meses sueltos
    "enero", "febrero", "marzo", "abril", "mayo", "junio",
    "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre",
    # Dias de la semana
    "lunes", "martes", "miercoles", "jueves", "viernes", "sabado", "domingo",
]

JUDICIAL_NOISE: frozenset[str] = frozenset(_norm(w) for w in _JUDICIAL_NOISE_RAW)


def is_noise_entity(ent: Any) -> bool:
    """
    Retorna True si la entidad detectada por spaCy es un falso positivo.

    Estrategias en orden:
      1. Lista explicita de ruido judicial (lookup O(1) normalizado).
      2. Un solo token que es stopword de spaCy.
      3. Todos los tokens son categoria funcional (ADV, VERB, DET...).
      4. Para PER: ningun token es PROPN (nombre propio).
    """
    label = ent.label_

    # Etiquetas numericas/temporales: solo filtrar por lista explicita
    if label in NUMERIC_LABELS:
        return _norm(ent.text) in JUDICIAL_NOISE

    # 1. Lista negra explicita
    if _norm(ent.text) in JUDICIAL_NOISE:
        return True

    tokens = list(ent)

    # 2. Un solo token stopword
    if len(tokens) == 1 and tokens[0].is_stop:
        return True

    # 3. Todos los tokens son categorias funcionales
    if all(t.pos_ in NON_PROPN_POS or t.is_stop for t in tokens):
        return True

    # 4. Para personas y MISC: debe haber al menos un PROPN
    #    MISC sin ningun nombre propio es casi siempre una frase funcional
    #    ("Vistas las actuaciones", "Teniendo en cuenta", etc.)
    if label in ("PER", "PERSON", "MISC") and not any(t.pos_ == "PROPN" for t in tokens):
        return True

    # 5. Primer token de la entidad en lista de ruido judicial
    #    Captura frases como "Vistas las actuaciones" cuyo inicio ya es ruido
    if _norm(tokens[0].text) in JUDICIAL_NOISE:
        return True

    # 6. MISC con primer token DET/PRON/ADP + segundo token de ruido judicial
    #    Captura "El acusado Juan Garcia", "La imputada Maria", "El senor Lopez"
    #    que spaCy etiqueta como MISC cuando deberia detectar solo el nombre.
    #    El primer token es un articulo/preposicion que "envuelve" el nombre.
    if label == "MISC" and len(tokens) >= 2:
        if tokens[0].pos_ in ("DET", "PRON") and _norm(tokens[1].text) in JUDICIAL_NOISE:
            return True

    return False


# Patrones regex adicionales: (class, label, pattern)
REGEX_PATTERNS: list[tuple[str, str, re.Pattern]] = [
    ("dni",   "DNI",     re.compile(r"\b\d{7,8}\b")),
    ("email", "EMAIL",   re.compile(r"[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}")),
    ("phone", "PHONE",   re.compile(r"\+?\d[\d\s\-]{7,14}\d")),
    ("misc",  "PATENTE", re.compile(r"\b[A-Z]{2,3}\d{3}[A-Z]{2}\b")),
]


# ── Helpers ───────────────────────────────────────────────────────────────────

def _span_tag(text: str, css_class: str, label: str) -> str:
    """Envuelve texto en un <span> con la clase de entidad."""
    safe = html_module.escape(text)
    return (
        f'<span class="entity {css_class}" data-label="{label}">'
        f'{safe}</span>'
    )


def _escape(text: str) -> str:
    """Escapa HTML y convierte saltos de linea en <br> para renderizar en el navegador."""
    return html_module.escape(text).replace("\n", "<br>\n")


def build_annotated_html(text: str, spans: list[dict[str, Any]]) -> str:
    """
    Dado el texto original y una lista de spans ordenados (sin solapamientos),
    produce el HTML con entidades etiquetadas.
    """
    result = []
    cursor = 0
    for span in sorted(spans, key=lambda s: s["start"]):
        start, end = span["start"], span["end"]
        result.append(_escape(text[cursor:start]))
        result.append(_span_tag(text[start:end], span["css"], span["label"]))
        cursor = end
    result.append(_escape(text[cursor:]))
    return "".join(result)


# Prioridad de tipos de entidad: menor número = mayor prioridad.
# PER siempre gana sobre MISC cuando hay solapamiento.
_LABEL_PRIORITY: dict[str, int] = {
    "PER": 1, "PERSON": 1,
    "DNI": 2, "EMAIL": 2, "PHONE": 2,
    "ORG": 3, "LOC": 3, "GPE": 3,
    "DATE": 4,
    "MISC": 5,
}


def remove_overlaps(spans: list[dict[str, Any]]) -> list[dict[str, Any]]:
    """Elimina spans solapados priorizando por tipo (PER > ORG/LOC > DATE > MISC)
    y luego por longitud. Los spans de la whitelist tienen prioridad máxima."""
    if not spans:
        return []

    # Ordenar: primero por prioridad de tipo (menor = gana), luego por longitud desc
    def _sort_key(s: dict) -> tuple:
        priority = _LABEL_PRIORITY.get(s.get("label", ""), 5)
        length = -(s["end"] - s["start"])  # negativo: más largo gana si mismo tipo
        return (priority, s["start"], length)

    sorted_spans = sorted(spans, key=_sort_key)

    # Greedy: aceptar spans en orden de prioridad, descartando los que solapan
    accepted: list[dict] = []
    occupied: list[tuple[int, int]] = []

    for s in sorted_spans:
        start, end = s["start"], s["end"]
        if not any(start < ae and end > as_ for as_, ae in occupied):
            accepted.append(s)
            occupied.append((start, end))

    # Devolver en orden de posición para construir el HTML correctamente
    return sorted(accepted, key=lambda s: s["start"])


# ── Endpoint principal ────────────────────────────────────────────────────────

@app.post("/analyze", response_model=AnalyzeResponse)
def analyze(req: AnalyzeRequest) -> AnalyzeResponse:
    if not req.text.strip():
        raise HTTPException(status_code=422, detail="El campo 'text' no puede estar vacio.")

    text = req.text
    raw_spans: list[dict[str, Any]] = []
    entities: list[EntityResult] = []
    noise_count = 0

    # 1. Entidades de spaCy
    doc = nlp(text)
    for ent in doc.ents:
        if is_noise_entity(ent):
            noise_count += 1
            logger.debug(
                "Ruido descartado: %r [%s] pos=%s",
                ent.text, ent.label_, [t.pos_ for t in ent],
            )
            continue

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

    if noise_count:
        logger.info("Entidades de ruido descartadas: %d", noise_count)

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
    return {"status": "ok", "model": _MODEL_NAME}


# ── Endpoint de diagnostico (para depurar falsos positivos) ──────────────────

@app.post("/debug-entity")
def debug_entity(req: AnalyzeRequest):
    """
    Devuelve POS-tags y si cada entidad seria descartada como ruido.
    Uso: POST /debug-entity  {"text": "Asimismo el tribunal ordeno..."}
    """
    doc = nlp(req.text)
    return {
        "model": _MODEL_NAME,
        "tokens": [
            {
                "text":     t.text,
                "pos":      t.pos_,
                "tag":      t.tag_,
                "is_stop":  t.is_stop,
                "ent_type": t.ent_type_,
            }
            for t in doc
        ],
        "entities_raw": [
            {
                "text":       e.text,
                "label":      e.label_,
                "is_noise":   is_noise_entity(e),
                "tokens_pos": [t.pos_ for t in e],
                "has_propn":  any(t.pos_ == "PROPN" for t in e),
                "all_stop":   all(t.is_stop for t in e),
            }
            for e in doc.ents
        ],
    }