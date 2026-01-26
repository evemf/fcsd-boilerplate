(function ($) {
    'use strict';

    // ================== CONFIGURACIÓN (solo cambia estas líneas) ==================
    const GEMINI_API_KEY = 'AIzaSyBHtim8C1xgpKYXVwywyWzEgxR-adMbWOA';        // ← Gratis en https://aistudio.google.com/app/apikey
    const TAVILY_API_KEY  = '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';   // ← Gratis en https://app.tavily.com (1000 búsquedas/mes free)
    const SITE_DOMAIN     = 'https://fcsd.org';             // ← Tu dominio exacto (con https://)
    // =============================================================================

    let conversationHistory = [];

    function addMessage(container, text, from) {
        const $msg = $('<div>').addClass('fcsd-chatbot-message fcsd-chatbot-message-' + from);
        if (from === 'bot') {
            $msg.html(text); // permite HTML (enlaces, negritas, etc.)
        } else {
            $msg.text(text);
        }
        container.append($msg);
        container.scrollTop(container[0].scrollHeight);
    }

    async function getSmartReply(userMessage) {
        // 1. Primero buscamos en tu web con Tavily (solo contenido de tu dominio)
        const tavilyResponse = await fetch('https://api.tavily.com/search', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                api_key: TAVILY_API_KEY,
                query: userMessage,
                search_depth: "advanced",
                include_answer: true,
                include_raw_content: false,
                max_results: 6,
                include_domains: [SITE_DOMAIN.replace('https://', '').replace('http://', '')]
            })
        });

        const searchData = await tavilyResponse.json();
        const contextFromYourSite = searchData.answer || 
            searchData.results.map(r => r.title + ": " + r.content).join("\n");

        // 2. Enviamos al Gemini con el contexto de tu web + historial
        const response = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=${GEMINI_API_KEY}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                contents: [
                    {
                        role: "user",
                        parts: [{ text: `Eres el asistente virtual de la tienda ${SITE_DOMAIN}.
Usa un tono cercano y amigable, con emojis.
SIEMPRE responde en español.
Solo puedes hablar de productos, políticas y contenido que existe en la web.

Contexto real de la tienda (usa esto para responder con precisión):
${contextFromYourSite}

Historial de conversación:
${conversationHistory.slice(-8).map(m => `${m.role}: ${m.text}`).join('\n')}

Pregunta del cliente: ${userMessage}

Responde de forma natural, útil y breve. Si no sabes algo, di que lo consultarás con un humano.` }]
                    }
                ],
                generationConfig: {
                    temperature: 0.7,
                    topK: 40,
                    topP: 0.95,
                    maxOutputTokens: 600,
                },
                safetySettings: [
                    { category: "HARM_CATEGORY_HARASSMENT", threshold: "BLOCK_NONE" },
                    { category: "HARM_CATEGORY_HATE_SPEECH", threshold: "BLOCK_NONE" },
                    { category: "HARM_CATEGORY_SEXUALLY_EXPLICIT", threshold: "BLOCK_NONE" },
                    { category: "HARM_CATEGORY_DANGEROUS_CONTENT", threshold: "BLOCK_NONE" }
                ]
            })
        });

        const data = await response.json();
        const botReply = data.candidates[0].content.parts[0].text;

        // Guardamos en historial
        conversationHistory.push({ role: "user", text: userMessage });
        conversationHistory.push({ role: "model", text: botReply });

        return botReply || "Lo siento, ahora mismo no puedo procesar tu mensaje 😅. ¡Prueba de nuevo en unos segundos!";
    }

    $(function () {
        const $root     = $('.fcsd-chatbot');
        if (!$root.length) return;

        const $toggle   = $root.find('.fcsd-chatbot-toggle');
        const $window   = $root.find('.fcsd-chatbot-window');
        const $messages = $root.find('.fcsd-chatbot-messages');
        const $form     = $root.find('.fcsd-chatbot-form');
        const $input    = $form.find('input[type="text"]');
        const $close    = $root.find('.fcsd-chatbot-close');

        $toggle.on('click', () => {
            $window.toggleClass('is-open');
            if ($window.hasClass('is-open') && !$messages.children().length) {
                addMessage($messages, '¡Hola! 👋<br>Soy tu asistente con IA.<br>Pregúntame lo que quieras sobre productos, envíos, tallas, devoluciones... ¡Sé todo lo que hay en la web! 😊', 'bot');
            }
        });

        $close.on('click', () => $window.removeClass('is-open'));

        $form.on('submit', async function (e) {
            e.preventDefault();
            const text = $input.val().trim();
            if (!text) return;

            addMessage($messages, text, 'user');
            $input.val('').prop('disabled', true);

            // Indicador de "escribiendo"
            const $typing = $('<div class="fcsd-chatbot-message fcsd-chatbot-message-bot"><span class="typing">Pensando<span class="dots">...</span></span></div>');
            $messages.append($typing);

            try {
                const reply = await getSmartReply(text);
                $typing.remove();
                addMessage($messages, reply, 'bot');
            } catch (err) {
                $typing.remove();
                addMessage($messages, 'Ups 😅 Parece que hay un problema de conexión. Inténtalo en unos segundos.', 'bot');
            }

            $input.prop('disabled', false).focus();
        });
    });

// CSS rápido para el puntito de "escribiendo" (puedes ponerlo en tu CSS)
const style = document.createElement('style');
style.textContent = `
.typing .dots { animation: dots 1.5s infinite; }
@keyframes dots {
  0%, 20% { content: '.'; }
  40% { content: '..'; }
  60% { content: '...'; }
  80%, 100% { content: ''; }
}
`;
document.head.appendChild(style);

})(jQuery);