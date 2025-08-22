require("dotenv").config();
const express = require("express");
const axios = require("axios");

const app = express();
app.use(express.json());

const phone_number_id = process.env.WHATSAPP_PHONE_ID;
const token = process.env.WHATSAPP_TOKEN;

// Função genérica para enviar mensagem
async function enviarMensagem(numeroDestino, mensagem) {
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
    console.log("Mensagem enviada para", numeroDestino);
  } catch (err) {
    console.error("Erro ao enviar mensagem:", err.response?.data || err.message);
  }
}

// Função que define o fluxo de conversa
async function fluxoCliente(numeroDestino, mensagemRecebida) {
  mensagemRecebida = mensagemRecebida.toLowerCase();

  if (mensagemRecebida.includes("oi") || mensagemRecebida.includes("olá")) {
    await enviarMensagem(numeroDestino, 
      "Olá! 👋 Bem-vindo ao estúdio XYZ Tattoo.\nPosso te ajudar com:\n1️⃣ Agendar uma tattoo\n2️⃣ Consultar preços\n3️⃣ Dúvidas gerais"
    );
  } else if (mensagemRecebida.includes("1") || mensagemRecebida.includes("agendar")) {
    await enviarMensagem(numeroDestino, 
      "Perfeito! Qual data você gostaria de agendar sua tattoo? Por favor, envie no formato DD/MM."
    );
  } else if (mensagemRecebida.includes("2") || mensagemRecebida.includes("preço")) {
    await enviarMensagem(numeroDestino, 
      "Nossos preços variam de acordo com o tamanho e estilo da tattoo. Pode me enviar uma referência ou tamanho aproximado?"
    );
  } else if (mensagemRecebida.includes("3") || mensagemRecebida.includes("dúvida")) {
    await enviarMensagem(numeroDestino, 
      "Pode me enviar sua dúvida que responderei em detalhes!"
    );
  } else {
    await enviarMensagem(numeroDestino, 
      "Desculpe, não entendi. Por favor, digite 1, 2 ou 3 para escolher uma opção."
    );
  }
}

// Webhook para receber mensagens
app.post("/webhook", async (req, res) => {
  console.log("Recebi algo no webhook:", JSON.stringify(req.body, null, 2));

  try {
    const body = req.body;
    const messages = body.entry?.[0]?.changes?.[0]?.value?.messages;

    if (messages?.length) {
      const message = messages[0];
      const numeroCliente = message.from;
      const textoMensagem = message.text?.body || "";

      console.log("Mensagem recebida de", numeroCliente, ":", textoMensagem);

      await fluxoCliente(numeroCliente, textoMensagem);
    }

    res.sendStatus(200);
  } catch (err) {
    console.error("Erro no webhook:", err);
    res.sendStatus(500);
  }
});

// Endpoint de verificação do webhook
app.get("/webhook", (req, res) => {
  const verify_token = process.env.WHATSAPP_VERIFY_TOKEN;

  const mode = req.query["hub.mode"];
  const token = req.query["hub.verify_token"];
  const challenge = req.query["hub.challenge"];

  if (mode && token) {
    if (mode === "subscribe" && token === verify_token) {
      console.log("Webhook verificado com sucesso!");
      res.status(200).send(challenge);
    } else {
      res.sendStatus(403);
    }
  }
});

app.listen(3018, () => console.log("Servidor rodando na porta 3018"));
