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
 * Handle de mensagens, agora recebe phone_number_id
 */
async function handleMessage(message, phone, sender = "Cliente", phone_number_id) {
  let clients = loadClients();
  let client = clients.find(c => c.phone === phone);
  if (!client) {
    client = { phone, status: "Conversando", step: 0, operator: "Bot", history: [] };
    clients.push(client);
  }

  const text = message.trim();
  const textLower = text.toLowerCase();

  // Adicionar mensagem ao histórico
  client.history.push({ sender, message: text, time: new Date().toISOString() });
  client.lastMessage = text;
  client.lastMessageTime = new Date().toISOString();
  client.operator = sender === "Daniel" ? "Daniel" : "Bot";

  // Fluxo principal
  switch (client.step) {
    case 0:
      if (textLower.includes("batatadoce")) {
        client.step = 1;
        await sendMessage(phone, "Olá! Qual é o seu nome?", phone_number_id);
      } else {
        await sendMessage(phone, "Para iniciar, digite a palavra-chave do anúncio.", phone_number_id);
      }
      break;

    case 1:
      client.name = text;
      client.step = 2;
      await sendButtons(phone, `Olá ${client.name}! Escolha uma opção:`, mainMenuButtons, phone_number_id);
      break;

    case 2:
      if (text.includes("🎁") || textLower.includes("vouchers")) {
        client.step = 10;
        await sendMessage(phone, "Promoções atuais:\n- Promoção 1\n- Promoção 2\nRegras: Promoção válida até X...", phone_number_id);
      } else if (text.includes("💰") || textLower.includes("orçamento")) {
        client.step = 20;
        await sendMessage(phone, "Para orçamento, informe se é cobertura ou reforma e descreva sua ideia.\nR$50 de sinal.", phone_number_id);
      } else if (text.includes("📍") || textLower.includes("localização")) {
        await sendMessage(phone, "Nosso estúdio: Av. Jurema, 609, Pq Jurema, Guarulhos", phone_number_id);
      } else if (text.includes("📅") || textLower.includes("agendar")) {
        client.step = 30;
        await sendMessage(phone, "Informe datas e horários preferidos.\nSinal de R$50 para confirmar.", phone_number_id);
      } else if (text.includes("🩹") || textLower.includes("cuidados")) {
        await sendMessage(phone, "Cuidados após tatuagem:\n- Limpeza diária\n- Evitar sol\n- Não coçar crostas", phone_number_id);
      } else if (text.includes("👩‍💻") || textLower.includes("atendente")) {
        client.step = 99;
        await sendMessage(phone, "Um atendente humano entrará em contato em breve.", phone_number_id);
      } else {
        client.step = 98;
        await sendMessage(phone, "Desculpe, não entendi. Nosso assistente IA vai te ajudar.", phone_number_id);
        // Aqui poderia entrar integração com IA da Meta
      }
      break;

    default:
      // passos extras ou fallback
      await sendMessage(phone, "O fluxo atual não foi reconhecido. Tente novamente.", phone_number_id);
      break;
  }

  saveClients(clients);
}

module.exports = { handleMessage, loadClients, saveClients };
