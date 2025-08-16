const venom = require('venom-bot');
const { responderLlama } = require('./llama');

let chatbotsAtivos = {}; // Histórico de ativação por número

venom
  .create({
    session: 'default',
    multidevice: true,
    headless: 'new',
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
    const numero = message.from;
    const texto = message.body.toLowerCase();

    // Ativa o chatbot apenas com a palavra "chatbot"
    if (texto === 'chatbot') {
      chatbotsAtivos[numero] = true;
      return client.sendText(numero, 'Chatbot ativado! Agora posso responder suas mensagens.');
    }

    if (!chatbotsAtivos[numero]) return; // Se não estiver ativo, não responde

    try {
      const resposta = await responderLlama(numero, message.body);
      await client.sendText(numero, resposta);
    } catch (err) {
      console.error(err);
      client.sendText(numero, 'Ocorreu um erro ao processar sua mensagem.');
    }
  });
}
