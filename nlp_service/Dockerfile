# ─── Microservicio NLP — FastAPI + spaCy ───────────────────────────────────
FROM python:3.12-slim

WORKDIR /app

# Deshabilitar verificación SSL (proxy corporativo con cert auto-firmado)
ENV PYTHONHTTPSVERIFY=0
ENV REQUESTS_CA_BUNDLE=""
ENV CURL_CA_BUNDLE=""
RUN pip config set global.trusted-host "pypi.org pypi.python.org files.pythonhosted.org"

# Dependencias del sistema para spaCy (compilador para algunos paquetes)
RUN apt-get update && apt-get install -y --no-install-recommends \
        build-essential \
        curl \
    && rm -rf /var/lib/apt/lists/*

# Instalar dependencias Python (sin verificar SSL)
COPY requirements.txt .
RUN pip install --no-cache-dir --trusted-host pypi.org --trusted-host pypi.python.org --trusted-host files.pythonhosted.org -r requirements.txt

# Descargar modelo español de spaCy (sin verificar SSL)
RUN PIP_TRUSTED_HOST="pypi.org files.pythonhosted.org github.com objects.githubusercontent.com githubusercontent.com" \
    python -m spacy download es_core_news_md

# Código del servicio
COPY main.py .

EXPOSE 8001

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8001", "--workers", "2"]
