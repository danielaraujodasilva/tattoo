const express = require("express");
const bodyParser = require("body-parser");
const { handleMessage } = require("./flow");
const { sendMessage } = require("./whatsapp");
const fs = require("fs");
const http = require("http");
const socketIo = require("socket.io");
require("dotenv").config();

const app = express();
const server = http.createServer(app);
const io = socketIo(server);

app.use(bodyParser.json());
app.use(express.static("public"));

const DATA_FILE = "./data/clients.json";

function loadClients() {
  if (!fs.existsSync(DATA_FILE)) return [];
  return JSON.parse(fs.readFileSync(DATA_FILE));
}

function saveClients(clients) {
  fs.writeFileSync(DATA_FILE, JSON.stringify(clients, null, 2));
}

// Webhook para WhatsApp (recebendo mensagens)
app.post("/webhook", async (req, res) => {
  const data = req.body;
  console.log("Webhook recebido:", JSON.stringify(data, null, 2)); // log completo

  if (
    data.entry &&
    Array.isArray(data.entry) &&
    data.entry.length > 0
  ) {
    const changes = data.entry[0].changes;
    if (Array.isArray(changes) && changes.length > 0) {
      const value = changes[0].value;
      const messages = value.messages;

      if (Array.isArray(messages) && messages.length > 0) {
        for (const msg of messages) {
          const phone = msg.from;
          const text = msg.text?.body || "";
          console.log(`Mensagem recebida de ${phone}: ${text}`);
          await handleMessage(text, phone, "Bot");
          io.emit("newMessage", { phone, message: text });
        }
      }
    }
  }

  res.sendStatus(200);
});

// Verificação do webhook (GET) — necessário para Meta
app.get("/webhook", (req, res) => {
  const verify_token = process.env.WHATSAPP_VERIFY_TOKEN || "zapcrm123"; // token da Meta

  const mode = req.query["hub.mode"];
  const token = req.query["hub.verify_token"];
  const challenge = req.query["hub.challenge"];

  if (mode && token) {
    if (mode === "subscribe" && token === verify_token) {
      console.log("WEBHOOK_VERIFIED");
      res.status(200).send(challenge);
    } else {
      res.sendStatus(403);
    }
  } else {
    res.sendStatus(400); 
  }
});

// API painel
app.get("/api/clients", (req, res) => {
  res.json(loadClients());
});

app.post("/api/sendMessage", async (req, res) => {
  const { phone, message } = req.body;
  await handleMessage(message, phone, "Daniel");
  await sendMessage(phone, message);
  io.emit("newMessage", { phone, message });
  res.json({ success: true });
});

app.post("/api/endFlow", async (req, res) => {
  const { phone, notifyClient } = req.body;
  const clients = loadClients();
  const client = clients.find(c => c.phone === phone);
  if (!client) return res.status(404).json({ error: "Cliente não encontrado" });

  client.status = "Atendido";
  client.step = 0;
  if (notifyClient) await sendMessage(phone, "Sua conversa foi encerrada. Qualquer dúvida, estamos à disposição!");
  saveClients(clients);
  io.emit("newMessage", { phone, message: "Fluxo encerrado" });
  res.json({ success: true });
});

const port = process.env.PORT || 3017;
server.listen(port, () => console.log(`Servidor rodando na porta ${port}`));
