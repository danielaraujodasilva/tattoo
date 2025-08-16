const { spawn } = require('child_process');

const python = spawn('python', ['chat/llama_local.py'], {
  stdio: ['pipe', 'pipe', 'pipe']
});

// Buffer para garantir que cada resposta seja completa
let buffer = '';

python.stdout.on('data', (data) => {
  buffer += data.toString();
  if (buffer.includes('\n')) {
    const linhas = buffer.split('\n');
    linhas.slice(0, -1).forEach((linha) => {
      if (linha.trim()) pendingResolve(linha.trim());
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

let pendingResolve = null;

function responderLlama(mensagem) {
  return new Promise((resolve, reject) => {
    pendingResolve = resolve;
    python.stdin.write(mensagem + '\n');
    // timeout para não travar caso algo dê errado
    setTimeout(() => {
      if (pendingResolve) {
        pendingResolve('Erro: timeout ao receber resposta da IA');
        pendingResolve = null;
      }
    }, 20000); // 20 segundos
  });
}

module.exports = { responderLlama };
