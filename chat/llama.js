const { spawn } = require('child_process');
const path = require('path');

// Caminho para o script Python
const llamaScript = path.join(__dirname, 'llama_local.py');

// Cria o processo Python apenas uma vez
const llamaProcess = spawn('python', [llamaScript]);

llamaProcess.stderr.on('data', (data) => {
  console.error(`Python stderr: ${data.toString()}`);
});

// Função para enviar mensagem e receber resposta
function responderLlama(mensagem) {
  return new Promise((resolve) => {
    const onData = (data) => {
      resolve(data.toString().trim());
      llamaProcess.stdout.off('data', onData); // remove listener
    };
    llamaProcess.stdout.on('data', onData);
    llamaProcess.stdin.write(mensagem + '\n');
  });
}

module.exports = { responderLlama };
