require("dotenv").config();
const express = require("express");
const axios = require("axios");

const app = express();
app.use(express.json());

const PHONE_NUMBER_ID = process.env.WHATSAPP_PHONE_ID;
const TOKEN = process.env.WHATSAPP_TOKEN;
const VERIFY_TOKEN = process.env.WHATSAPP_VERIFY_TOKEN || "zapcrm123";

// Função para enviar mensagem
async function enviarMensagem(numeroDestino, mensagem) {
  console.log("[LOG] Preparando para enviar mensagem para:", numeroDestino);
  try {
    const response = await axios.post(
      `https://graph.facebook.com/v17.0/${PHONE_NUMBER_ID}/messages`,
      {
        messaging_product: "whatsapp",
        to: numeroDestino,
        type: "text",
        text: { body: mensagem },
      },
      {
        headers: { Authorization: `Bearer ${TOKEN}` },
      }
    );
    console.log("[LOG] Mensagem enviada com sucesso!", response.data);
  } catch (err) {
    console.error("[ERROR] Falha ao enviar mensagem:", err.response?.data || err.message);
  }
}

// Fluxo de conversa
async function fluxoCliente(numeroDestino, mensagemRecebida) {
  console.log("[LOG] Iniciando fluxoCliente para:", numeroDestino, "com mensagem:", mensagemRecebida);

  mensagemRecebida = (mensagemRecebida || "").toLowerCase();

  if (mensagemRecebida.includes("oi") || mensagemRecebida.includes("olá")) {
    await enviarMensagem(numeroDestino, 
      "Olá! 👋 Bem-vindo ao estúdio XYZ Tattoo.\nPosso te ajudar com:\n1️⃣ Agendar uma tattoo\n2️⃣ Consultar preços\n3️⃣ Dúvidas gerais"
    );
  } else if (mensagemRecebida.includes("1") || mensagemRecebida.includes("agendar")) {
    await enviarMensagem(numeroDestino, "Perfeito! Qual data você gostaria de agendar sua tattoo? Por favor, envie no formato DD/MM.");
  } else if (mensagemRecebida.includes("2") || mensagemRecebida.includes("preço")) {
    await enviarMensagem(numeroDestino, "Nossos preços variam de acordo com o tamanho e estilo da tattoo. Pode me enviar uma referência ou tamanho aproximado?");
  } else if (mensagemRecebida.includes("3") || mensagemRecebida.includes("dúvida")) {
    await enviarMensagem(numeroDestino, "Pode me enviar sua dúvida que responderei em detalhes!");
  } else {
    await enviarMensagem(numeroDestino, "Desculpe, não entendi. Por favor, digite 1, 2 ou 3 para escolher uma opção.");
  }
}

// Webhook POST ultra-detalhado
app.post("/webhook", async (req, res) => {
  console.log("[LOG] === RECEBIDO POST NO WEBHOOK ===");
  console.log("[LOG] Body completo:", JSON.stringify(req.body, null, 2));

  try {
    const body = req.body;

    // Detecta mensagens dentro do JSON, mesmo se o formato mudar
    const messages =
      body.entry?.[0]?.changes?.[0]?.value?.messages ||
      body.messages ||
      [];

    console.log("[LOG] Mensagens detectadas:", messages.length);

    if (!messages.length) {
      console.log("[LOG] Nenhuma mensagem válida encontrada");
      return res.sendStatus(200);
    }

    for (const message of messages) {
      const numeroCliente = message.from || message.sender?.id || "desconhecido";
      const textoMensagem = message.text?.body || message.message?.text || "";

      console.log("[LOG] Número do cliente:", numeroCliente);
      console.log("[LOG] Texto da mensagem:", textoMensagem);

      if (textoMensagem) {
        await fluxoCliente(numeroCliente, textoMensagem);
      } else {
        console.log("[LOG] Mensagem sem texto detectada, ignorando...");
      }
    }

    res.sendStatus(200);
  } catch (err) {
    console.error("[ERROR] Erro no webhook:", err);
    res.sendStatus(500);
  }
});

// Webhook GET para verificação
app.get("/webhook", (req, res) => {
  const mode = req.query["hub.mode"];
  const token = req.query["hub.verify_token"];
  const challenge = req.query["hub.challenge"];

  console.log("[LOG] === RECEBIDO GET PARA VERIFICAÇÃO ===");
  console.log("[LOG] Query params:", req.query);

  if (mode && token) {
    if (mode === "subscribe" && token === VERIFY_TOKEN) {
      console.log("[LOG] Webhook verificado com sucesso!");
      return res.status(200).send(challenge);
    } else {
      console.log("[LOG] Verificação falhou");
      return res.sendStatus(403);
    }
  } else {
    console.log("[LOG] GET sem parâmetros de verificação");
    return res.sendStatus(400);
  }
});

// Inicia servidor na porta 3018
app.listen(3018, () => console.log("[LOG] Servidor rodando na porta 3018"));
