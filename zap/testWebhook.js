const axios = require("axios");

const WEBHOOK_URL = "https://67380b5a085c.ngrok-free.app/webhook"; // sua URL do ngrok
const TEST_PHONE = "5511999999999"; // número de teste fictício
const TEST_MESSAGE = "Oi, quero testar o webhook!";

const payload = {
  entry: [
    {
      changes: [
        {
          value: {
            messages: [
              {
                from: TEST_PHONE,
                id: "TEST123",
                timestamp: Date.now().toString(),
                text: { body: TEST_MESSAGE },
                type: "text"
              }
            ]
          }
        }
      ]
    }
  ]
};

(async () => {
  try {
    const res = await axios.post(WEBHOOK_URL, payload);
    console.log("Webhook recebido:", JSON.stringify(data, null, 2));
  } catch (err) {
    console.error("Erro ao enviar webhook de teste:", err.message);
  }
})();
