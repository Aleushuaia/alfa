<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Documento Anonimizado</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.7;
            color: #1a1a1a;
            margin: 2cm 2.5cm;
        }
        h1 {
            font-size: 13pt;
            border-bottom: 1px solid #555;
            padding-bottom: 6px;
            margin-bottom: 18px;
            color: #333;
        }
        p { white-space: pre-wrap; word-wrap: break-word; }
        .footer {
            margin-top: 2cm;
            font-size: 8pt;
            color: #888;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Documento Anonimizado</h1>
    <p>{{ $text }}</p>
    <div class="footer">
        Generado por Alfa &mdash; {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
