import express from "express";
import multer from "multer";
import fs from "fs";
import path from "path";
import dotenv from "dotenv";
import axios from "axios";
import { fileURLToPath } from "url";

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const port = process.env.PORT || 3010;

// Configuração do Multer para salvar áudios
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, path.join(__dirname, "audios"));
  },
  filename: (req, file, cb) => {
    cb(null, Date.now() + path.extname(file.originalname));
  },
});
const upload = multer({ storage });

// Endpoint para receber áudio e processar
app.post("/audio", upload.single("audio"), async (req, res) => {
  try {
    const audioPath = req.file.path;

    // 1. Transcreve usando o Whisper local do Ollama
    const transcriptRes = await axios.post(
      `${process.env.OLLAMA_HOST}/api/generate`,
      {
        model: "whisper",
        prompt: "",
        input_audio: fs.readFileSync(audioPath).toString("base64"),
      }
    );

    const transcribedText = transcriptRes.data.response.trim();
    console.log("Transcrição:", transcribedText);

    // 2. Envia o texto transcrito para o Nous-Hermes 2
    const chatRes = await axios.post(
      `${process.env.OLLAMA_HOST}/api/generate`,
      {
        model: process.env.OLLAMA_MODEL,
        prompt: transcribedText,
        stream: false,
      }
    );

    const resposta = chatRes.data.response.trim();
    console.log("Resposta IA:", resposta);

    // Apaga o áudio após processar
    fs.unlinkSync(audioPath);

    res.json({ pergunta: transcribedText, resposta });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: "Erro ao processar o áudio" });
  }
});

app.listen(port, () => {
  console.log(`Servidor rodando na porta ${port}`);
});
