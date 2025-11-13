// Chatbot FIC – generación automática de certificados
document.addEventListener('DOMContentLoaded', () => {
  const sendBtn = document.getElementById('sendChatBtn');
  const chatInput = document.getElementById('chatInput');
  const messages = document.getElementById('messagesArea');
  const quickOpts = document.getElementById('quickOpts');

  const state = {
    flow: null,
    ficType: null,
    awaiting: null,
    buffer: {},
    locked: false // 🔒 nuevo: indica si el bot está procesando
  };

  // Configurar axios
  if (window.axios) {
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    if (tokenMeta) {
      window.axios.defaults.headers.common['X-CSRF-TOKEN'] = tokenMeta.getAttribute('content');
    }
    window.axios.defaults.headers.common['Accept'] = 'application/json';
  }

  // === helpers UI ===
  function append(textOrNode, who='bot'){
    let d;
    if (typeof textOrNode === 'string') {
      d = document.createElement('div');
      d.className = who === 'bot' ? 'bot-msg' : 'user-msg';
      d.textContent = textOrNode;
    } else {
      d = document.createElement('div');
      d.className = who === 'bot' ? 'bot-msg' : 'user-msg';
      d.appendChild(textOrNode);
    }
    messages.appendChild(d);
    messages.scrollTop = messages.scrollHeight;
  }

  function botReply(text, delay=400){
    state.locked = true; // 🔒 bloquea mientras el bot responde
    sendBtn.disabled = true;
    chatInput.disabled = true;

    setTimeout(() => {
      append(text, 'bot');
      // 🔓 desbloquear después de responder
      state.locked = false;
      sendBtn.disabled = false;
      chatInput.disabled = false;
      chatInput.focus();
    }, delay);
  }

  // === Opciones rápidas ===
  quickOpts.addEventListener('click', (e) => {
    if (state.locked) return; // 🚫 evitar clics durante espera

    const btn = e.target.closest('.opt-btn');
    if(!btn) return;
    const val = btn.getAttribute('data-opt');
    append(val,'user');

    if(val === 'Generar Certificado'){
      startGenerateFlow();
    } else if(val === 'Requisitos'){
      botReply('Para generar certificados FIC se requiere:');
      botReply('• NIT o Cédula del empresario\n• Tipo de certificado (ticket, NIT o vigencia)\n• En el caso de vigencia: año a certificar (máx. 15 años atrás).');
    } else if(val === 'Consulta de Certificado' || val === 'Consultar Certificado'){
      state.flow = 'consultar';
      botReply('¿Qué deseas consultar? Ingresa NIT, número de ticket o año de vigencia.');
      state.awaiting = 'query';
    } else {
      botReply('Enlace de contacto: soporte@sena.edu.co');
    }
  });

  // === Entrada del usuario ===
  sendBtn.addEventListener('click', () => {
    if (state.locked) return; // 🚫 no permitir envío si está bloqueado

    const text = chatInput.value.trim();
    if(!text) return;
    append(text,'user');
    chatInput.value = '';
    handleUserText(text);
  });

  chatInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      sendBtn.click();
    }
  });

  // === Inicia el flujo de generación ===
  function startGenerateFlow(){
    state.flow = 'generar';
    state.ficType = null;
    state.awaiting = 'chooseType';
    state.buffer = {};
    botReply('¿Qué tipo de certificado FIC desea generar?\nResponda: "TICKET", "NIT" o "VIGENCIA".');
  }

  // === Procesa los mensajes del usuario ===
  function handleUserText(text){
    const t = text.toLowerCase();

    if(state.flow === 'generar'){
      if(state.awaiting === 'chooseType'){
        if(t.includes('ticket')) {
          state.ficType = 'ticket';
          state.awaiting = 'nit';
          botReply('Seleccionaste **Certificado por TICKET**.\nPor favor ingresa el NIT de la empresa.');
        } else if(t.includes('nit')) {
          state.ficType = 'nit';
          state.awaiting = 'nit';
          botReply('Seleccionaste **Certificado por NIT**.\nIngresa el NIT o la cédula del empresario.');
        } else if(t.includes('vigencia')) {
          state.ficType = 'vigencia';
          state.awaiting = 'nit';
          botReply('Seleccionaste **Certificado por VIGENCIA**.\nPrimero ingresa el NIT o la cédula del empresario.');
        } else {
          botReply('Por favor responde: "ticket", "nit" o "vigencia".');
        }
        return;
      }

      if(state.awaiting === 'nit'){
        state.buffer.nit = text.trim();
        if(state.ficType === 'ticket'){
          state.awaiting = 'ticket';
          botReply('Ahora ingresa el número de ticket.');
        } else if(state.ficType === 'nit'){
          generateCertificate('nit',{nit: state.buffer.nit});
        } else if(state.ficType === 'vigencia'){
          state.awaiting = 'year';
          botReply('Ingresa el año de la vigencia (ejemplo: 2025). Solo se permiten 15 años atrás desde el actual.');
        }
        return;
      }

      if(state.awaiting === 'ticket'){
        state.buffer.ticket_number = text.trim();
        generateCertificate('ticket',{nit: state.buffer.nit, ticket_number: state.buffer.ticket_number});
        return;
      }

      if(state.awaiting === 'year'){
        const year = parseInt(text.trim(),10);
        if(isNaN(year)){
          botReply('El año debe ser un número válido, por ejemplo: 2025.');
          return;
        }
        const current = new Date().getFullYear();
        if(year > current || year < (current - 15)){
          botReply(`Año fuera de rango. Solo se permiten vigencias entre ${current-15} y ${current}.`);
          return;
        }
        state.buffer.year = year;
        generateCertificate('vigencia',{nit: state.buffer.nit, year: state.buffer.year});
        return;
      }
    }

    if(state.flow === 'consultar' && state.awaiting === 'query'){
      state.awaiting = null;
      botReply('Consultando certificado...');
      queryServerAndShow('query',{query: text});
      state.flow = null;
      return;
    }

    if(t.includes('certificado')) {
      botReply('Si deseas generar un certificado, escribe "Generar Certificado".');
      return;
    }

    botReply('No entendí 🤔. Prueba con: "Generar Certificado" o "Consultar Certificado".');
  }

  // === Generación del certificado ===
  async function generateCertificate(type, payload){
    botReply('Generando certificado, por favor espere...');
    state.locked = true;
    sendBtn.disabled = true;
    chatInput.disabled = true;

    try {
      const resp = await axios.post('/api/certificates/generate', {...payload, type});
      const data = resp.data;
      if(data.success){
        botReply('✅ Certificado generado correctamente.');
        if(data.result){
          const pre = document.createElement('pre');
          pre.style.whiteSpace = 'pre-wrap';
          pre.textContent = JSON.stringify(data.result, null, 2);
          append(pre, 'bot');
        }
      } else {
        botReply('❌ Error en la solicitud: ' + (data.message || 'No se pudo generar el certificado.'));
      }
    } catch (err){
      botReply('❌ Error de conexión con el servidor.');
      console.error(err);
    } finally {
      // 🔓 desbloquear al finalizar
      state.locked = false;
      sendBtn.disabled = false;
      chatInput.disabled = false;
      chatInput.focus();
      state.flow = null; state.ficType = null; state.awaiting = null; state.buffer = {};
    }
  }

  // === Consulta genérica ===
  async function queryServerAndShow(type, payload){
    state.locked = true;
    sendBtn.disabled = true;
    chatInput.disabled = true;

    try {
      const resp = await axios.post('/api/certificates/query', {...payload, type});
      const data = resp.data;
      if(data.success){
        botReply(data.message || 'Resultado:');
        const pre = document.createElement('pre');
        pre.style.whiteSpace = 'pre-wrap';
        pre.textContent = JSON.stringify(data.result ?? data, null, 2);
        append(pre, 'bot');
      } else {
        botReply(data.message || 'No se encontró información.');
      }
    } catch (err){
      botReply('Error de conexión con el servidor.');
      console.error(err);
    } finally {
      state.locked = false;
      sendBtn.disabled = false;
      chatInput.disabled = false;
      chatInput.focus();
    }
  }
});
