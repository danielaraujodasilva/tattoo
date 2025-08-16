import sys
from pathlib import Path
from transformers import LlamaForCausalLM, LlamaTokenizer
import torch

# Caminho absoluto do modelo LLaMA local
MODEL_PATH = Path(r"C:\Users\server_spd\.ollama\models\Llama3.2-3B-Instruct")

# Prompt inicial para definir o comportamento da IA
PROMPT_INICIAL = """Você é uma atendente de estúdio de tatuagem. 
Seja simpática, clara e prestativa. 
Forneça informações sobre agendamento, preços e estilos de tatuagem quando perguntado."""

# Carrega tokenizer e modelo uma vez
tokenizer = LlamaTokenizer.from_pretrained(MODEL_PATH, legacy=False)
model = LlamaForCausalLM.from_pretrained(MODEL_PATH, device_map="auto")

# Histórico de conversa por usuário
historicos = {}

def gerar_resposta(usuario, mensagem):
    if usuario not in historicos:
        historicos[usuario] = [f"IA: {PROMPT_INICIAL}"]

    historicos[usuario].append(f"Usuario: {mensagem}")
    prompt = "\n".join(historicos[usuario]) + "\nIA:"
    
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
    return resposta

# Loop infinito para comunicação via stdin
for line in sys.stdin:
    if not line.strip():
        continue
    try:
        usuario, mensagem = line.strip().split("||", 1)
        resposta = gerar_resposta(usuario, mensagem)
        print(resposta, flush=True)
    except Exception as e:
        print(f"Erro: {str(e)}", flush=True)
