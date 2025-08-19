const clientsDiv = document.getElementById("clientsList");
const socket = io();

async function loadClients() {
  clientsDiv.innerHTML = "";
  const res = await fetch("/api/clients");
  const clients = await res.json();

  clients.forEach(client => {
    const item = document.createElement("a");
    item.classList.add("list-group-item", "list-group-item-action");

    let bgColor = "#fff";
    if (client.operator === "Daniel") bgColor = "#d4edda";
    else if (client.operator === "Bot") bgColor = "#f8d7da";
    item.style.backgroundColor = bgColor;

    const lastTime = new Date(client.lastMessageTime);
    const diff = Math.floor((Date.now() - lastTime.getTime()) / 60000);

    item.innerHTML = `
      <strong>${client.name || client.phone}</strong><br>
      Última mensagem: ${client.lastMessage || "N/A"}<br>
      Status: ${client.status || "N/A"}<br>
      Operador: ${client.operator}<br>
      ${diff} min atrás
    `;
    item.onclick = () => openChat(client.phone);
    clientsDiv.appendChild(item);
  });
}

async function openChat(phone) {
  let chatWindow = document.getElementById("chatOverlay");

  if (!chatWindow) {
    chatWindow = document.createElement("div");
    chatWindow.id = "chatOverlay";
    chatWindow.style.position = "fixed";
    chatWindow.style.top = "50%";
    chatWindow.style.left = "50%";
    chatWindow.style.transform = "translate(-50%, -50%)";
    chatWindow.style.width = "400px";
    chatWindow.style.height = "500px";
    chatWindow.style.backgroundColor = "#fff";
    chatWindow.style.border = "2px solid #c0392b";
    chatWindow.style.padding = "10px";
    chatWindow.style.overflowY = "auto";
    document.body.appendChild(chatWindow);
  }

  chatWindow.innerHTML = `<h5>Chat ${phone}</h5>`;

  const input = document.createElement("input");
  input.type = "text";
  input.classList.add("form-control");
  input.placeholder = "Digite a mensagem";
  input.onkeypress = async e => {
    if (e.key === "Enter") {
      await fetch("/api/sendMessage", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ phone, message: input.value })
      });
      input.value = "";
      loadClients();
    }
  };
  chatWindow.appendChild(input);

  const endBtn = document.createElement("button");
  endBtn.innerText = "Encerrar conversa";
  endBtn.classList.add("btn", "btn-warning", "mt-2");
  endBtn.onclick = async () => {
    await fetch("/api/endFlow", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ phone, notifyClient: true })
    });
    chatWindow.remove();
    loadClients();
  };
  chatWindow.appendChild(endBtn);
}

socket.on("newMessage", () => loadClients());
loadClients();
