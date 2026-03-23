# ─── Microservicio Whisper — FastAPI + openai-whisper (CPU) ─────────────────
FROM python:3.12-slim

WORKDIR /app

# ── Variables de entorno para proxies corporativos ───────────────────────────
ENV PYTHONHTTPSVERIFY=0
ENV REQUESTS_CA_BUNDLE=""
ENV CURL_CA_BUNDLE=""
RUN pip config set global.trusted-host "pypi.org pypi.python.org files.pythonhosted.org download.pytorch.org"

# ── Dependencias del sistema ──────────────────────────────────────────────────
# ffmpeg es imprescindible para que Whisper decodifique audio/video
RUN apt-get update && apt-get install -y --no-install-recommends \
        ffmpeg \
        build-essential \
        curl \
    && rm -rf /var/lib/apt/lists/*

# ── Herramientas de build ─────────────────────────────────────────────────────
RUN pip install --no-cache-dir \
        --trusted-host pypi.org \
        --trusted-host pypi.python.org \
        --trusted-host files.pythonhosted.org \
        --upgrade pip setuptools wheel

# ── PyTorch CPU-only (evita descargar cientos de MB de paquetes CUDA) ────────
# Se instala ANTES de openai-whisper para que éste reutilice este torch sin
# intentar resolver una variante CUDA.
RUN pip install --no-cache-dir \
        --trusted-host pypi.org \
        --trusted-host pypi.python.org \
        --trusted-host files.pythonhosted.org \
        --trusted-host download.pytorch.org \
        torch --index-url https://download.pytorch.org/whl/cpu

# ── openai-whisper ────────────────────────────────────────────────────────────
# Con torch ya presente no intenta instalar CUDA. numpy<2 evita conflicto.
RUN pip install --no-cache-dir \
        --trusted-host pypi.org \
        --trusted-host pypi.python.org \
        --trusted-host files.pythonhosted.org \
        "numpy<2" openai-whisper
# ── Pre-descargar el modelo Whisper durante el build ────────────────────────
# Así el arranque del contenedor no depende de acceso a internet.
# PYTHONHTTPSVERIFY=0 deshabilita la verificación SSL (proxy corporativo).
ARG WHISPER_MODEL_BUILD=small
RUN PYTHONHTTPSVERIFY=0 python -c \
    "import ssl; ssl._create_default_https_context=ssl._create_unverified_context; import whisper; whisper.load_model('${WHISPER_MODEL_BUILD}')"
# ── Resto de dependencias del servicio ────────────────────────────────────────
COPY requirements.txt .
RUN pip install --no-cache-dir \
        --trusted-host pypi.org \
        --trusted-host pypi.python.org \
        --trusted-host files.pythonhosted.org \
        -r requirements.txt

# ── Código del servicio ───────────────────────────────────────────────────────
COPY main.py .

EXPOSE 8002

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8002", "--workers", "1"]
