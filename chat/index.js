const venom = require('venom-bot');
const { responderLlama } = require('./llama');

let chatbotsAtivos = {};

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
  .catch(err => console.log('Venom: Erro na criação da sessão', err));

function start(client) {
  client.onMessage(async (message) => {
    const numero = message.from;
    const texto = message.body.toLowerCase();

    console.log(`Venom: Mensagem recebida de ${numero}: ${texto}`);

    if (texto === 'chatbot') {
      chatbotsAtivos[numero] = true;
      console.log(`Venom: Chatbot ativado para ${numero}`);
      return client.sendText(numero, 'Chatbot ativado! Agora posso responder suas mensagens.');
    }

    if (!chatbotsAtivos[numero]) {
      console.log(`Venom: Chatbot não está ativo para ${numero}`);
      return;
    }

    try {
      console.log(`Venom: Enviando mensagem para LLaMA: ${texto}`);
      const resposta = await responderLlama(numero, message.body);
      console.log(`Venom: Resposta recebida da LLaMA: ${resposta}`);
      await client.sendText(numero, resposta);
    } catch (err) {
      console.error('Venom: Erro ao chamar o LLaMA', err);
      client.sendText(numero, 'Ocorreu um erro ao processar sua mensagem.');
    }
  });
}
