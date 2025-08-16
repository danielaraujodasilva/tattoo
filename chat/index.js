const venom = require('venom-bot');
const { responderLlama } = require('./llama');

let chatbotAtivo = false;

venom
  .create({
    session: 'default',
    multidevice: true,
    headless: 'new', // Atualizado para a nova forma
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
  client.onMessage(async (message) => {
    const texto = message.body.toLowerCase();

    if (texto === 'batatadoce') {
      chatbotAtivo = true;
      return client.sendText(message.from, 'Chatbot ativado! Agora posso responder suas mensagens.');
    }

    if (!chatbotAtivo) return;

    try {
      const resposta = await responderLlama(message.body);
      client.sendText(message.from, resposta);
    } catch (err) {
      console.error(err);
      client.sendText(message.from, 'Ocorreu um erro ao processar sua mensagem.');
    }
  });
}
