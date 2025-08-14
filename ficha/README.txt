Instruções rápidas

1) Descompacte o arquivo dentro da pasta do seu servidor (ex: C:/xampp/htdocs/anamnese/)
2) Acesse no navegador:
   - Formulário: http://localhost/anamnese/anamneses.html
   - Lista:     http://localhost/anamnese/listar_anamneses.php

3) Permissões:
   - A pasta `data/` será criada automaticamente. Garanta que o PHP tenha permissões de escrita nela.

4) O formulário salva assinaturas como PNG em data/assinaturas/ e os registros em data/anamneses.json (formato array).

5) Futuras melhorias sugeridas:
   - Migrar para MySQL (tabelas clientes e atendimentos).
   - Autenticação para abrir/ver fichas.
   - Validações mais robustas no servidor (CPF, sanitização adicional).

Suporte:
Se quiser que eu altere algo no layout, campos ou comportamento (por exemplo: gerar PDF em vez de imprimir), me diga que eu ajusto e atualizo o pacote.
