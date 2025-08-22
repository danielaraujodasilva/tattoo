require("dotenv").config();
const axios = require("axios");

async function enviarMensagemTeste() {
  const phone_number_id = process.env.WHATSAPP_PHONE_ID;
  const token = process.env.WHATSAPP_TOKEN;
  const numeroDestino = "5511947573311"; // Coloque aqui o número que quer testar (formato E.164)
  const mensagem = "Mensagem de teste do bot! ✅";

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
    console.log("Mensagem enviada com sucesso!");
    console.log(response.data);
  } catch (err) {
    console.error("Erro ao enviar mensagem:", err.response?.data || err.message);
  }
}

enviarMensagemTeste();
