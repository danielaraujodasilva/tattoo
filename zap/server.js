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

// Webhook POST
app.post("/webhook", async (req, res) => {
  const data = req.body;
  console.log("Webhook payload recebido:", JSON.stringify(data, null, 2));

  if (data.entry && Array.isArray(data.entry)) {
    for (const entry of data.entry) {
      for (const change of entry.changes) {
        const value = change.value;
        const phone_number_id = value.metadata?.phone_number_id; // ID correto
        const messages = value.messages || [];
        const contactName = value.contacts?.[0]?.profile?.name || "Desconhecido";

        for (const msg of messages) {
          const phone = msg.from;
          const text = msg.text?.body || "";
          const hora = new Date().toLocaleTimeString();
          console.log(`[${hora}] Mensagem recebida de ${contactName} (${phone}): "${text}"`);

          await handleMessage(text, phone, "Bot", phone_number_id);
          io.emit("newMessage", { phone, message: text });
        }
      }
    }
  }

  res.sendStatus(200);
});

// Webhook GET
app.get("/webhook", (req, res) => {
  const verify_token = process.env.WHATSAPP_VERIFY_TOKEN || "zapcrm123";
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
  await handleMessage(message, phone, "Daniel", process.env.WHATSAPP_PHONE_ID);
  await sendMessage(phone, message, process.env.WHATSAPP_PHONE_ID);
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
  if (notifyClient) await sendMessage(phone, "Sua conversa foi encerrada. Qualquer dúvida, estamos à disposição!", process.env.WHATSAPP_PHONE_ID);
  saveClients(clients);
  io.emit("newMessage", { phone, message: "Fluxo encerrado" });
  res.json({ success: true });
});

const port = process.env.PORT || 3017;
server.listen(port, () => console.log(`Servidor rodando na porta ${port}`));
