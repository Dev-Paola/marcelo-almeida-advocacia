<?php
declare(strict_types=1);

function agendamentoTemHoneypot(array $payload): bool
{
    foreach ([$payload['website'] ?? '', $payload['company'] ?? ''] as $value) {
        if ((is_string($value) && trim($value) !== '') || (!is_string($value) && !empty($value))) {
            return true;
        }
    }

    return false;
}

function agendamentoLimparTexto(mixed $value): string
{
    return is_string($value) ? trim(strip_tags($value)) : '';
}

function agendamentoContarCaracteres(string $value): int
{
    $length = preg_match_all('/./us', $value);
    return $length === false ? strlen($value) : $length;
}

function inicializarBancoAgendamentos(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS consultas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            email TEXT NOT NULL,
            telefone TEXT NOT NULL,
            area_atuacao TEXT NOT NULL,
            mensagem TEXT NOT NULL,
            ip_hash TEXT NOT NULL,
            created_at TEXT NOT NULL
        )'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS rate_limits (
            ip_hash TEXT PRIMARY KEY,
            window_start INTEGER NOT NULL,
            request_count INTEGER NOT NULL
        )'
    );
}

/**
 * @return array{status: int, body: array<string, mixed>}
 */
function processarAgendamento(array $payload, PDO $pdo, string $ipCliente): array
{
    if (agendamentoTemHoneypot($payload)) {
        return [
            'status' => 202,
            'body' => ['message' => 'Solicitação recebida.'],
        ];
    }

    $areasPermitidas = [
        'Direito Empresarial',
        'Direito de Família',
        'Direito Civil',
    ];

    $dados = [
        'nome' => agendamentoLimparTexto($payload['nome'] ?? null),
        'email' => agendamentoLimparTexto($payload['email'] ?? null),
        'telefone' => agendamentoLimparTexto($payload['telefone'] ?? null),
        'area_atuacao' => agendamentoLimparTexto($payload['area_atuacao'] ?? null),
        'mensagem' => agendamentoLimparTexto($payload['mensagem'] ?? null),
    ];

    $erros = [];

    if (agendamentoContarCaracteres($dados['nome']) < 3 || agendamentoContarCaracteres($dados['nome']) > 120) {
        $erros['nome'] = 'Informe um nome com 3 a 120 caracteres.';
    }

    if (filter_var($dados['email'], FILTER_VALIDATE_EMAIL) === false) {
        $erros['email'] = 'Informe um endereço de e-mail válido.';
    }

    if (strlen((string) preg_replace('/\D+/', '', $dados['telefone'])) < 10) {
        $erros['telefone'] = 'Informe um telefone com pelo menos 10 dígitos.';
    }

    if (!in_array($dados['area_atuacao'], $areasPermitidas, true)) {
        $erros['area_atuacao'] = 'Selecione uma área de atuação válida.';
    }

    if (agendamentoContarCaracteres($dados['mensagem']) < 10 || agendamentoContarCaracteres($dados['mensagem']) > 2000) {
        $erros['mensagem'] = 'Informe um resumo com 10 a 2000 caracteres.';
    }

    inicializarBancoAgendamentos($pdo);

    $ipHash = hash('sha256', $ipCliente);
    $now = time();
    $createdAt = gmdate('Y-m-d\TH:i:s\Z');

    $pdo->beginTransaction();

    try {
        $expiredWindows = $pdo->prepare('DELETE FROM rate_limits WHERE window_start <= :cutoff');
        $expiredWindows->execute(['cutoff' => $now - 600]);

        $rateLimit = $pdo->prepare(
            'INSERT INTO rate_limits (ip_hash, window_start, request_count)
             VALUES (:ip_hash, :now, 1)
             ON CONFLICT(ip_hash) DO UPDATE SET
               window_start = CASE
                 WHEN rate_limits.window_start <= :reset_cutoff THEN excluded.window_start
                 ELSE rate_limits.window_start
               END,
               request_count = CASE
                 WHEN rate_limits.window_start <= :count_cutoff THEN 1
                 ELSE rate_limits.request_count + 1
               END'
        );
        $rateLimit->execute([
            'ip_hash' => $ipHash,
            'now' => $now,
            'reset_cutoff' => $now - 600,
            'count_cutoff' => $now - 600,
        ]);

        $countStatement = $pdo->prepare('SELECT request_count FROM rate_limits WHERE ip_hash = :ip_hash');
        $countStatement->execute(['ip_hash' => $ipHash]);
        $requestCount = (int) $countStatement->fetchColumn();

        if ($requestCount > 3) {
            $pdo->commit();
            return [
                'status' => 429,
                'body' => ['message' => 'Limite de solicitações atingido. Tente novamente em alguns minutos.'],
            ];
        }

        if ($erros !== []) {
            $pdo->commit();
            return [
                'status' => 422,
                'body' => [
                    'message' => 'Revise os campos indicados e tente novamente.',
                    'errors' => $erros,
                ],
            ];
        }

        $insert = $pdo->prepare(
            'INSERT INTO consultas
                (nome, email, telefone, area_atuacao, mensagem, ip_hash, created_at)
             VALUES
                (:nome, :email, :telefone, :area_atuacao, :mensagem, :ip_hash, :created_at)'
        );
        $insert->execute([
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'telefone' => $dados['telefone'],
            'area_atuacao' => $dados['area_atuacao'],
            'mensagem' => $dados['mensagem'],
            'ip_hash' => $ipHash,
            'created_at' => $createdAt,
        ]);

        $consultation = [
            'id' => (int) $pdo->lastInsertId(),
            ...$dados,
            'created_at' => $createdAt,
        ];
        $pdo->commit();

        return [
            'status' => 201,
            'body' => [
                'message' => 'Solicitação enviada. Entraremos em contato em breve.',
                'consultation' => $consultation,
            ],
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}
