import sys
from pathlib import Path

# Caminho absoluto do seu modelo LLaMA
MODEL_PATH = Path(r"C:\Users\server_spd\.ollama\models")

try:
    from transformers import LlamaForCausalLM, LlamaTokenizer
    import torch
except ImportError:
    print("Instale transformers e torch: pip install transformers torch", file=sys.stderr)
    sys.exit(1)

def main():
    if len(sys.argv) < 2:
        print("Nenhuma mensagem recebida.", file=sys.stderr)
        sys.exit(1)

    mensagem = sys.argv[1]

    # Carrega tokenizer e modelo
    tokenizer = LlamaTokenizer.from_pretrained(MODEL_PATH)
    model = LlamaForCausalLM.from_pretrained(MODEL_PATH, device_map="auto")

    # Tokeniza a mensagem
    inputs = tokenizer(mensagem, return_tensors="pt").to(model.device)

    # Gera a resposta
    with torch.no_grad():
        outputs = model.generate(
            **inputs,
            max_new_tokens=100,
            do_sample=True,
            temperature=0.7,
            top_p=0.9
        )

    resposta = tokenizer.decode(outputs[0], skip_special_tokens=True)

    # Imprime a resposta para o Node.js capturar
    print(resposta)

if __name__ == "__main__":
    main()
