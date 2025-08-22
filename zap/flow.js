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

/**
 * Agora o handleMessage recebe o phone_number_id como argumento.
 */
async function handleMessage(message, phone, sender = "Cliente", phone_number_id) {
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
      await sendMessage(phone, "Olá! Qual é o seu nome?", phone_number_id);
    } else {
      await sendMessage(phone, "Para iniciar, digite a palavra-chave do anúncio.", phone_number_id);
    }
  } else if (client.step === 1) {
    client.name = message.trim();
    client.step = 2;
    await sendButtons(phone, "Olá " + client.name + "! Escolha uma opção:", mainMenuButtons, phone_number_id);
  } else if (client.step === 2) {
    // Subfluxos simplificados
    if (text.includes("🎁") || text.toLowerCase().includes("vouchers")) {
      client.step = 10;
      await sendMessage(phone, "Promoções atuais:\n- Promoção 1\n- Promoção 2\nRegras: Promoção válida até X...", phone_number_id);
    } else if (text.includes("💰") || text.toLowerCase().includes("orçamento")) {
      client.step = 20;
      await sendMessage(phone, "Para orçamento, informe se é cobertura ou reforma e descreva sua ideia.\nR$50 de sinal.", phone_number_id);
    } else if (text.includes("📍") || text.toLowerCase().includes("localização")) {
      await sendMessage(phone, "Nosso estúdio: Av. Jurema, 609, Pq Jurema, Guarulhos", phone_number_id);
    } else if (text.includes("📅") || text.toLowerCase().includes("agendar")) {
      client.step = 30;
      await sendMessage(phone, "Informe datas e horários preferidos.\nSinal de R$50 para confirmar.", phone_number_id);
    } else if (text.includes("🩹") || text.toLowerCase().includes("cuidados")) {
      await sendMessage(phone, "Cuidados após tatuagem:\n- Limpeza diária\n- Evitar sol\n- Não coçar crostas", phone_number_id);
    } else if (text.includes("👩‍💻") || text.toLowerCase().includes("atendente")) {
      client.step = 99;
      await sendMessage(phone, "Um atendente humano entrará em contato em breve.", phone_number_id);
    } else {
      client.step = 98;
      await sendMessage(phone, "Desculpe, não entendi. Nosso assistente IA vai te ajudar.", phone_number_id);
      // Aqui entraria a integração com IA da Meta
    }
  }

  saveClients(clients);
}

module.exports = { handleMessage, loadClients, saveClients };
