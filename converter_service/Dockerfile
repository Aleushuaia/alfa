# ─── Microservicio Converter — FastAPI + LibreOffice headless ──────────────
# Convierte .doc / .rtf / .odt a .docx con máxima fidelidad usando soffice.
# Detecta el formato real por el contenido del archivo (no por la extensión).
FROM python:3.12-slim

WORKDIR /app

# ── Variables de entorno para proxies corporativos (cert auto-firmado) ───────
ENV PYTHONHTTPSVERIFY=0
ENV REQUESTS_CA_BUNDLE=""
ENV CURL_CA_BUNDLE=""
ENV HOME=/tmp
RUN pip config set global.trusted-host "pypi.org pypi.python.org files.pythonhosted.org"

# ── LibreOffice (solo Writer) + fuentes métrica-compatibles ──────────────────
# carlito ≈ Calibri, caladea ≈ Cambria, liberation ≈ Arial/Times/Courier.
# Con buena cobertura de fuentes la conversión evita sustituciones que
# descolocan el interlineado y el paginado.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libreoffice-writer \
        libreoffice-core \
        fonts-liberation \
        fonts-crosextra-carlito \
        fonts-crosextra-caladea \
        fonts-dejavu \
    && rm -rf /var/lib/apt/lists/*

# ── Herramientas de build ────────────────────────────────────────────────────
RUN pip install --no-cache-dir \
        --trusted-host pypi.org \
        --trusted-host pypi.python.org \
        --trusted-host files.pythonhosted.org \
        --upgrade pip

# ── Dependencias del servicio ────────────────────────────────────────────────
COPY requirements.txt .
RUN pip install --no-cache-dir \
        --trusted-host pypi.org \
        --trusted-host pypi.python.org \
        --trusted-host files.pythonhosted.org \
        -r requirements.txt

# ── Warm-up: primera ejecución de soffice (genera el perfil de usuario) ──────
# Evita que la primera petición real pague el arranque en frío de LibreOffice.
RUN printf 'warmup' > /tmp/warmup.txt \
    && soffice --headless --nologo --nofirststartwizard \
        -env:UserInstallation=file:///tmp/lo_warmup \
        --convert-to docx --outdir /tmp /tmp/warmup.txt \
    && rm -rf /tmp/warmup.txt /tmp/warmup.docx /tmp/lo_warmup

# ── Código del servicio ──────────────────────────────────────────────────────
COPY main.py .

EXPOSE 8004

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8004", "--workers", "2"]
