const { spawn } = require('child_process');
const path = require('path');

// Spawn do Python
const python = spawn('python', [path.join(__dirname, 'llama_local.py')], {
  stdio: ['pipe', 'pipe', 'pipe']
});

let buffer = '';
let pendingResolve = null;

python.stdout.on('data', (data) => {
  buffer += data.toString();
  if (buffer.includes('\n')) {
    const linhas = buffer.split('\n');
    linhas.slice(0, -1).forEach((linha) => {
      if (linha.trim() && pendingResolve) {
        pendingResolve(linha.trim());
        pendingResolve = null;
      }
    });
    buffer = linhas[linhas.length - 1];
  }
});

python.stderr.on('data', (data) => {
  console.error('Erro LLaMA:', data.toString());
});

python.on('close', (code) => {
  console.log(`Python terminou com código ${code}`);
});

// Função para enviar mensagem ao Python
function responderLlama(usuario, mensagem) {
  return new Promise((resolve, reject) => {
    pendingResolve = resolve;
    python.stdin.write(`${usuario}||${mensagem}\n`);
    setTimeout(() => {
      if (pendingResolve) {
        pendingResolve('Erro: timeout na resposta da IA');
        pendingResolve = null;
      }
    }, 20000); // 20s timeout
  });
}

module.exports = { responderLlama };
