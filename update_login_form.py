from pathlib import Path

path = Path("sistema/index.php")
lines = path.read_text("latin-1").splitlines()

for idx, line in enumerate(lines):
    if "CPF / Usuário" in line:
        lines[idx] = "\t\t\t\t\t\t\t\t<span class=\"label-input100\">CPF</span><br>"
        lines[idx + 1] = "\t\t\t\t\t\t\t\t<input type=\"text\" name=\"usuario\" id=\"usuario\" class=\"input100\" placeholder=\"CPF (somente dígitos) do aluno ou e-mail do administrador\" pattern=\"[0-9@.\\-]{5,50}\" title=\"Informe o CPF do aluno (apenas números) ou o e-mail do administrador\" required>"
        lines.insert(idx + 2, "\t\t\t\t\t\t\t\t<small class=\"text-muted\">Alunos usam CPF; administradores mantêm o login com e-mail.</small>")
        break
else:
    raise SystemExit("CPF label not found")

for idx, line in enumerate(lines):
    if 'placeholder="Data de nascimento do Usuário ou senha do administrador"' in line:
        lines[idx] = "\t\t\t\t\t\t\t\t<div class=\"input-group\">"
        lines.insert(idx + 1, "\t\t\t\t\t\t\t\t\t<input type=\"password\" name=\"senha\" id=\"senha\" class=\"input100\" placeholder=\"Data de nascimento (DDMMAAAA) ou senha do administrador\" required>")
        lines.insert(idx + 2, "\t\t\t\t\t\t\t\t\t<div class=\"input-group-append\">")
        lines.insert(idx + 3, "\t\t\t\t\t\t\t\t\t\t<button type=\"button\" class=\"btn btn-outline-secondary btn-sm\" id=\"toggleSenha\" aria-label=\"Mostrar/ocultar senha\">Mostrar</button>")
        lines.insert(idx + 4, "\t\t\t\t\t\t\t\t\t</div>")
        lines.insert(idx + 5, "\t\t\t\t\t\t\t\t</div>")
        lines.insert(idx + 6, "\t\t\t\t\t\t\t\t<small class=\"text-muted\">Alunos devem digitar apenas os dígitos da data de nascimento.</small>")
        break
else:
    raise SystemExit("password placeholder block not found")

path.write_text("\n".join(lines), "latin-1")
