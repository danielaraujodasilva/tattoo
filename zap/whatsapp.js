const axios = require("axios");
require("dotenv").config();

async function sendMessage(phone, text, phone_number_id = process.env.WHATSAPP_PHONE_ID) {
  try {
    const url = `https://graph.facebook.com/v17.0/${phone_number_id}/messages`;
    const data = {
      messaging_product: "whatsapp",
      to: phone,
      type: "text",
      text: { body: text }
    };
    const headers = { Authorization: `Bearer ${process.env.WHATSAPP_TOKEN}` };
    await axios.post(url, data, { headers });
    console.log("Mensagem enviada para", phone);
  } catch (err) {
    console.error("Erro ao enviar mensagem:", err.response?.data || err.message);
  }
}

async function sendButtons(phone, text, buttons, phone_number_id = process.env.WHATSAPP_PHONE_ID) {
  try {
    const url = `https://graph.facebook.com/v17.0/${phone_number_id}/messages`;
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
    const headers = { Authorization: `Bearer ${process.env.WHATSAPP_TOKEN}` };
    await axios.post(url, data, { headers });
    console.log("Botões enviados para", phone);
  } catch (err) {
    console.error("Erro ao enviar botões:", err.response?.data || err.message);
  }
}

module.exports = { sendMessage, sendButtons };
