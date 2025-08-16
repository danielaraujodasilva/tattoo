const { spawn } = require('child_process');

// Histórico por usuário
const historico = {};

// Prompt base da IA
const promptBase = `Você é uma atendente de estúdio de tatuagem. 
Responda sempre de forma educada, prestativa e clara, como se estivesse atendendo um cliente real.`;

/**
 * Função para gerar resposta da IA via Ollama CLI (modo chat)
 * @param {string} usuario - ID do usuário (telefone)
 * @param {string} mensagem - Mensagem recebida
 * @returns {Promise<string>} - Resposta gerada
 */
function responderOllama(usuario, mensagem) {
    return new Promise((resolve, reject) => {
        if (!historico[usuario]) historico[usuario] = [];

        // Adiciona mensagem do usuário ao histórico
        historico[usuario].push({ role: 'user', content: mensagem });

        // Monta prompt completo
        let promptCompleto = promptBase + '\n\n';
        historico[usuario].forEach(msg => {
            promptCompleto += `${msg.role === 'user' ? 'Cliente' : 'Atendente'}: ${msg.content}\n`;
        });
        promptCompleto += 'Atendente:';

        // Spawn do Ollama CLI no modo chat
        const cmd = spawn('ollama', ['chat', 'Llama3.2-3B-Instruct']);

        let resposta = '';
        let erro = '';

        // Envia prompt para stdin do processo
        cmd.stdin.write(promptCompleto + '\n');
        cmd.stdin.end();

        cmd.stdout.on('data', data => {
            resposta += data.toString();
        });

        cmd.stderr.on('data', data => {
            erro += data.toString();
        });

        cmd.on('close', code => {
            if (code !== 0) {
                console.error('Erro LLaMA:', erro);
                return reject(new Error(`Ollama CLI retornou código ${code}`));
            }

            resposta = resposta.trim();

            // Adiciona resposta ao histórico
            historico[usuario].push({ role: 'assistant', content: resposta });

            console.log(`Node: [Ollama] ${usuario} -> ${resposta}`);
            resolve(resposta);
        });
    });
}

module.exports = { responderOllama };
