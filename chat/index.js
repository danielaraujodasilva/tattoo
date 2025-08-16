const venom = require('venom-bot');
const { responderOllama } = require('./llama');

let chatbotAtivo = false;

venom.create({
    session: 'default',
    multidevice: true,
    headless: 'new', // Evita deprecation warning
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
    client.onMessage(async message => {
        const texto = message.body.toLowerCase();
        const usuario = message.from;

        console.log(`Mensagem recebida de ${usuario}: ${message.body}`);

        // Ativa chatbot
        if (texto === 'batatadoce') {
            chatbotAtivo = true;
            console.log(`Chatbot ativado por ${usuario}`);
            return client.sendText(usuario, 'Chatbot ativado! Agora posso responder suas mensagens.');
        }

        // Se chatbot não ativo, ignora
        if (!chatbotAtivo) {
            console.log('Chatbot não ativo. Ignorando mensagem.');
            return;
        }

        // Resposta via Ollama
        try {
            console.log(`Chamando Ollama para ${usuario}...`);
            const resposta = await responderOllama(usuario, message.body);
            await client.sendText(usuario, resposta);
            console.log(`Resposta enviada para ${usuario}`);
        } catch (err) {
            console.error(`Erro ao chamar Ollama: ${err.message}`);
            await client.sendText(usuario, 'Ocorreu um erro ao processar sua mensagem.');
        }
    });
}
