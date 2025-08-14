// chatbot.js
import { create } from 'venom-bot';
import express from 'express';
import dotenv from 'dotenv';
import axios from 'axios';
import fs from 'fs';
import path from 'path';
import { exec, spawn } from 'child_process';
import ffmpegPath from 'ffmpeg-static';
import { pipeline } from 'stream';
import { promisify } from 'util';
import googleTTS from 'google-tts-api';

dotenv.config();
const pipe = promisify(pipeline);

const app = express();
const port = process.env.PORT || 3010;
let client = null;
let iaAtiva = false; // controle para saber se a IA local está rodando

// Ajuste estes caminhos conforme sua máquina:
const WHISPER_CPP_BIN = process.env.WHISPER_CPP_BIN || './whisper.cpp/main'; // executável do whisper.cpp
const WHISPER_MODEL = process.env.WHISPER_MODEL || './models/ggml-small.bin'; // modelo ggml para whisper.cpp
const OPEN_TTS_URL = process.env.OPEN_TTS_URL || 'http://localhost:5500/api/tts'; // se usar OpenTTS (recomendo instalar local)
const IA_LOCAL_ENDPOINT = process.env.IA_LOCAL_ENDPOINT || 'http://localhost:5000/perguntar';

// Prompt que já estávamos usando antes
const promptBase = `
Você é um atendente virtual do Estúdio Daniel Araujo Tattoo.
Seu papel é responder com simpatia e objetividade, ajudando o cliente a marcar, orçar ou tirar dúvidas.
Fale de forma natural, sem parecer robótico, mas mantenha as informações corretas.
Não invente coisas.
`;

// Garante pastas
if (!fs.existsSync('./audios')) fs.mkdirSync('./audios', { recursive: true });

// --- Função para iniciar a IA local quando detectar "batatadoce"
function iniciarIaLocal() {
  if (iaAtiva) return;
  iaAtiva = true;
  console.log('🚀 Iniciando IA local (ia_local.js)...');
  const proc = exec('node ia_local.js', (error, stdout, stderr) => {
    if (error) {
      console.error(`Erro ao iniciar IA local: ${error.message}`);
      iaAtiva = false;
      return;
    }
    console.log('IA local finalizou (stdout):', stdout);
    iaAtiva = false;
  });

  proc.on('exit', (code) => {
    console.log('Processo ia_local.js saiu com código', code);
    iaAtiva = false;
  });
}

// --- Helpers FFmpeg (converte OGG/OPUS para WAV 16k mono) ---
async function convertToWav(inputPath, outputPath) {
  return new Promise((resolve, reject) => {
    const ffmpeg = spawn(ffmpegPath, [
      '-y',
      '-i', inputPath,
      '-ar', '16000',
      '-ac', '1',
      '-c:a', 'pcm_s16le',
      outputPath,
    ]);

    ffmpeg.stderr.on('data', (d) => {
      // console.log('ffmpeg:', d.toString());
    });
    ffmpeg.on('close', (code) => {
      if (code === 0) resolve(outputPath);
      else reject(new Error(`ffmpeg exit code ${code}`));
    });
  });
}

// --- Transcrever com whisper.cpp (assume binary e modelo presentes) ---
async function transcreverComWhisperCpp(wavPath) {
  // Este comando usa o whisper.cpp (main) com argumentos simples: -m modelo -f arquivo.wav -otxt
  // Ajuste os flags conforme a sua build do whisper.cpp.
  return new Promise((resolve, reject) => {
    if (!fs.existsSync(WHISPER_CPP_BIN)) {
      return reject(new Error(`whisper.cpp bin não encontrado em: ${WHISPER_CPP_BIN}`));
    }
    const args = [
      '-m', WHISPER_MODEL,
      '-f', wavPath,
      '-otxt', // gera arquivo .txt ao lado do wav (varia por build)
      '--language', 'pt' // tenta forçar pt (opcional)
    ];

    const proc = spawn(WHISPER_CPP_BIN, args, { stdio: ['ignore', 'pipe', 'pipe'] });
    let stdout = '';
    let stderr = '';
    proc.stdout.on('data', (d) => stdout += d.toString());
    proc.stderr.on('data', (d) => stderr += d.toString());

    proc.on('close', (code) => {
      if (code !== 0) {
        // fallback: tenta extrair texto da saída
        console.error('whisper.cpp stderr:', stderr);
        return reject(new Error(`whisper.cpp exit code ${code}`));
      }
      // Muitas builds do whisper.cpp escrevem <wav>.txt ao lado; tentamos ler
      const txtPathTry = wavPath.replace(/\.[^.]+$/, '.txt');
      if (fs.existsSync(txtPathTry)) {
        const text = fs.readFileSync(txtPathTry, 'utf8').trim();
        if (text) return resolve(text);
      }
      // Se não encontrou arquivo .txt, tenta usar stdout
      const guess = stdout.trim() || stderr.trim();
      resolve(guess || '');
    });
  });
}

// --- Fallback de transcrição: (opcional) você pode implementar uma chamada pra um serviço cloud aqui ---
// Ex.: OpenAI Whisper API, AssemblyAI, etc.

// --- Gerar áudio com OpenTTS (preferido local). Fallback para google-tts-api se OpenTTS não disponível.
async function gerarAudioTTS(texto, outPath) {
  // Tenta OpenTTS (recomendado rodar localmente): muitos setups aceitam GET /api/tts?text=...&voice=alloy
  try {
    // Tentamos uma chamada comum ao OpenTTS (GET), retornando stream WAV/MP3
    const params = new URLSearchParams({ text: texto, voice: 'alloy', format: 'wav' });
    const url = `${OPEN_TTS_URL}?${params.toString()}`;
    const res = await axios.get(url, { responseType: 'stream', timeout: 120000 });
    const writer = fs.createWriteStream(outPath);
    await pipe(res.data, writer);
    return outPath;
  } catch (err) {
    console.warn('OpenTTS não respondeu ou deu erro, usando fallback google-tts-api. Erro:', err.message);
    // Fallback: google-tts-api (gera MP3 via URL). Baixa e converte para wav com ffmpeg.
    try {
      const url = googleTTS.getAudioUrl(texto, { lang: 'pt-BR', slow: false, host: 'https://translate.google.com' });
      const res = await axios.get(url, { responseType: 'stream' });
      const mp3Tmp = outPath.replace(/\.[^.]+$/, '.mp3');
      const writer = fs.createWriteStream(mp3Tmp);
      await pipe(res.data, writer);
      // Converter mp3 -> wav 16k mono
      await convertToWav(mp3Tmp, outPath);
      fs.unlinkSync(mp3Tmp);
      return outPath;
    } catch (err2) {
      console.error('Fallback TTS falhou:', err2.message);
      throw err2;
    }
  }
}

// --- Enviar voice pelo venom-bot (envia arquivo local) ---
async function enviarVoice(to, filePath) {
  // Venom aceita paths locais
  return client.sendFile(to, filePath, path.basename(filePath), '', { sendAudioAsVoice: true });
}

// --- Função principal: processar áudio recebido (ptt) ---
async function processarAudio(message) {
  try {
    // Salva arquivo criptografado do WhatsApp
    const buffer = await client.decryptFile(message);
    const oggPath = path.join('./audios', `${message.id}.ogg`);
    fs.writeFileSync(oggPath, buffer);

    // Converte para WAV 16k mono
    const wavPath = path.join('./audios', `${message.id}.wav`);
    await convertToWav(oggPath, wavPath);

    // Transcrever
    let textoTranscrito = '';
    try {
      textoTranscrito = await transcreverComWhisperCpp(wavPath);
    } catch (err) {
      console.error('Erro na transcrição local:', err.message);
      textoTranscrito = '';
    }

    if (!textoTranscrito) {
      // Se não transcreveu, avisa e encerra
      await client.sendText(message.from, 'Desculpa, não consegui transcrever seu áudio. Pode mandar novamente?');
      return;
    }

    // Se detectar batatadoce, inicia IA local
    if (textoTranscrito.toLowerCase().includes('batatadoce')) iniciarIaLocal();

    // Pergunta para IA local
    const respostaTexto = await gerarRespostaIa(textoTranscrito);

    // Gera TTS (WAV)
    const respostaAudioPath = path.join('./audios', `resposta_${Date.now()}.wav`);
    await gerarAudioTTS(respostaTexto, respostaAudioPath);

    // Envia audio como voice
    await enviarVoice(message.from, respostaAudioPath);

  } catch (err) {
    console.error('Erro processando áudio:', err);
    try { await client.sendText(message.from, 'Ocorreu um erro ao processar seu áudio.'); } catch(e){}
  }
}

// --- Processar mensagem de texto ---
async function processarTexto(message) {
  try {
    const textoRecebido = message.body || '';
    if (textoRecebido.toLowerCase().includes('batatadoce')) {
      iniciarIaLocal();
    }
    const resposta = await gerarRespostaIa(textoRecebido);
    await client.sendText(message.from, resposta);
  } catch (err) {
    console.error('Erro processando texto:', err);
  }
}

// --- Chama sua IA local (assume endpoint REST) ---
async function gerarRespostaIa(texto) {
  try {
    const res = await axios.post(IA_LOCAL_ENDPOINT, {
      prompt: `${promptBase}\nUsuário: ${texto}\nAtendente:`
    }, { timeout: 120000 });
    if (res.data && (res.data.resposta || res.data.answer || res.data.text)) {
      return res.data.resposta || res.data.answer || res.data.text;
    }
    // Se recebeu raw
    if (typeof res.data === 'string') return res.data;
    return 'Desculpe, não consegui formular uma resposta agora.';
  } catch (err) {
    console.error('Erro na chamada à IA local:', err.message);
    return 'Tive um problema para processar sua mensagem.';
  }
}

// --- Iniciar Venom ---
create({
  session: 'chatbot',
  multidevice: true
})
.then((venomClient) => {
  client = venomClient;
  console.log('🤖 Chatbot iniciado com sucesso!');

  client.onMessage(async (message) => {
    try {
      // Se for áudio de voz (ptt) ou voice note
      const isVoice = message.isMedia && (message.type === 'ptt' || message.mimetype && message.mimetype.includes('ogg'));
      if (isVoice) {
        await processarAudio(message);
      } else if (message.type === 'chat' || message.isGroupMsg === false) {
        // texto
        await processarTexto(message);
      }
    } catch (err) {
      console.error('Erro no processamento da mensagem:', err);
    }
  });
})
.catch((err) => console.error('Erro ao iniciar Venom:', err));

// Servidor Express
app.get('/', (req, res) => {
  res.send('Chatbot do Daniel rodando 🚀');
});

app.listen(port, () => {
  console.log(`🌐 Servidor rodando na porta ${port}`);
});
