const venom = require('venom-bot');

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
    if (message.body) {
      client.sendText(message.from, 'Olá! Recebi sua mensagem.');
    }
  });
}
