require("dotenv").config();
const express = require("express");
const axios = require("axios");

const app = express();
app.use(express.json({ limit: "1mb" })); // aumenta limite caso venha JSON grande

const PHONE_NUMBER_ID = process.env.WHATSAPP_PHONE_ID;
const TOKEN = process.env.WHATSAPP_TOKEN;
const VERIFY_TOKEN = process.env.WHATSAPP_VERIFY_TOKEN;

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

  mensagemRecebida = mensagemRecebida.toLowerCase();

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

// Webhook POST ultra-verboso
app.post("/webhook", async (req, res) => {
  console.log("[LOG] === POST recebido no webhook ===");
  console.log("[LOG] Body completo recebido:", JSON.stringify(req.body, null, 2));

  try {
    const body = req.body;

    // Confere se é do WhatsApp Business
    if (body.object !== "whatsapp_business_account") {
      console.log("[LOG] Objeto não é WhatsApp Business. Ignorando.");
      return res.sendStatus(200);
    }

    // Mostra todos os entrys
    const entries = body.entry || [];
    console.log(`[LOG] Total de entrys: ${entries.length}`);

    for (const [i, entry] of entries.entries()) {
      console.log(`[LOG] Entry #${i}:`, JSON.stringify(entry, null, 2));

      const changes = entry.changes || [];
      console.log(`[LOG] Total de changes no entry #${i}: ${changes.length}`);

      for (const [j, change] of changes.entries()) {
        console.log(`[LOG] Change #${j}:`, JSON.stringify(change, null, 2));

        const messages = change.value?.messages || [];
        console.log(`[LOG] Total de mensagens no change #${j}: ${messages.length}`);

        for (const [k, message] of messages.entries()) {
          console.log(`[LOG] Mensagem #${k}:`, JSON.stringify(message, null, 2));

          const numeroCliente = message.from || "desconhecido";
          const textoMensagem = message.text?.body || "";

          console.log("[LOG] Número do cliente:", numeroCliente);
          console.log("[LOG] Texto da mensagem:", textoMensagem);

          if (textoMensagem) {
            await fluxoCliente(numeroCliente, textoMensagem);
          } else {
            console.log("[LOG] Mensagem sem texto. Ignorando.");
          }
        }
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
      res.status(200).send(challenge);
    } else {
      console.log("[LOG] Verificação falhou");
      res.sendStatus(403);
    }
  }
});

// Inicia servidor na porta 3018
app.listen(3018, () => console.log("[LOG] Servidor rodando na porta 3018"));
