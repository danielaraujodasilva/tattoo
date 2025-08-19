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

// Webhook para WhatsApp
app.post("/webhook", async (req, res) => {
  const data = req.body;
  console.log("Webhook recebido:", data);

  // Assumindo que a mensagem vem em data.messages[0]
  if (data.messages && data.messages.length > 0) {
    const msg = data.messages[0];
    const phone = msg.from;
    const text = msg.text?.body || "";
    await handleMessage(text, phone, "Bot");
  }
  res.sendStatus(200);
});

// Verificação do webhook
app.get("/webhook", (req, res) => {
  const challenge = req.query["hub.challenge"];
  res.send(challenge);
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
