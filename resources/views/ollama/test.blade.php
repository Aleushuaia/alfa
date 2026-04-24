@extends('layouts.dashboard')

@section('title', 'Probar modelo — ' . config('app.name', 'Alfa'))
@section('page-title', 'Probar modelo')
@section('breadcrumb', 'Ollama')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────────────────────── */
.ollama-wrap {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    height: calc(100vh - var(--topbar-h, 60px) - 2.5rem);
    min-height: 500px;
}
.t-card {
    background: var(--card-bg);
    border-radius: var(--card-radius, 14px);
    box-shadow: var(--card-shadow, 0 2px 20px rgba(0,0,0,.07));
    border: 1px solid var(--card-border);
    padding: 1.4rem 1.75rem;
    color: var(--body-color);
}
.t-card-header {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1rem;
}
.t-card-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1rem; flex-shrink: 0;
    box-shadow: 0 4px 14px var(--accent-glow, rgba(59,130,246,.35));
}
.t-card-header h5 {
    margin: 0; font-size: 1rem; font-weight: 700; color: var(--heading-color);
}
.t-card-header p {
    margin: 0; font-size: .77rem; color: var(--muted-color);
}

/* ── Barra de estado del modelo ─────────────────────────────────────────── */
.model-status-bar {
    display: flex;
    align-items: center;
    gap: .5rem;
    font-size: .78rem;
    color: var(--muted-color);
    padding: .45rem .8rem;
    border-radius: 8px;
    background: var(--table-hover-bg, rgba(0,0,0,.04));
    border: 1px solid var(--card-border);
    flex-wrap: wrap;
}
.model-status-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #94a3b8;
    flex-shrink: 0;
    transition: background .3s;
}
.model-status-dot.online  { background: #10b981; box-shadow: 0 0 6px #10b98180; }
.model-status-dot.error   { background: #ef4444; }
.model-status-dot.loading { background: #f59e0b; animation: pulse-dot 1s infinite; }
@keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:.3} }

/* ── Historial de chat ───────────────────────────────────────────────────── */
.chat-history {
    flex: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: .9rem;
    padding: .5rem 0;
    min-height: 0;
}
.chat-history::-webkit-scrollbar { width: 5px; }
.chat-history::-webkit-scrollbar-track { background: transparent; }
.chat-history::-webkit-scrollbar-thumb { background: var(--card-border); border-radius: 10px; }

/* ── Burbujas ────────────────────────────────────────────────────────────── */
.bubble-row {
    display: flex;
    gap: .6rem;
    align-items: flex-end;
}
.bubble-row.user  { flex-direction: row-reverse; }
.bubble-row.model { flex-direction: row; }

.bubble-avatar {
    width: 32px; height: 32px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; flex-shrink: 0;
    font-weight: 700;
}
.bubble-row.user .bubble-avatar {
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #fff;
}
.bubble-row.model .bubble-avatar {
    background: var(--table-hover-bg, rgba(0,0,0,.08));
    color: var(--muted-color);
    border: 1px solid var(--card-border);
}

.bubble {
    max-width: 78%;
    padding: .65rem .95rem;
    border-radius: 14px;
    font-size: .875rem;
    line-height: 1.7;
    white-space: pre-wrap;
    word-break: break-word;
}
.bubble-row.user .bubble {
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #fff;
    border-bottom-right-radius: 4px;
}
.bubble-row.model .bubble {
    background: var(--table-hover-bg, rgba(0,0,0,.05));
    color: var(--body-color);
    border: 1px solid var(--card-border);
    border-bottom-left-radius: 4px;
}
.bubble-row.model.error .bubble {
    background: rgba(239,68,68,.08);
    border-color: rgba(239,68,68,.3);
    color: #ef4444;
}

/* Typing indicator */
.typing-dots span {
    display: inline-block;
    width: 7px; height: 7px;
    border-radius: 50%;
    background: var(--muted-color);
    margin: 0 2px;
    animation: bounce-dot .9s infinite;
}
.typing-dots span:nth-child(2) { animation-delay: .15s; }
.typing-dots span:nth-child(3) { animation-delay: .3s; }
@keyframes bounce-dot { 0%,80%,100%{transform:scale(0)} 40%{transform:scale(1)} }

/* ── Input area ──────────────────────────────────────────────────────────── */
.chat-input-area {
    display: flex;
    gap: .6rem;
    align-items: flex-end;
    padding-top: .6rem;
    border-top: 1px solid var(--card-border);
    margin-top: .5rem;
}
.chat-input-area textarea {
    flex: 1;
    resize: none;
    border-radius: 10px;
    border: 1.5px solid var(--input-border);
    background: var(--input-bg);
    color: var(--body-color);
    font-size: .875rem;
    padding: .6rem .85rem;
    line-height: 1.55;
    max-height: 140px;
    min-height: 44px;
    transition: border-color .2s;
    outline: none;
    font-family: inherit;
}
.chat-input-area textarea:focus {
    border-color: var(--accent);
}
.chat-input-area textarea::placeholder { color: var(--input-placeholder, #94a3b8); }

.btn-send {
    height: 44px; min-width: 44px;
    border-radius: 10px;
    border: none;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #fff;
    font-size: 1rem;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: opacity .2s, transform .15s;
    flex-shrink: 0;
    padding: 0 1rem;
    gap: .4rem;
    font-weight: 600;
    font-size: .875rem;
}
.btn-send:disabled { opacity: .55; cursor: not-allowed; }
.btn-send:not(:disabled):hover { opacity: .9; transform: translateY(-1px); }

/* ── Chat card stretches ─────────────────────────────────────────────────── */
.chat-card {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}
.chat-card .t-card-header { margin-bottom: .5rem; }

/* ── Char counter ────────────────────────────────────────────────────────── */
.char-counter {
    font-size: .7rem;
    color: var(--muted-color);
    text-align: right;
    margin-top: .15rem;
}
.char-counter.warn { color: #f59e0b; }
.char-counter.danger { color: #ef4444; }

/* ── Empty state ─────────────────────────────────────────────────────────── */
.chat-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--muted-color);
    gap: .6rem;
    padding: 2rem 1rem;
    text-align: center;
}
.chat-empty i { font-size: 2rem; }
.chat-empty p { font-size: .82rem; line-height: 1.6; margin: 0; max-width: 340px; }
</style>
@endpush

@section('content')
<div class="ollama-wrap">

    {{-- ── Barra de estado ──────────────────────────────────────────────── --}}
    <div class="model-status-bar">
        <span class="model-status-dot" id="status-dot"></span>
        <span id="status-text">Verificando conexión con el modelo…</span>
        <span style="margin-left:auto;opacity:.7">
            <i class="fas fa-server me-1"></i>{{ $ollamaUrl }}
            &nbsp;·&nbsp;
            <i class="fas fa-brain me-1"></i>{{ $ollamaModel }}
        </span>
    </div>

    {{-- ── Chat card ────────────────────────────────────────────────────── --}}
    <div class="t-card chat-card">
        <div class="t-card-header">
            <div class="t-card-icon">
                <i class="fas fa-robot"></i>
            </div>
            <div>
                <h5>Probar modelo</h5>
                <p>Interacción directa con el modelo LLM configurado</p>
            </div>
            <button id="btn-clear-chat"
                    title="Limpiar conversación"
                    style="margin-left:auto;background:none;border:none;cursor:pointer;color:var(--muted-color);font-size:.82rem;display:flex;align-items:center;gap:.3rem;padding:.3rem .6rem;border-radius:6px;transition:color .2s;"
                    onmouseover="this.style.color='var(--body-color)'"
                    onmouseout="this.style.color='var(--muted-color)'">
                <i class="fas fa-broom"></i> Limpiar
            </button>
        </div>

        {{-- Historial --}}
        <div class="chat-history" id="chat-history">
            <div class="chat-empty" id="chat-empty">
                <i class="fas fa-comments"></i>
                <p>Escribí un mensaje para comenzar a interactuar con el modelo.<br>
                   <span style="opacity:.7">Podés probar: <em>"Decime si estás funcionando correctamente"</em></span>
                </p>
            </div>
        </div>

        {{-- Input --}}
        <div class="chat-input-area">
            <div style="flex:1;display:flex;flex-direction:column;">
                <textarea id="chat-input"
                          placeholder="Escribí tu mensaje aquí… (Enter para enviar, Shift+Enter para nueva línea)"
                          maxlength="5000"
                          rows="2"
                          aria-label="Mensaje para el modelo"></textarea>
                <div class="char-counter" id="char-counter">0 / 5000</div>
            </div>
            <button id="btn-send" class="btn-send" disabled title="Enviar mensaje">
                <i class="fas fa-paper-plane"></i>
                <span>Enviar</span>
            </button>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
'use strict';

/* ── Config ── */
const MAX_CHARS = 5000;
const SEND_URL  = '{{ route("ollama.send") }}';
const PING_URL  = '{{ route("ollama.send") }}'; // ping rápido al cargar
const USER_INIT = '{{ strtoupper(substr(auth()->user()->name ?? "U", 0, 1)) }}';

/* ── DOM ── */
const chatHistory  = document.getElementById('chat-history');
const chatEmpty    = document.getElementById('chat-empty');
const chatInput    = document.getElementById('chat-input');
const btnSend      = document.getElementById('btn-send');
const charCounter  = document.getElementById('char-counter');
const statusDot    = document.getElementById('status-dot');
const statusText   = document.getElementById('status-text');
const btnClearChat = document.getElementById('btn-clear-chat');

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

/* ── Char counter ── */
chatInput.addEventListener('input', () => {
    const len = chatInput.value.length;
    charCounter.textContent = `${len} / ${MAX_CHARS}`;
    charCounter.className = 'char-counter' + (len > 4500 ? ' danger' : len > 4000 ? ' warn' : '');
    btnSend.disabled = len === 0 || len > MAX_CHARS;
});

/* ── Enter to send ── */
chatInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (!btnSend.disabled) sendMessage();
    }
});

/* ── Auto-resize textarea ── */
chatInput.addEventListener('input', () => {
    chatInput.style.height = 'auto';
    chatInput.style.height = Math.min(chatInput.scrollHeight, 140) + 'px';
});

/* ── Send button ── */
btnSend.addEventListener('click', sendMessage);

/* ── Clear chat ── */
btnClearChat.addEventListener('click', () => {
    // Remove all bubble rows
    chatHistory.querySelectorAll('.bubble-row').forEach(el => el.remove());
    chatEmpty.style.display = '';
});

/* ── Append bubble ── */
function appendBubble(role, text, isError = false) {
    chatEmpty.style.display = 'none';

    const isUser = role === 'user';
    const row = document.createElement('div');
    row.className = `bubble-row ${isUser ? 'user' : 'model'}${isError ? ' error' : ''}`;

    const avatar = document.createElement('div');
    avatar.className = 'bubble-avatar';
    avatar.textContent = isUser ? USER_INIT : '🤖';

    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    bubble.textContent = text; // plain text — seguro contra XSS

    row.appendChild(avatar);
    row.appendChild(bubble);
    chatHistory.appendChild(row);
    scrollBottom();
    return row;
}

/* ── Typing indicator ── */
function showTyping() {
    chatEmpty.style.display = 'none';
    const row = document.createElement('div');
    row.className = 'bubble-row model';
    row.id = 'typing-indicator';

    const avatar = document.createElement('div');
    avatar.className = 'bubble-avatar';
    avatar.textContent = '🤖';

    const bubble = document.createElement('div');
    bubble.className = 'bubble typing-dots';
    bubble.innerHTML = '<span></span><span></span><span></span>';

    row.appendChild(avatar);
    row.appendChild(bubble);
    chatHistory.appendChild(row);
    scrollBottom();
}

function hideTyping() {
    document.getElementById('typing-indicator')?.remove();
}

/* ── Scroll ── */
function scrollBottom() {
    chatHistory.scrollTo({ top: chatHistory.scrollHeight, behavior: 'smooth' });
}

/* ── Send message ── */
async function sendMessage() {
    const text = chatInput.value.trim();
    if (!text || text.length > MAX_CHARS) return;

    // Reset input
    chatInput.value = '';
    chatInput.style.height = 'auto';
    charCounter.textContent = `0 / ${MAX_CHARS}`;
    charCounter.className = 'char-counter';
    btnSend.disabled = true;

    appendBubble('user', text);
    showTyping();
    setStatus('loading', 'Procesando…');

    try {
        const res = await fetch(SEND_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ message: text }),
        });

        const data = await res.json();
        hideTyping();

        if (!res.ok || !data.success) {
            const errMsg = data.error ?? `Error del servidor (HTTP ${res.status}).`;
            appendBubble('model', errMsg, true);
            setStatus('error', 'Error al conectar con el modelo.');
        } else {
            appendBubble('model', data.response);
            setStatus('online', `Modelo ${data.model ?? '{{ $ollamaModel }}'} activo`);
        }
    } catch (err) {
        hideTyping();
        appendBubble('model', 'No se pudo conectar con el servidor. Verificá tu red.', true);
        setStatus('error', 'Sin conexión con el modelo.');
    }
}

/* ── Status bar ── */
function setStatus(state, text) {
    statusDot.className = 'model-status-dot ' + state;
    statusText.textContent = text;
}

/* ── Ping al cargar — verificar si el modelo responde ── */
(async function ping() {
    setStatus('loading', 'Verificando conexión con el modelo…');
    try {
        const res = await fetch(SEND_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ message: 'Decime si estás funcionando correctamente' }),
        });
        const data = await res.json();
        if (res.ok && data.success) {
            setStatus('online', `Modelo ${data.model ?? '{{ $ollamaModel }}'} activo`);
            // Mostrar la respuesta del ping como primer mensaje del modelo
            appendBubble('model', '✅ ' + data.response);
        } else {
            setStatus('error', data.error ?? 'No se pudo conectar con el modelo.');
        }
    } catch {
        setStatus('error', 'No se pudo verificar la conexión con el modelo.');
    }
})();

})();
</script>
@endpush
