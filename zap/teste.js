require("dotenv").config();
const express = require("express");
const axios = require("axios");

const app = express();
app.use(express.json());

const phone_number_id = process.env.WHATSAPP_PHONE_ID;
const token = process.env.WHATSAPP_TOKEN;

// Função para enviar mensagem
async function enviarMensagem(numeroDestino, mensagem) {
  console.log("[LOG] Preparando para enviar mensagem para:", numeroDestino);
  const url = `https://graph.facebook.com/v17.0/${phone_number_id}/messages`;

  try {
    const response = await axios.post(
      url,
      {
        messaging_product: "whatsapp",
        to: numeroDestino,
        type: "text",
        text: { body: mensagem }
      },
      {
        headers: { Authorization: `Bearer ${token}` }
      }
    );
    console.log("[LOG] Mensagem enviada com sucesso!", response.data);
  } catch (err) {
    console.error("[ERROR] Falha ao enviar mensagem:", err.response?.data || err.message);
  }
}

// Função que define o fluxo de conversa
async function fluxoCliente(numeroDestino, mensagemRecebida) {
  console.log("[LOG] Iniciando fluxoCliente para:", numeroDestino, "com mensagem:", mensagemRecebida);

  mensagemRecebida = mensagemRecebida.toLowerCase();

  if (mensagemRecebida.includes("oi") || mensagemRecebida.includes("olá")) {
    console.log("[LOG] Cliente disse oi/olá");
    await enviarMensagem(numeroDestino, 
      "Olá! 👋 Bem-vindo ao estúdio XYZ Tattoo.\nPosso te ajudar com:\n1️⃣ Agendar uma tattoo\n2️⃣ Consultar preços\n3️⃣ Dúvidas gerais"
    );
  } else if (mensagemRecebida.includes("1") || mensagemRecebida.includes("agendar")) {
    console.log("[LOG] Cliente escolheu agendar");
    await enviarMensagem(numeroDestino, 
      "Perfeito! Qual data você gostaria de agendar sua tattoo? Por favor, envie no formato DD/MM."
    );
  } else if (mensagemRecebida.includes("2") || mensagemRecebida.includes("preço")) {
    console.log("[LOG] Cliente perguntou preço");
    await enviarMensagem(numeroDestino, 
      "Nossos preços variam de acordo com o tamanho e estilo da tattoo. Pode me enviar uma referência ou tamanho aproximado?"
    );
  } else if (mensagemRecebida.includes("3") || mensagemRecebida.includes("dúvida")) {
    console.log("[LOG] Cliente tem dúvida");
    await enviarMensagem(numeroDestino, 
      "Pode me enviar sua dúvida que responderei em detalhes!"
    );
  } else {
    console.log("[LOG] Mensagem não reconhecida");
    await enviarMensagem(numeroDestino, 
      "Desculpe, não entendi. Por favor, digite 1, 2 ou 3 para escolher uma opção."
    );
  }
}

// Webhook POST para receber mensagens
app.post("/webhook", async (req, res) => {
  console.log("[LOG] Webhook recebido!");
  console.log("[LOG] Corpo da requisição:", JSON.stringify(req.body, null, 2));

  try {
    const body = req.body;
    const messages = body.entry?.[0]?.changes?.[0]?.value?.messages;

    if (!messages || !messages.length) {
      console.log("[LOG] Nenhuma mensagem encontrada no corpo");
      return res.sendStatus(200);
    }

    console.log("[LOG] Mensagem detectada!");
    const message = messages[0];
    const numeroCliente = message.from;
    const textoMensagem = message.text?.body || "";

    console.log("[LOG] Número do cliente:", numeroCliente);
    console.log("[LOG] Texto da mensagem:", textoMensagem);

    await fluxoCliente(numeroCliente, textoMensagem);

    res.sendStatus(200);
  } catch (err) {
    console.error("[ERROR] Erro no webhook:", err);
    res.sendStatus(500);
  }
});

// Webhook GET para verificação
app.get("/webhook", (req, res) => {
  const verify_token = process.env.WHATSAPP_VERIFY_TOKEN;

  const mode = req.query["hub.mode"];
  const token = req.query["hub.verify_token"];
  const challenge = req.query["hub.challenge"];

  console.log("[LOG] GET webhook chamado para verificação");

  if (mode && token) {
    if (mode === "subscribe" && token === verify_token) {
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
