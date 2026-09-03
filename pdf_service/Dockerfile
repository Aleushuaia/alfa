# ─── Microservicio PDF — FastAPI + pypdf + reportlab ────────────────────────
FROM python:3.12-slim

WORKDIR /app

# Deshabilitar verificación SSL (proxy corporativo con cert auto-firmado)
ENV PYTHONHTTPSVERIFY=0
ENV REQUESTS_CA_BUNDLE=""
ENV CURL_CA_BUNDLE=""
RUN pip config set global.trusted-host "pypi.org pypi.python.org files.pythonhosted.org"

RUN pip install --no-cache-dir --trusted-host pypi.org --trusted-host pypi.python.org --trusted-host files.pythonhosted.org --upgrade pip

COPY requirements.txt .
RUN pip install --no-cache-dir --trusted-host pypi.org --trusted-host pypi.python.org --trusted-host files.pythonhosted.org -r requirements.txt

COPY main.py .

EXPOSE 8003
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8003"]
