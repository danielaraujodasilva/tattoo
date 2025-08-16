import sys
from pathlib import Path
from transformers import LlamaForCausalLM, LlamaTokenizer
import torch

MODEL_PATH = Path(r"C:\Users\server_spd\.ollama\models\Llama3.2-3B-Instruct")

# Carrega modelo e tokenizer apenas uma vez
tokenizer = LlamaTokenizer.from_pretrained(MODEL_PATH, legacy=False)
model = LlamaForCausalLM.from_pretrained(MODEL_PATH, device_map="auto")

# Mantém o histórico de mensagens para contexto
historico = []

def gerar_resposta(mensagem):
    global historico
    historico.append(f"Usuario: {mensagem}")
    prompt = "\n".join(historico) + "\nIA:"
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
    historico.append(f"IA: {resposta}")
    return resposta

# Loop infinito lendo mensagens do stdin
for line in sys.stdin:
    msg = line.strip()
    if not msg:
        continue
    if msg.lower() == "sair":
        break
    resposta = gerar_resposta(msg)
    print(resposta, flush=True)
