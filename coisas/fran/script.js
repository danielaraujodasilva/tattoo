
let tarefas = [];
let tarefaEditando = null;

document.addEventListener("DOMContentLoaded", () => {
  fetch('carregar.php')
    .then(res => res.json())
    .then(data => {
      tarefas = data;
      renderizarTarefas();
    });

  new Sortable(listaTarefas, {
    animation: 150,
    onEnd: salvarOrdem
  });
});

function renderizarTarefas() {
  const lista = document.getElementById("listaTarefas");
  lista.innerHTML = "";
  tarefas.forEach((tarefa, index) => {
    const li = document.createElement("li");
    li.className = "list-group-item list-group-item-action bg-secondary text-white d-flex justify-content-between align-items-center";
    li.innerHTML = `
      <span onclick="abrirModal(${index})">${tarefa.titulo}</span>
      <button class="btn btn-sm btn-danger" onclick="removerTarefa(${index})">🗑</button>
    `;
    lista.appendChild(li);
  });
}

function adicionarTarefa() {
  const input = document.getElementById("novaTarefa");
  const titulo = input.value.trim();
  if (titulo) {
    tarefas.push({ id: Date.now(), titulo, detalhes: "" });
    salvarTarefas();
    input.value = "";
  }
}

function removerTarefa(index) {
  tarefas.splice(index, 1);
  salvarTarefas();
}

function abrirModal(index) {
  tarefaEditando = index;
  document.getElementById("modalTitulo").value = tarefas[index].titulo;
  document.getElementById("modalDetalhesTexto").value = tarefas[index].detalhes;
  new bootstrap.Modal(document.getElementById("modalDetalhes")).show();
}

function salvarEdicao() {
  const titulo = document.getElementById("modalTitulo").value;
  const detalhes = document.getElementById("modalDetalhesTexto").value;
  tarefas[tarefaEditando].titulo = titulo;
  tarefas[tarefaEditando].detalhes = detalhes;
  salvarTarefas();
  bootstrap.Modal.getInstance(document.getElementById("modalDetalhes")).hide();
}

function salvarTarefas() {
  fetch('salvar.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(tarefas)
  }).then(() => renderizarTarefas());
}

function salvarOrdem(evt) {
  const novos = [];
  document.querySelectorAll("#listaTarefas li").forEach(li => {
    const span = li.querySelector("span").textContent;
    const tarefa = tarefas.find(t => t.titulo === span);
    if (tarefa) novos.push(tarefa);
  });
  tarefas = novos;
  salvarTarefas();
}
