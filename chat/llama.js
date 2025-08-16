const { spawn } = require('child_process');

/**
 * Função que envia uma mensagem para o LLaMA local e retorna a resposta.
 * O Node.js chama o script Python 'llama_local.py'.
 */
function responderLlama(mensagem) {
  return new Promise((resolve, reject) => {
    const python = spawn('python', ['llama_local.py', mensagem]);

    let resposta = '';
    let erro = '';

    python.stdout.on('data', (data) => {
      resposta += data.toString();
    });

    python.stderr.on('data', (data) => {
      erro += data.toString();
    });

    python.on('close', (code) => {
      if (code === 0) {
        resolve(resposta.trim());
      } else {
        console.error('Erro LLaMA:', erro);
        reject(new Error('Erro ao chamar o LLaMA'));
      }
    });
  });
}

module.exports = { responderLlama };
