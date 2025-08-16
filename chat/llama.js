const { spawn } = require('child_process');
const path = require('path');

const python = spawn('python', [path.join(__dirname, 'llama_local.py')], {
  stdio: ['pipe', 'pipe', 'pipe']
});

let buffer = '';
let pendingResolve = null;

python.stdout.on('data', (data) => {
  console.log('Node: stdout do Python:', data.toString());
  buffer += data.toString();
  if (buffer.includes('\n')) {
    const linhas = buffer.split('\n');
    linhas.slice(0, -1).forEach((linha) => {
      if (linha.trim() && pendingResolve) {
        console.log('Node: Resolvendo promise com linha:', linha.trim());
        pendingResolve(linha.trim());
        pendingResolve = null;
      }
    });
    buffer = linhas[linhas.length - 1];
  }
});

python.stderr.on('data', (data) => {
  console.error('Node: stderr do Python:', data.toString());
});

python.on('close', (code) => {
  console.log(`Node: Python terminou com código ${code}`);
});

function responderLlama(usuario, mensagem) {
  return new Promise((resolve, reject) => {
    console.log(`Node: Enviando para Python -> ${usuario}||${mensagem}`);
    pendingResolve = resolve;
    python.stdin.write(`${usuario}||${mensagem}\n`);
    setTimeout(() => {
      if (pendingResolve) {
        console.error('Node: Timeout de 20s sem resposta da IA');
        pendingResolve('Erro: timeout na resposta da IA');
        pendingResolve = null;
      }
    }, 20000);
  });
}

module.exports = { responderLlama };
