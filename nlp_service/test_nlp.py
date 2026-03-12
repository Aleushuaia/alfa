import urllib.request, json

data = json.dumps({'text': 'Juan Perez trabaja en el Tribunal de Comodoro Rivadavia. Su DNI es 32456789 y su email es juan@justicia.gob.ar. Telefono 2974123456'}).encode()
req = urllib.request.Request('http://localhost:8001/analyze', data=data, headers={'Content-Type': 'application/json'})
resp = urllib.request.urlopen(req)
result = json.loads(resp.read())

print('ENTIDADES DETECTADAS:', len(result['entities']))
for e in result['entities']:
    print(f"  [{e['label']}] {e['text']} (pos {e['start']}-{e['end']}) via {e['source']}")

print()
print('HTML (primer fragmento):')
print(result['html'][:400])
