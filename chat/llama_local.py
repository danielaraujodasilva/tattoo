import sys
from pathlib import Path
from transformers import LlamaForCausalLM, LlamaTokenizer
import torch

MODEL_PATH = Path(r"C:\Users\server_spd\.ollama\models\Llama3.2-3B-Instruct")

# Carrega modelo e tokenizer **uma vez**
tokenizer = LlamaTokenizer.from_pretrained(MODEL_PATH, legacy=False)
model = LlamaForCausalLM.from_pretrained(MODEL_PATH, device_map="auto")

def gerar_resposta(mensagem):
    inputs = tokenizer(mensagem, return_tensors="pt").to(model.device)
    with torch.no_grad():
        outputs = model.generate(
            **inputs,
            max_new_tokens=100,
            do_sample=True,
            temperature=0.7,
            top_p=0.9
        )
    resposta = tokenizer.decode(outputs[0], skip_special_tokens=True)
    return resposta

# Loop infinito lendo mensagens do stdin
for line in sys.stdin:
    msg = line.strip()
    if not msg:
        continue
    if msg.lower() == "sair":
        break
    resposta = gerar_resposta(msg)
    print(resposta, flush=True)  # **flush garante que Node receba a resposta imediatamente**
