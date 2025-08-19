const { sendMessage, sendButtons } = require("./whatsapp");
const fs = require("fs");

const DATA_FILE = "./data/clients.json";

function loadClients() {
  if (!fs.existsSync(DATA_FILE)) return [];
  return JSON.parse(fs.readFileSync(DATA_FILE));
}

function saveClients(clients) {
  fs.writeFileSync(DATA_FILE, JSON.stringify(clients, null, 2));
}

const mainMenuButtons = [
  "🎁 Vouchers e promoções",
  "💰 Fazer um orçamento",
  "📍 Localização do estúdio",
  "📅 Agendar uma sessão",
  "🩹 Cuidados com a tatuagem",
  "👩‍💻 Falar com um atendente humano",
  "❓ Outras dúvidas"
];

async function handleMessage(message, phone, sender = "Cliente") {
  let clients = loadClients();
  let client = clients.find(c => c.phone === phone);
  if (!client) {
    client = { phone, status: "Conversando", step: 0, operator: "Bot", history: [] };
    clients.push(client);
  }

  const text = message.trim();

  // Adicionar mensagem ao histórico
  client.history.push({ sender, message: text, time: new Date().toISOString() });
  client.lastMessage = text;
  client.lastMessageTime = new Date().toISOString();

  if (sender === "Daniel") client.operator = "Daniel";
  else client.operator = "Bot";

  // Fluxo principal
  if (client.step === 0) {
    if (text.toLowerCase().includes("batatadoce")) {
      client.step = 1;
      await sendMessage(phone, "Olá! Qual é o seu nome?");
    } else {
      await sendMessage(phone, "Para iniciar, digite a palavra-chave do anúncio.");
    }
  } else if (client.step === 1) {
    client.name = message.trim();
    client.step = 2;
    await sendButtons(phone, "Olá " + client.name + "! Escolha uma opção:", mainMenuButtons);
  } else if (client.step === 2) {
    // Subfluxos simplificados (orçamento, agendamento, localização, etc.)
    if (text.includes("🎁") || text.toLowerCase().includes("vouchers")) {
      client.step = 10;
      await sendMessage(phone, "Promoções atuais:\n- Promoção 1\n- Promoção 2\nRegras: Promoção válida até X...");
    } else if (text.includes("💰") || text.toLowerCase().includes("orçamento")) {
      client.step = 20;
      await sendMessage(phone, "Para orçamento, informe se é cobertura ou reforma e descreva sua ideia.\nR$50 de sinal.");
    } else if (text.includes("📍") || text.toLowerCase().includes("localização")) {
      await sendMessage(phone, "Nosso estúdio: Av. Jurema, 609, Pq Jurema, Guarulhos");
    } else if (text.includes("📅") || text.toLowerCase().includes("agendar")) {
      client.step = 30;
      await sendMessage(phone, "Informe datas e horários preferidos.\nSinal de R$50 para confirmar.");
    } else if (text.includes("🩹") || text.toLowerCase().includes("cuidados")) {
      await sendMessage(phone, "Cuidados após tatuagem:\n- Limpeza diária\n- Evitar sol\n- Não coçar crostas");
    } else if (text.includes("👩‍💻") || text.toLowerCase().includes("atendente")) {
      client.step = 99;
      await sendMessage(phone, "Um atendente humano entrará em contato em breve.");
    } else {
      client.step = 98;
      await sendMessage(phone, "Desculpe, não entendi. Nosso assistente IA vai te ajudar.");
      // Aqui entraria a integração com IA da Meta
    }
  }

  saveClients(clients);
}

module.exports = { handleMessage, loadClients, saveClients };
