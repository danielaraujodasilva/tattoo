import { create } from "venom-bot";
import express from "express";
import dotenv from "dotenv";
import fs from "fs";
import path from "path";
import axios from "axios";
import { spawn } from "child_process";
import ffmpegPath from "ffmpeg-static";

dotenv.config();

const app = express();
const port = process.env.PORT || 3010;
const OLLAMA_HOST = process.env.OLLAMA_HOST || "http://127.0.0.1:11434";
const OLLAMA_MODEL = process.env.OLLAMA_MODEL || "nous-hermes2";

if (!fs.existsSync("./audios")) fs.mkdirSync("./audios");

function convertToWav(input, output) {
  return new Promise((resolve, reject) => {
    const ffmpeg = spawn(ffmpegPath, [
      "-y",
      "-i",
      input,
      "-ar",
      "16000",
      "-ac",
      "1",
      "-c:a",
      "pcm_s16le",
      output,
    ]);

    ffmpeg.stderr.on("data", () => {});

    ffmpeg.on("close", (code) => {
      if (code === 0) resolve(output);
      else reject(new Error(`ffmpeg error, code ${code}`));
    });
  });
}

async function transcreverAudioComOllama(wavPath) {
  const audioData = fs.readFileSync(wavPath);
  const base64Audio = audioData.toString("base64");

  const response = await axios.post(
    `${OLLAMA_HOST}/api/generate`,
    {
      model: "whisper",
      prompt: "",
      input_audio: base64Audio,
    },
    {
      timeout: 120000,
    }
  );
  return response.data.response.trim();
}

async function perguntarIaLocal(texto) {
  const response = await axios.post(
    `${OLLAMA_HOST}/api/generate`,
    {
      model: OLLAMA_MODEL,
      prompt: texto,
      stream: false,
    },
    { timeout: 120000 }
  );
  return response.data.response.trim();
}

async function enviarVoice(client, to, filePath) {
  return client.sendFile(to, filePath, path.basename(filePath), "", {
    sendAudioAsVoice: true,
  });
}

let clientVenom;

async function iniciarVenom() {
  clientVenom = await create({
    session: "chatbot-session",
    multidevice: true,
    puppeteerOptions: {
      executablePath: "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe",
      headless: false, // Opção 1: navegador visível para debugging
      // Removi as flags --no-sandbox e --disable-setuid-sandbox (Opção 2)
    },
  });

  clientVenom.onMessage(async (message) => {
    try {
      if (
        message.isMedia &&
        (message.type === "ptt" || message.mimetype?.includes("ogg"))
      ) {
        const buffer = await clientVenom.decryptFile(message);
        const oggPath = `./audios/${message.id}.ogg`;
        fs.writeFileSync(oggPath, buffer);

        const wavPath = `./audios/${message.id}.wav`;
        await convertToWav(oggPath, wavPath);

        const textoTranscrito = await transcreverAudioComOllama(wavPath);
        console.log(`Audio transcrito: ${textoTranscrito}`);

        const respostaTexto = await perguntarIaLocal(textoTranscrito);
        console.log(`Resposta IA: ${respostaTexto}`);

        const ttsUrl = `https://translate.google.com/translate_tts?ie=UTF-8&q=${encodeURIComponent(
          respostaTexto
        )}&tl=pt-BR&client=tw-ob`;

        const audioRes = await axios.get(ttsUrl, { responseType: "arraybuffer" });
        const respostaAudioPath = `./audios/resposta_${Date.now()}.mp3`;
        fs.writeFileSync(respostaAudioPath, audioRes.data);

        await enviarVoice(clientVenom, message.from, respostaAudioPath);

        fs.unlinkSync(oggPath);
        fs.unlinkSync(wavPath);
        fs.unlinkSync(respostaAudioPath);
      } else if (message.type === "chat") {
        const texto = message.body;
        const resposta = await perguntarIaLocal(texto);
        await clientVenom.sendText(message.from, resposta);
      }
    } catch (error) {
      console.error("Erro processando mensagem:", error);
    }
  });

  console.log("🤖 Venom inicializado. Escaneie o QR Code para conectar.");
}

app.get("/", (req, res) => {
  res.send("Chatbot Daniel - Rodando!");
});

app.listen(port, () => {
  console.log(`🌐 Servidor rodando na porta ${port}`);
});

iniciarVenom().catch((e) => {
  console.error("Erro iniciando Venom:", e);
});
