<?php

function aluno_context_primary_id(): int
{
    @session_start();
    return (int) ($_SESSION['id'] ?? 0);
}

function aluno_context_ids(PDO $pdo): array
{
    @session_start();

    $ids = [];
    $idSessao = (int) ($_SESSION['id'] ?? 0);
    if ($idSessao > 0) {
        $ids[] = $idSessao;
    }

    $idVendedorSwitch = (int) ($_SESSION['switch_vendedor_usuario_id'] ?? 0);
    if ($idVendedorSwitch > 0 && $idVendedorSwitch !== $idSessao) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = :id AND nivel = 'Vendedor' AND ativo = 'Sim' LIMIT 1");
        $stmt->execute([':id' => $idVendedorSwitch]);
        if ((int) ($stmt->fetchColumn() ?: 0) > 0) {
            $ids[] = $idVendedorSwitch;
        }
    }

    return array_values(array_unique(array_map('intval', $ids)));
}

function aluno_context_bind_in(string $column, array $ids, array &$params, string $prefix = 'ctx'): string
{
    if (empty($ids)) {
        return "{$column} = 0";
    }

    $holders = [];
    foreach (array_values($ids) as $i => $id) {
        $key = ':' . $prefix . $i;
        $holders[] = $key;
        $params[$key] = (int) $id;
    }

    return "{$column} IN (" . implode(',', $holders) . ")";
}

function aluno_context_autorizado(PDO $pdo): bool
{
    @session_start();
    $idSessao = (int) ($_SESSION['id'] ?? 0);
    if ($idSessao <= 0) {
        return false;
    }

    $nivel = (string) ($_SESSION['nivel'] ?? '');
    if ($nivel === 'Aluno') {
        return true;
    }

    $idVendedorSwitch = (int) ($_SESSION['switch_vendedor_usuario_id'] ?? 0);
    if ($idVendedorSwitch <= 0) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = :id AND nivel = 'Vendedor' AND ativo = 'Sim' LIMIT 1");
    $stmt->execute([':id' => $idVendedorSwitch]);
    return (int) ($stmt->fetchColumn() ?: 0) > 0;
}

