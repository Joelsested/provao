<?php

if (!function_exists('digitsOnly')) {
    function digitsOnly(string $value): string
    {
        return preg_replace('/\D/', '', $value);
    }
}

if (!function_exists('parseDateParts')) {
    function parseDateParts(string $value)
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches) && checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1])) {
            return [ (int)$matches[1], (int)$matches[2], (int)$matches[3] ];
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $matches) && checkdate((int)$matches[2], (int)$matches[1], (int)$matches[3])) {
            return [ (int)$matches[3], (int)$matches[2], (int)$matches[1] ];
        }

        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $value, $matches) && checkdate((int)$matches[2], (int)$matches[1], (int)$matches[3])) {
            return [ (int)$matches[3], (int)$matches[2], (int)$matches[1] ];
        }

        $digits = digitsOnly($value);
        if (strlen($digits) === 8) {
            $day = (int)substr($digits, 0, 2);
            $month = (int)substr($digits, 2, 2);
            $year = (int)substr($digits, 4, 4);
            if (checkdate($month, $day, $year)) {
                return [ $year, $month, $day ];
            }

            $year = (int)substr($digits, 0, 4);
            $month = (int)substr($digits, 4, 2);
            $day = (int)substr($digits, 6, 2);
            if (checkdate($month, $day, $year)) {
                return [ $year, $month, $day ];
            }
        }

        return false;
    }
}

if (!function_exists('normalizeDate')) {
    function normalizeDate(string $value): string
    {
        $parts = parseDateParts($value);
        if ($parts === false) {
            return '';
        }

        [$year, $month, $day] = $parts;
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
}

if (!function_exists('birthDigits')) {
    function birthDigits(string $value): string
    {
        $parts = parseDateParts($value);
        if ($parts === false) {
            return '';
        }

        [$year, $month, $day] = $parts;
        return sprintf('%02d%02d%04d', $day, $month, $year);
    }
}

if (!function_exists('cleanCpfColumn')) {
    function cleanCpfColumn(string $column = 'cpf'): string
    {
        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE($column, '.', ''), '-', ''), ' ', ''), '/', ''), '(', ''), ')', '')";
    }
}

if (!function_exists('nextTableId')) {
    function nextTableId(PDO $pdo, string $table): ?int
    {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        if ($table === '') {
            return null;
        }
        $stmt = $pdo->query("SHOW COLUMNS FROM {$table} LIKE 'id'");
        $colInfo = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if ($colInfo && stripos($colInfo['Extra'] ?? '', 'auto_increment') !== false) {
            return null;
        }
        $nextId = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM {$table}")->fetchColumn();
        return (int) $nextId;
    }
}

if (!function_exists('post_value')) {
    function post_value(array $keys, $default = ''): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $_POST)) {
                return trim((string) $_POST[$key]);
            }
        }
        return trim((string) $default);
    }
}

if (!function_exists('tableHasColumn')) {
    function tableHasColumn(PDO $pdo, string $table, string $column): bool
    {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        if ($table === '' || $column === '') {
            return false;
        }
        $stmt = $pdo->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
        return (bool) ($stmt && $stmt->fetch(PDO::FETCH_ASSOC));
    }
}

if (!function_exists('getConfigDateCorteAtendente')) {
    function getConfigDateCorteAtendente(PDO $pdo): string
    {
        if (!tableHasColumn($pdo, 'config', 'data_corte_atendente')) {
            try {
                $pdo->exec("ALTER TABLE config ADD COLUMN data_corte_atendente DATE DEFAULT NULL");
            } catch (Exception $e) {
                return '';
            }
        }

        $stmt = $pdo->query("SELECT data_corte_atendente FROM config LIMIT 1");
        $value = $stmt ? ($stmt->fetchColumn() ?: '') : '';
        $normalized = normalizeDate((string) $value);
        return $normalized;
    }
}

if (!function_exists('getConfigAdminOverrideTrocaAtendente')) {
    function getConfigAdminOverrideTrocaAtendente(PDO $pdo): bool
    {
        if (!tableHasColumn($pdo, 'config', 'destrava_troca_atendente_admin')) {
            try {
                $pdo->exec("ALTER TABLE config ADD COLUMN destrava_troca_atendente_admin TINYINT(1) NOT NULL DEFAULT 0");
            } catch (Exception $e) {
                return false;
            }
        }

        try {
            $stmt = $pdo->query("SELECT destrava_troca_atendente_admin FROM config LIMIT 1");
            $value = $stmt ? $stmt->fetchColumn() : 0;
            return (int) ($value ?? 0) === 1;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('ensureHistoricoAtendentesTable')) {
    function ensureHistoricoAtendentesTable(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS historico_atendentes (
            id int(11) NOT NULL AUTO_INCREMENT,
            aluno_id int(11) NOT NULL,
            usuario_anterior int(11) DEFAULT NULL,
            usuario_novo int(11) NOT NULL,
            motivo varchar(255) DEFAULT NULL,
            origem varchar(50) NOT NULL,
            admin_id int(11) DEFAULT NULL,
            data datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_aluno_id (aluno_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

if (!function_exists('registrarHistoricoAtendente')) {
    function registrarHistoricoAtendente(PDO $pdo, int $alunoId, ?int $usuarioAnterior, int $usuarioNovo, ?string $motivo, string $origem, ?int $adminId, ?string $dataOverride = null): void
    {
        try {
            ensureHistoricoAtendentesTable($pdo);
            $dataOverrideNorm = $dataOverride ? normalizeDate($dataOverride) : '';
            if ($dataOverrideNorm !== '') {
                $stmt = $pdo->prepare("INSERT INTO historico_atendentes SET aluno_id = :aluno_id, usuario_anterior = :anterior, usuario_novo = :novo, motivo = :motivo, origem = :origem, admin_id = :admin_id, data = :data");
                $params = [
                    ':aluno_id' => $alunoId,
                    ':anterior' => $usuarioAnterior,
                    ':novo' => $usuarioNovo,
                    ':motivo' => $motivo,
                    ':origem' => $origem,
                    ':admin_id' => $adminId,
                    ':data' => $dataOverrideNorm . ' 00:00:00',
                ];
            } else {
                $stmt = $pdo->prepare("INSERT INTO historico_atendentes SET aluno_id = :aluno_id, usuario_anterior = :anterior, usuario_novo = :novo, motivo = :motivo, origem = :origem, admin_id = :admin_id, data = NOW()");
                $params = [
                    ':aluno_id' => $alunoId,
                    ':anterior' => $usuarioAnterior,
                    ':novo' => $usuarioNovo,
                    ':motivo' => $motivo,
                    ':origem' => $origem,
                    ':admin_id' => $adminId,
                ];
            }
            $stmt->execute($params);
        } catch (Exception $e) {
            // Falha no log nao impede o fluxo
        }
    }
}

if (!function_exists('resolveAtendenteId')) {
    function resolveAtendenteId(PDO $pdo, array $responsavel, ?string $dataCadastro = null): int
    {
        $responsavelId = (int) ($responsavel['id'] ?? 0);
        if ($responsavelId <= 0) {
            return 0;
        }
        $nivel = $responsavel['nivel'] ?? '';
        if ($nivel !== 'Vendedor' && $nivel !== 'Parceiro') {
            return $responsavelId;
        }

        $tabela = ($nivel === 'Vendedor') ? 'vendedores' : 'parceiros';
        $hasTutorId = tableHasColumn($pdo, $tabela, 'tutor_id');
        $hasSecretarioId = tableHasColumn($pdo, $tabela, 'secretario_id');
        $columns = ['professor'];
        if ($hasTutorId) {
            $columns[] = 'tutor_id';
        }
        if ($hasSecretarioId) {
            $columns[] = 'secretario_id';
        }

        $stmt = $pdo->prepare("SELECT " . implode(', ', $columns) . " FROM {$tabela} WHERE id = :id");
        $stmt->execute([':id' => (int) ($responsavel['id_pessoa'] ?? 0)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return $responsavelId;
        }

        if ((int) ($row['professor'] ?? 0) !== 1) {
            return $responsavelId;
        }

        // Para responsavel com Professor marcado, o atendente operacional
        // precisa ser Tutor ou Secretario ativo; nunca o proprio vendedor/parceiro.
        if (!$hasTutorId && !$hasSecretarioId) {
            return 0;
        }

        $secretarioId = $hasSecretarioId ? (int) ($row['secretario_id'] ?? 0) : 0;
        if ($secretarioId > 0) {
            $stmtSec = $pdo->prepare("SELECT id FROM usuarios WHERE nivel = 'Secretario' AND id_pessoa = :id_pessoa AND ativo = 'Sim' LIMIT 1");
            $stmtSec->execute([':id_pessoa' => $secretarioId]);
            $usuarioSec = (int) ($stmtSec->fetchColumn() ?: 0);
            if ($usuarioSec > 0) {
                return $usuarioSec;
            }
        }

        $tutorId = $hasTutorId ? (int) ($row['tutor_id'] ?? 0) : 0;
        if ($tutorId > 0) {
            $stmtTutor = $pdo->prepare("SELECT id FROM usuarios WHERE nivel = 'Tutor' AND id_pessoa = :id_pessoa AND ativo = 'Sim' LIMIT 1");
            $stmtTutor->execute([':id_pessoa' => $tutorId]);
            $usuarioTutor = (int) ($stmtTutor->fetchColumn() ?: 0);
            if ($usuarioTutor > 0) {
                return $usuarioTutor;
            }
        }

        return 0;
    }
}

if (!function_exists('tableExists')) {
    function tableExists(PDO $pdo, string $table): bool
    {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        if ($table === '') {
            return false;
        }
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        return (bool) ($stmt && $stmt->fetch(PDO::FETCH_NUM));
    }
}

if (!function_exists('ensureAlunosResponsavelColumn')) {
    function ensureAlunosResponsavelColumn(PDO $pdo): bool
    {
        if (!tableHasColumn($pdo, 'alunos', 'responsavel_id')) {
            try {
                $pdo->exec("ALTER TABLE alunos ADD COLUMN responsavel_id int(11) DEFAULT NULL");
            } catch (Exception $e) {
                return false;
            }
        }
        return tableHasColumn($pdo, 'alunos', 'responsavel_id');
    }
}

if (!function_exists('buscarUsuarioAlunoPorPessoaId')) {
    function buscarUsuarioAlunoPorPessoaId(PDO $pdo, int $alunoPessoaId): int
    {
        if ($alunoPessoaId <= 0) {
            return 0;
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id_pessoa = :id_pessoa AND nivel = 'Aluno' LIMIT 1");
            $stmt->execute([':id_pessoa' => $alunoPessoaId]);
            return (int) ($stmt->fetchColumn() ?: 0);
        } catch (Exception $e) {
            return 0;
        }
    }
}

if (!function_exists('buscarIdsUsuariosMesmaPessoa')) {
    function buscarIdsUsuariosMesmaPessoa(PDO $pdo, int $usuarioId): array
    {
        if ($usuarioId <= 0) {
            return [];
        }

        try {
            $stmtPessoa = $pdo->prepare("SELECT id_pessoa FROM usuarios WHERE id = :id LIMIT 1");
            $stmtPessoa->execute([':id' => $usuarioId]);
            $idPessoa = (int) ($stmtPessoa->fetchColumn() ?: 0);
            if ($idPessoa <= 0) {
                return [$usuarioId];
            }

            $stmtUsuarios = $pdo->prepare("SELECT id FROM usuarios WHERE id_pessoa = :id_pessoa");
            $stmtUsuarios->execute([':id_pessoa' => $idPessoa]);
            $ids = array_map('intval', $stmtUsuarios->fetchAll(PDO::FETCH_COLUMN, 0));
            if (!in_array($usuarioId, $ids, true)) {
                $ids[] = $usuarioId;
            }
            $ids = array_values(array_unique(array_filter($ids)));
            return $ids;
        } catch (Exception $e) {
            return [$usuarioId];
        }
    }
}

if (!function_exists('buscarResponsavelAlunoId')) {
    function buscarResponsavelAlunoId(PDO $pdo, int $alunoPessoaId): int
    {
        if ($alunoPessoaId <= 0) {
            return 0;
        }

        ensureAlunosResponsavelColumn($pdo);
        $stmt = $pdo->prepare("SELECT usuario, responsavel_id FROM alunos WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $alunoPessoaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $responsavel = (int) ($row['responsavel_id'] ?? 0);
        if ($responsavel > 0) {
            return $responsavel;
        }
        return (int) ($row['usuario'] ?? 0);
    }
}

if (!function_exists('buscarResponsavelAluno')) {
    function buscarResponsavelAluno(PDO $pdo, int $alunoPessoaId): ?array
    {
        $responsavelId = buscarResponsavelAlunoId($pdo, $alunoPessoaId);
        if ($responsavelId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare("SELECT id, nome, nivel, id_pessoa FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $responsavelId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('responsavelEhProfessor')) {
    function responsavelEhProfessor(PDO $pdo, array $responsavel): bool
    {
        $nivel = $responsavel['nivel'] ?? '';
        if (!in_array($nivel, ['Vendedor', 'Parceiro'], true)) {
            return false;
        }

        $idPessoa = (int) ($responsavel['id_pessoa'] ?? 0);
        if ($idPessoa <= 0) {
            return false;
        }

        $tabela = $nivel === 'Vendedor' ? 'vendedores' : 'parceiros';
        try {
            $stmt = $pdo->prepare("SELECT professor FROM {$tabela} WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $idPessoa]);
            return (int) ($stmt->fetchColumn() ?: 0) === 1;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('ensureUsuariosVinculosTable')) {
    function ensureUsuariosVinculosTable(PDO $pdo): bool
    {
        if (tableExists($pdo, 'usuarios_vinculos')) {
            return true;
        }

        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios_vinculos (
                id int(11) NOT NULL AUTO_INCREMENT,
                usuario_vendedor_id int(11) NOT NULL,
                usuario_aluno_id int(11) NOT NULL,
                criado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                atualizado_em datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_usuario_vendedor (usuario_vendedor_id),
                UNIQUE KEY uniq_usuario_aluno (usuario_aluno_id),
                KEY idx_usuario_aluno (usuario_aluno_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Exception $e) {
            return false;
        }

        return tableExists($pdo, 'usuarios_vinculos');
    }
}

if (!function_exists('salvarVinculoVendedorAluno')) {
    function salvarVinculoVendedorAluno(PDO $pdo, int $usuarioVendedorId, int $usuarioAlunoId): bool
    {
        if ($usuarioVendedorId <= 0 || $usuarioAlunoId <= 0) {
            return false;
        }
        if (!ensureUsuariosVinculosTable($pdo)) {
            return false;
        }

        try {
            $stmtVendedor = $pdo->prepare("SELECT nivel, ativo FROM usuarios WHERE id = :id LIMIT 1");
            $stmtVendedor->execute([':id' => $usuarioVendedorId]);
            $vendedor = $stmtVendedor->fetch(PDO::FETCH_ASSOC) ?: [];

            $stmtAluno = $pdo->prepare("SELECT nivel, ativo FROM usuarios WHERE id = :id LIMIT 1");
            $stmtAluno->execute([':id' => $usuarioAlunoId]);
            $aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC) ?: [];

            if (($vendedor['nivel'] ?? '') !== 'Vendedor' || ($aluno['nivel'] ?? '') !== 'Aluno') {
                return false;
            }
            if (($vendedor['ativo'] ?? '') !== 'Sim' || ($aluno['ativo'] ?? '') !== 'Sim') {
                return false;
            }

            $pdo->beginTransaction();
            $stmtDelete = $pdo->prepare("DELETE FROM usuarios_vinculos WHERE usuario_vendedor_id = :vend OR usuario_aluno_id = :aluno");
            $stmtDelete->execute([
                ':vend' => $usuarioVendedorId,
                ':aluno' => $usuarioAlunoId,
            ]);

            $stmtInsert = $pdo->prepare("INSERT INTO usuarios_vinculos (usuario_vendedor_id, usuario_aluno_id) VALUES (:vend, :aluno)");
            $stmtInsert->execute([
                ':vend' => $usuarioVendedorId,
                ':aluno' => $usuarioAlunoId,
            ]);
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }
}

if (!function_exists('tentarVinculoVendedorAlunoPorCpf')) {
    function tentarVinculoVendedorAlunoPorCpf(PDO $pdo, string $cpf): bool
    {
        $cpfDigits = digitsOnly($cpf);
        if ($cpfDigits === '') {
            return false;
        }

        try {
            $cpfColumn = cleanCpfColumn('cpf');
            $stmtVendedores = $pdo->prepare("SELECT id FROM usuarios WHERE nivel = 'Vendedor' AND ativo = 'Sim' AND {$cpfColumn} = :cpf");
            $stmtVendedores->execute([':cpf' => $cpfDigits]);
            $vendedores = array_map('intval', $stmtVendedores->fetchAll(PDO::FETCH_COLUMN, 0) ?: []);

            $stmtAlunos = $pdo->prepare("SELECT id FROM usuarios WHERE nivel = 'Aluno' AND ativo = 'Sim' AND {$cpfColumn} = :cpf");
            $stmtAlunos->execute([':cpf' => $cpfDigits]);
            $alunos = array_map('intval', $stmtAlunos->fetchAll(PDO::FETCH_COLUMN, 0) ?: []);

            if (count($vendedores) !== 1 || count($alunos) !== 1) {
                return false;
            }

            return salvarVinculoVendedorAluno($pdo, $vendedores[0], $alunos[0]);
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('buscarUsuarioAlunoVinculadoPorVendedor')) {
    function buscarUsuarioAlunoVinculadoPorVendedor(PDO $pdo, int $usuarioVendedorId): int
    {
        if ($usuarioVendedorId <= 0) {
            return 0;
        }

        if (ensureUsuariosVinculosTable($pdo)) {
            try {
                $stmt = $pdo->prepare("SELECT usuario_aluno_id FROM usuarios_vinculos WHERE usuario_vendedor_id = :id LIMIT 1");
                $stmt->execute([':id' => $usuarioVendedorId]);
                $vinculado = (int) ($stmt->fetchColumn() ?: 0);
                if ($vinculado > 0) {
                    return $vinculado;
                }
            } catch (Exception $e) {
                // fallback por CPF
            }
        }

        try {
            $stmtVendedor = $pdo->prepare("SELECT cpf FROM usuarios WHERE id = :id AND nivel = 'Vendedor' LIMIT 1");
            $stmtVendedor->execute([':id' => $usuarioVendedorId]);
            $cpfDigits = digitsOnly((string) ($stmtVendedor->fetchColumn() ?: ''));
            if ($cpfDigits === '') {
                return 0;
            }

            $cpfColumn = cleanCpfColumn('cpf');
            $stmtAluno = $pdo->prepare("SELECT id FROM usuarios WHERE nivel = 'Aluno' AND ativo = 'Sim' AND {$cpfColumn} = :cpf ORDER BY id DESC");
            $stmtAluno->execute([':cpf' => $cpfDigits]);
            $alunos = array_map('intval', $stmtAluno->fetchAll(PDO::FETCH_COLUMN, 0) ?: []);

            if (count($alunos) === 1) {
                $alunoId = $alunos[0];
                salvarVinculoVendedorAluno($pdo, $usuarioVendedorId, $alunoId);
                return $alunoId;
            }
        } catch (Exception $e) {
            return 0;
        }

        return 0;
    }
}

if (!function_exists('buscarUsuarioVendedorVinculadoPorAluno')) {
    function buscarUsuarioVendedorVinculadoPorAluno(PDO $pdo, int $usuarioAlunoId): int
    {
        if ($usuarioAlunoId <= 0) {
            return 0;
        }

        if (ensureUsuariosVinculosTable($pdo)) {
            try {
                $stmt = $pdo->prepare("SELECT usuario_vendedor_id FROM usuarios_vinculos WHERE usuario_aluno_id = :id LIMIT 1");
                $stmt->execute([':id' => $usuarioAlunoId]);
                $vinculado = (int) ($stmt->fetchColumn() ?: 0);
                if ($vinculado > 0) {
                    return $vinculado;
                }
            } catch (Exception $e) {
                // fallback por CPF
            }
        }

        try {
            $stmtAluno = $pdo->prepare("SELECT cpf FROM usuarios WHERE id = :id AND nivel = 'Aluno' LIMIT 1");
            $stmtAluno->execute([':id' => $usuarioAlunoId]);
            $cpfDigits = digitsOnly((string) ($stmtAluno->fetchColumn() ?: ''));
            if ($cpfDigits === '') {
                return 0;
            }

            $cpfColumn = cleanCpfColumn('cpf');
            $stmtVendedor = $pdo->prepare("SELECT id FROM usuarios WHERE nivel = 'Vendedor' AND ativo = 'Sim' AND {$cpfColumn} = :cpf ORDER BY id DESC");
            $stmtVendedor->execute([':cpf' => $cpfDigits]);
            $vendedores = array_map('intval', $stmtVendedor->fetchAll(PDO::FETCH_COLUMN, 0) ?: []);

            if (count($vendedores) === 1) {
                $vendedorId = $vendedores[0];
                salvarVinculoVendedorAluno($pdo, $vendedorId, $usuarioAlunoId);
                return $vendedorId;
            }
        } catch (Exception $e) {
            return 0;
        }

        return 0;
    }
}

if (!function_exists('mergeEarlierDate')) {
    function mergeEarlierDate(array &$map, int $matriculaId, string $data): void
    {
        if ($matriculaId <= 0) {
            return;
        }
        $dataNorm = normalizeDate($data);
        if ($dataNorm === '') {
            return;
        }
        if (!isset($map[$matriculaId]) || $map[$matriculaId] === '' || $dataNorm < $map[$matriculaId]) {
            $map[$matriculaId] = $dataNorm;
        }
    }
}

if (!function_exists('carregarDatasPagamentoPorMatricula')) {
    function carregarDatasPagamentoPorMatricula(PDO $pdo, array $matriculaIds): array
    {
        $matriculaIds = array_values(array_unique(array_map('intval', $matriculaIds)));
        if (empty($matriculaIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($matriculaIds), '?'));
        $datas = [];

        if (tableExists($pdo, 'pagamentos_boleto') && tableHasColumn($pdo, 'pagamentos_boleto', 'id_matricula')) {
            $colData = tableHasColumn($pdo, 'pagamentos_boleto', 'data_pagamento') ? 'data_pagamento' :
                (tableHasColumn($pdo, 'pagamentos_boleto', 'criado_em') ? 'criado_em' :
                (tableHasColumn($pdo, 'pagamentos_boleto', 'data') ? 'data' : ''));
            if ($colData !== '') {
                $statusWhere = tableHasColumn($pdo, 'pagamentos_boleto', 'status')
                    ? "LOWER(COALESCE(status, '')) IN ('paid', 'pago')"
                    : '1=1';
                $sql = "SELECT id_matricula, MIN({$colData}) AS data_pag FROM pagamentos_boleto WHERE id_matricula IN ({$placeholders}) AND {$statusWhere} GROUP BY id_matricula";
                try {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($matriculaIds);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    foreach ($rows as $row) {
                        mergeEarlierDate($datas, (int) ($row['id_matricula'] ?? 0), (string) ($row['data_pag'] ?? ''));
                    }
                } catch (Exception $e) {
                    // Ignora falhas de tabela/coluna.
                }
            }
        }

        if (tableExists($pdo, 'parcelas_geradas_por_boleto') && tableHasColumn($pdo, 'parcelas_geradas_por_boleto', 'id_matricula')) {
            $colData = tableHasColumn($pdo, 'parcelas_geradas_por_boleto', 'data_pagamento') ? 'data_pagamento' :
                (tableHasColumn($pdo, 'parcelas_geradas_por_boleto', 'created_at') ? 'created_at' :
                (tableHasColumn($pdo, 'parcelas_geradas_por_boleto', 'data') ? 'data' : ''));
            if ($colData !== '') {
                $condicoes = [];
                if (tableHasColumn($pdo, 'parcelas_geradas_por_boleto', 'situacao')) {
                    $condicoes[] = 'situacao = 1';
                }
                if (tableHasColumn($pdo, 'parcelas_geradas_por_boleto', 'status')) {
                    $condicoes[] = "LOWER(COALESCE(status, '')) IN ('paid', 'pago')";
                }
                $wherePagamento = empty($condicoes) ? '1=1' : '(' . implode(' OR ', $condicoes) . ')';
                $sql = "SELECT id_matricula, MIN({$colData}) AS data_pag FROM parcelas_geradas_por_boleto WHERE id_matricula IN ({$placeholders}) AND {$wherePagamento} GROUP BY id_matricula";
                try {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($matriculaIds);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    foreach ($rows as $row) {
                        mergeEarlierDate($datas, (int) ($row['id_matricula'] ?? 0), (string) ($row['data_pag'] ?? ''));
                    }
                } catch (Exception $e) {
                    // Ignora falhas de tabela/coluna.
                }
            }
        }

        if (tableExists($pdo, 'pagamentos_pix') && tableHasColumn($pdo, 'pagamentos_pix', 'id_matricula')) {
            $colData = tableHasColumn($pdo, 'pagamentos_pix', 'data_atualizacao') ? 'data_atualizacao' :
                (tableHasColumn($pdo, 'pagamentos_pix', 'data_criacao') ? 'data_criacao' :
                (tableHasColumn($pdo, 'pagamentos_pix', 'data') ? 'data' : ''));
            if ($colData !== '') {
                $statusWhere = tableHasColumn($pdo, 'pagamentos_pix', 'status')
                    ? "(UPPER(COALESCE(status, '')) = 'CONCLUIDA' OR LOWER(COALESCE(status, '')) = 'paid')"
                    : '1=1';
                $sql = "SELECT id_matricula, MIN({$colData}) AS data_pag FROM pagamentos_pix WHERE id_matricula IN ({$placeholders}) AND {$statusWhere} GROUP BY id_matricula";
                try {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($matriculaIds);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    foreach ($rows as $row) {
                        mergeEarlierDate($datas, (int) ($row['id_matricula'] ?? 0), (string) ($row['data_pag'] ?? ''));
                    }
                } catch (Exception $e) {
                    // Ignora falhas de tabela/coluna.
                }
            }
        }

        return $datas;
    }
}

if (!function_exists('buscarMatriculasPagasNaoFinalizadasAntesCorte')) {
    function buscarMatriculasPagasNaoFinalizadasAntesCorte(PDO $pdo, int $alunoPessoaId, ?string $dataCorte = null): array
    {
        if ($alunoPessoaId <= 0) {
            return [];
        }

        $dataCorteNorm = normalizeDate((string) $dataCorte);
        if ($dataCorteNorm === '') {
            return [];
        }

        $alunoUsuarioId = buscarUsuarioAlunoPorPessoaId($pdo, $alunoPessoaId);
        if ($alunoUsuarioId <= 0) {
            return [];
        }

        $sql = "SELECT
                    m.id,
                    m.data,
                    m.status,
                    m.total_recebido,
                    CASE
                        WHEN m.pacote = 'Sim' THEN COALESCE(p.nome, CONCAT('Pacote #', m.id_curso))
                        ELSE COALESCE(c.nome, CONCAT('Curso #', m.id_curso))
                    END AS item_nome
                FROM matriculas m
                LEFT JOIN cursos c ON c.id = m.id_curso
                LEFT JOIN pacotes p ON p.id = m.id_curso
                WHERE m.aluno = :aluno
                  AND LOWER(COALESCE(m.status, '')) <> 'finalizado'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':aluno' => $alunoUsuarioId]);
        $matriculas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (empty($matriculas)) {
            return [];
        }

        $ids = array_map(static function ($row) {
            return (int) ($row['id'] ?? 0);
        }, $matriculas);
        $datasPagamento = carregarDatasPagamentoPorMatricula($pdo, $ids);

        $bloqueios = [];
        foreach ($matriculas as $matricula) {
            $matriculaId = (int) ($matricula['id'] ?? 0);
            if ($matriculaId <= 0) {
                continue;
            }

            $status = strtolower(trim((string) ($matricula['status'] ?? '')));
            $totalRecebido = (float) ($matricula['total_recebido'] ?? 0);
            $temPagamentoBasico = ($status !== '' && $status !== 'aguardando') || $totalRecebido > 0;

            $dataPagamento = $datasPagamento[$matriculaId] ?? '';
            if ($dataPagamento === '' && $temPagamentoBasico) {
                $dataPagamento = normalizeDate((string) ($matricula['data'] ?? ''));
            }

            if ($dataPagamento === '' || $dataPagamento > $dataCorteNorm) {
                continue;
            }

            $bloqueios[] = [
                'id_matricula' => $matriculaId,
                'item_nome' => (string) ($matricula['item_nome'] ?? ''),
                'data_pagamento' => $dataPagamento,
            ];
        }

        return $bloqueios;
    }
}

if (!function_exists('podeTrocarAtendente')) {
    function podeTrocarAtendente(PDO $pdo, int $alunoPessoaId, int $novoAtendenteId = 0, ?string $dataRef = null): array
    {
        if ($alunoPessoaId <= 0) {
            return [
                'permitido' => false,
                'bloqueado' => true,
                'mensagem' => 'Aluno invalido.',
                'matriculas' => [],
            ];
        }

        $dataCorte = getConfigDateCorteAtendente($pdo);
        if ($dataCorte === '') {
            return [
                'permitido' => true,
                'bloqueado' => false,
                'mensagem' => '',
                'matriculas' => [],
            ];
        }

        $stmtAluno = $pdo->prepare("SELECT data FROM alunos WHERE id = :id LIMIT 1");
        $stmtAluno->execute([':id' => $alunoPessoaId]);
        $dataCadastro = normalizeDate((string) ($stmtAluno->fetchColumn() ?: ''));
        if ($dataCadastro === '' || $dataCadastro >= $dataCorte) {
            return [
                'permitido' => true,
                'bloqueado' => false,
                'mensagem' => '',
                'matriculas' => [],
            ];
        }

        $bloqueios = buscarMatriculasPagasNaoFinalizadasAntesCorte($pdo, $alunoPessoaId, $dataCorte);
        if (empty($bloqueios)) {
            return [
                'permitido' => true,
                'bloqueado' => false,
                'mensagem' => '',
                'matriculas' => [],
            ];
        }

        $itens = [];
        foreach ($bloqueios as $idx => $item) {
            if ($idx >= 3) {
                break;
            }
            $idMat = (int) ($item['id_matricula'] ?? 0);
            $nomeItem = trim((string) ($item['item_nome'] ?? ''));
            $itens[] = $nomeItem !== '' ? ('#' . $idMat . ' - ' . $nomeItem) : ('#' . $idMat);
        }

        $mensagem = 'Troca bloqueada: aluno matriculado antes da data de corte com pagamento confirmado antes da data de corte.';
        if (!empty($itens)) {
            $mensagem .= ' Itens: ' . implode('; ', $itens) . '.';
        }

        return [
            'permitido' => false,
            'bloqueado' => true,
            'mensagem' => $mensagem,
            'matriculas' => $bloqueios,
        ];
    }
}


