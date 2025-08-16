const venom = require('venom-bot');

let chatbotAtivo = false; // Flag para ativar/desativar o bot

venom
  .create({
    session: 'default',
    multidevice: true,
    headless: true, // mantém headless
    useChrome: true,
    chromiumArgs: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage'
    ]
  })
  .then(client => start(client))
  .catch(err => console.log(err));

function start(client) {
  client.onMessage(message => {
    const texto = message.body.toLowerCase();

    // Ativa o chatbot apenas com a palavra-chave
    if (texto === 'batatadoce') {
      chatbotAtivo = true;
      return client.sendText(message.from, 'Chatbot ativado! Agora posso responder suas mensagens.');
    }

    // Se não estiver ativo, não responde
    if (!chatbotAtivo) return;

    // Resposta padrão depois de ativar
    client.sendText(message.from, 'Olá! Recebi sua mensagem.');
  });
}
