Checklist de deploy/producao

Pre-deploy
- Fazer backup do banco (dump completo).
- Confirmar que o codigo local esta com `git status` limpo.
- Verificar se o dominio e HTTPS estao ativos.
- Confirmar PHP 8.2+ e extensoes: curl, openssl, mbstring, pdo_mysql, fileinfo, json.

Configuracao de ambiente (.env no servidor)
- DB_HOST, DB_NAME, DB_USER, DB_PASS.
- API_TOKEN e WEBHOOK_TOKEN.
- EFI_SANDBOX, EFI_CLIENT_ID_PROD, EFI_CLIENT_SECRET_PROD.
- EFI_CERT_PATH_PROD (caminho absoluto do .pem).
- EFI_CLIENT_ID_HOMOLOG, EFI_CLIENT_SECRET_HOMOLOG.
- EFI_CERT_PATH_HOMOLOG (caminho absoluto do .pem).
- EFI_PIX_KEY.
- ASAAS_ENABLED=false (se nao usa).
- MP_ENABLED=false (se nao usa).
- MP_ACCESS_TOKEN, MP_PUBLIC_KEY (somente se MP estiver ativo).

Infra/servidor
- Apache com AllowOverride All para .htaccess (headers e rewrites).
- Permissoes de escrita:
  - sistema/painel-admin/img/contas
  - sistema/painel-admin/img/arquivos
  - sistema/painel-aluno/img/arquivos
  - sistema/painel-secretario/img/arquivos
  - sistema/painel-aluno/img/perfil
  - sistema/painel-admin/img/perfil
  - sistema/painel-secretario/img/perfil
- Certificados EFI existentes em `efi/` (e caminhos corretos no .env).

Webhooks
- Configurar EFI para enviar o token:
  - Authorization: Bearer <WEBHOOK_TOKEN> (ou X-Webhook-Token).
- Validar resposta 200 no webhook apos um evento de teste.

Pos-deploy
- Testar login admin e aluno.
- Testar compra de curso/pacote (pix/boleto).
- Confirmar webhook atualiza matricula e libera cursos.
- Verificar upload de documentos e foto.
- Verificar historico/certificados.

Rollback (se necessario)
- Fazer checkout do commit anterior e redeploy.
- Restaurar backup do banco se houve alteracoes de schema.
