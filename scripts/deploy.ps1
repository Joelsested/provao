param(
    [string]$Message = ""
)

$ErrorActionPreference = "Stop"

if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Host "git nao encontrado no PATH."
    exit 1
}

if (-not (Test-Path ".git")) {
    Write-Host "Repositorio git nao encontrado. Execute 'git init' primeiro."
    exit 1
}

Write-Host "Status atual:"
git status -sb

git add .

$hasStaged = $true
git diff --cached --quiet
if ($LASTEXITCODE -eq 0) {
    $hasStaged = $false
}

if (-not $hasStaged) {
    Write-Host "Nao ha alteracoes para commit."
} else {
    if ([string]::IsNullOrWhiteSpace($Message)) {
        $Message = Read-Host "Mensagem do commit"
    }
    if ([string]::IsNullOrWhiteSpace($Message)) {
        Write-Host "Mensagem vazia. Abortando."
        exit 1
    }
    git commit -m $Message
}

git push

Write-Host "Deploy via git finalizado."
