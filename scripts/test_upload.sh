#!/bin/bash
# Test PDF upload process
curl -s -c /tmp/cookies http://localhost:8080/ -o /tmp/page.html
TOKEN=$(grep '_token' /tmp/page.html | grep -o 'value="[^"]*"' | head -1 | cut -d'"' -f2)
echo "Token: $TOKEN"
curl -s -b /tmp/cookies -c /tmp/cookies \
    -X POST http://localhost:8080/pdf-analyzer/process \
    -F "_token=$TOKEN" \
    -F "pdf=@/var/www/actuacion.pdf;type=application/pdf" \
    -o /tmp/result.html \
    -w "\nHTTP_STATUS:%{http_code}\n"
echo "--- Result size: $(wc -c < /tmp/result.html) bytes ---"
grep -i "error\|exception\|Error\|500" /tmp/result.html | cut -c1-300 | head -20
