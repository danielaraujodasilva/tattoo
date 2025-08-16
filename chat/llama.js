const { spawn } = require('child_process');

// Inicializa o Python apenas uma vez
const python = spawn('python', ['chat/llama_local.py'], { stdio: ['pipe', 'pipe', 'pipe'] });

function responderLlama(mensagem) {
  return new Promise((resolve, reject) => {
    const onData = (data) => {
      python.stdout.off('data', onData); // Remove listener para evitar múltiplos eventos
      resolve(data.toString().trim());
    };

    python.stdout.on('data', onData);

    python.stdin.write(mensagem + '\n'); // Envia mensagem para o Python
  });
}

// Exibe erros do Python no console
python.stderr.on('data', (data) => {
  console.error('Erro LLaMA:', data.toString());
});

python.on('close', (code) => {
  console.log(`Python terminou com código ${code}`);
});

module.exports = { responderLlama };
