const axios = require("axios");
require("dotenv").config();

const PHONE_ID = process.env.WHATSAPP_PHONE_ID;
const TOKEN = process.env.WHATSAPP_TOKEN;

async function sendMessage(phone, text) {
  try {
    const url = `https://graph.facebook.com/v17.0/${PHONE_ID}/messages`;
    const data = {
      messaging_product: "whatsapp",
      to: phone,
      type: "text",
      text: { body: text }
    };
    const headers = { Authorization: `Bearer ${TOKEN}` };
    await axios.post(url, data, { headers });
    console.log("Mensagem enviada para", phone);
  } catch (err) {
    console.error("Erro ao enviar mensagem:", err.response?.data || err.message);
  }
}

async function sendButtons(phone, text, buttons) {
  try {
    const url = `https://graph.facebook.com/v17.0/${PHONE_ID}/messages`;
    const data = {
      messaging_product: "whatsapp",
      to: phone,
      type: "interactive",
      interactive: {
        type: "button",
        body: { text },
        action: { buttons: buttons.map((b, i) => ({ type: "reply", reply: { id: "btn_" + i, title: b } })) }
      }
    };
    const headers = { Authorization: `Bearer ${TOKEN}` };
    await axios.post(url, data, { headers });
    console.log("Botões enviados para", phone);
  } catch (err) {
    console.error("Erro ao enviar botões:", err.response?.data || err.message);
  }
}

module.exports = { sendMessage, sendButtons };
