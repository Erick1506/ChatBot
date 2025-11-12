<div id="chatWidget">
    {{-- Chat embebido: sustituye al botón flotante/modal --}}
    <div id="chatWidgetEmbedded" class="chat-embedded" role="region" aria-label="Asistente SENA">
        <div class="chat-header">Asistente SENA</div>


        <div class="chat-body" id="chatBody">
            <div class="quick-opts" id="quickOpts">
                <div class="opt-btn" data-opt="Generar Certificado">Generar Certificado</div>
                <div class="opt-btn" data-opt="Requisitos">Requisitos</div>
            </div>


            <div id="messagesArea">
                <div class="bot-msg">Hola 👋, soy el asistente virtual del SENA. ¿En qué puedo ayudarte?</div>
            </div>
        </div>


        <div class="chat-footer">
            <input id="chatInput" type="text" class="form-control" placeholder="Escribe tu mensaje..."
                aria-label="Escribe tu mensaje" />
            <button id="sendChatBtn" class="btn btn-success" type="button">Enviar</button>
        </div>
    </div>
    