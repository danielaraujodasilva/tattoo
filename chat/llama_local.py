import sys
from pathlib import Path
from transformers import LlamaForCausalLM, LlamaTokenizer
import torch

# Caminho para o modelo (use barra normal para Windows)
MODEL_PATH = Path(r"C:\Users\server_spd\.ollama\models\Llama3.2-3B-Instruct").as_posix()

# Histórico das mensagens para contexto
historico = []

# Prompt inicial da atendente
prompt_base = (
    "Você é uma atendente de um estúdio de tatuagem, prestativa e educada. "
    "Responda perguntas de clientes sobre tatuagens, horários, preços e cuidados. "
)

print("Python: Iniciando carregamento do tokenizer e modelo...", flush=True)

try:
    tokenizer = LlamaTokenizer.from_pretrained(MODEL_PATH)
    model = LlamaForCausalLM.from_pretrained(MODEL_PATH, device_map="auto")
    print("Python: Modelo e tokenizer carregados com sucesso!", flush=True)
except Exception as e:
    print(f"Python: Erro ao carregar modelo/tokenizer: {e}", flush=True)
    sys.exit(1)

def gerar_resposta(mensagem):
    global historico

    # Adiciona mensagem ao histórico
    historico.append(f"Cliente: {mensagem}")

    # Monta prompt completo com histórico
    prompt_completo = prompt_base + "\n" + "\n".join(historico) + "\nAtendente:"
    print(f"Python: Prompt enviado para o modelo:\n{prompt_completo}\n", flush=True)

    inputs = tokenizer(prompt_completo, return_tensors="pt").to(model.device)

    try:
        with torch.no_grad():
            outputs = model.generate(
                **inputs,
                max_new_tokens=150,
                do_sample=True,
                temperature=0.7,
                top_p=0.9
            )
        resposta = tokenizer.decode(outputs[0], skip_special_tokens=True)

        # Mantém apenas a parte da resposta do modelo
        resposta_final = resposta.split("Atendente:")[-1].strip()

        # Adiciona ao histórico
        historico.append(f"Atendente: {resposta_final}")

        print(f"Python: Resposta gerada: {resposta_final}\n", flush=True)
        return resposta_final
    except Exception as e:
        print(f"Python: Erro ao gerar resposta: {e}", flush=True)
        return "Desculpe, ocorreu um erro ao processar sua mensagem."

# Loop infinito lendo mensagens do stdin
print("Python: Aguardando mensagens do Node.js...", flush=True)
for line in sys.stdin:
    msg = line.strip()
    if msg.lower() == "sair":
        print("Python: Encerrando processo...", flush=True)
        break
    resposta = gerar_resposta(msg)
    print(resposta, flush=True)
