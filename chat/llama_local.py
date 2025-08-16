import sys
from pathlib import Path
from transformers import LlamaForCausalLM, LlamaTokenizer
import torch
import traceback

MODEL_PATH = Path(r"C:\Users\server_spd\.ollama\models\Llama3.2-3B-Instruct")
PROMPT_INICIAL = """Você é uma atendente de estúdio de tatuagem.
Seja simpática, clara e prestativa.
Forneça informações sobre agendamento, preços e estilos de tatuagem quando perguntado."""

# Logs
print("Python: Carregando tokenizer e modelo...", flush=True)

try:
    tokenizer = LlamaTokenizer.from_pretrained(MODEL_PATH, legacy=False)
    model = LlamaForCausalLM.from_pretrained(MODEL_PATH, device_map="auto")
except Exception as e:
    print("Python: Erro ao carregar modelo/tokenizer:", str(e), flush=True)
    traceback.print_exc()
    sys.exit(1)

print("Python: Modelo e tokenizer carregados com sucesso.", flush=True)

historicos = {}

def gerar_resposta(usuario, mensagem):
    try:
        print(f"Python: Recebido usuário='{usuario}', mensagem='{mensagem}'", flush=True)
        if usuario not in historicos:
            historicos[usuario] = [f"IA: {PROMPT_INICIAL}"]
            print(f"Python: Histórico inicial criado para {usuario}", flush=True)

        historicos[usuario].append(f"Usuario: {mensagem}")
        prompt = "\n".join(historicos[usuario]) + "\nIA:"

        print(f"Python: Prompt gerado:\n{prompt}", flush=True)

        inputs = tokenizer(prompt, return_tensors="pt").to(model.device)
        with torch.no_grad():
            outputs = model.generate(
                **inputs,
                max_new_tokens=150,
                do_sample=True,
                temperature=0.7,
                top_p=0.9
            )
        resposta = tokenizer.decode(outputs[0], skip_special_tokens=True)
        historicos[usuario].append(f"IA: {resposta}")

        print(f"Python: Resposta gerada:\n{resposta}", flush=True)
        return resposta
    except Exception as e:
        print("Python: Erro ao gerar resposta:", str(e), flush=True)
        traceback.print_exc()
        return f"Erro: {str(e)}"

# Loop stdin
print("Python: Aguardando mensagens...", flush=True)
for line in sys.stdin:
    if not line.strip():
        continue
    try:
        usuario, mensagem = line.strip().split("||", 1)
        resposta = gerar_resposta(usuario, mensagem)
        print(resposta, flush=True)
    except Exception as e:
        print("Python: Erro no loop principal:", str(e), flush=True)
        traceback.print_exc()
