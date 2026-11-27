<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/api/agendamento.php';

function verificar(bool $condicao, string $mensagem): void
{
    if (!$condicao) {
        throw new RuntimeException($mensagem);
    }
}

function criarBancoEmMemoria(): PDO
{
    return new PDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
}

function payloadValido(): array
{
    return [
        'nome' => 'Maria da Silva',
        'email' => 'maria@example.com',
        'telefone' => '(11) 99999-9999',
        'area_atuacao' => 'Direito Civil',
        'mensagem' => 'Preciso de orientação sobre um contrato.',
    ];
}

function contarConsultas(PDO $pdo): int
{
    inicializarBancoAgendamentos($pdo);
    return (int) $pdo->query('SELECT COUNT(*) FROM consultas')->fetchColumn();
}

function executarTeste(string $nome, callable $teste): bool
{
    try {
        $teste();
        echo "[PASS] {$nome}\n";
        return true;
    } catch (Throwable $exception) {
        echo "[FAIL] {$nome}: {$exception->getMessage()}\n";
        return false;
    }
}

$testes = [
    'Agendamento válido (201), sanitização e hash de IP' => static function (): void {
        $pdo = criarBancoEmMemoria();
        $ipCliente = '203.0.113.42';
        $payload = payloadValido();
        $payload['nome'] = ' <strong>Maria da Silva</strong> ';
        $payload['email'] = ' maria@example.com ';
        $payload['mensagem'] = ' <p>Preciso de orientação sobre um contrato.</p> ';

        $resultado = processarAgendamento($payload, $pdo, $ipCliente);
        verificar($resultado['status'] === 201, 'Esperava status 201.');
        verificar(contarConsultas($pdo) === 1, 'A solicitação não foi persistida.');

        $registro = $pdo->query(
            'SELECT nome, email, telefone, area_atuacao, mensagem, ip_hash
             FROM consultas LIMIT 1'
        )->fetch(PDO::FETCH_ASSOC);
        verificar($registro !== false, 'O registro não foi encontrado.');
        verificar($registro['nome'] === 'Maria da Silva', 'O nome não foi sanitizado corretamente.');
        verificar($registro['email'] === 'maria@example.com', 'O e-mail não foi normalizado.');
        verificar(
            $registro['mensagem'] === 'Preciso de orientação sobre um contrato.',
            'A mensagem não foi sanitizada corretamente.'
        );
        verificar($registro['ip_hash'] === hash('sha256', $ipCliente), 'O hash SHA-256 do IP está incorreto.');
        verificar($registro['ip_hash'] !== $ipCliente, 'O IP puro foi armazenado.');
    },
    'Validação (422) sem persistência' => static function (): void {
        $pdo = criarBancoEmMemoria();
        $payload = [
            'nome' => 'A',
            'email' => 'email-invalido',
            'telefone' => '123',
            'area_atuacao' => 'Direito Penal',
            'mensagem' => '',
        ];

        $resultado = processarAgendamento($payload, $pdo, '203.0.113.43');
        verificar($resultado['status'] === 422, 'Esperava status 422.');
        verificar(
            array_keys($resultado['body']['errors']) === ['nome', 'email', 'telefone', 'area_atuacao', 'mensagem'],
            'A resposta não indicou todos os campos inválidos.'
        );
        verificar(contarConsultas($pdo) === 0, 'Um payload inválido foi persistido.');
    },
    'Honeypot bloqueia bots sem gravar consultas' => static function (): void {
        $pdo = criarBancoEmMemoria();
        inicializarBancoAgendamentos($pdo);
        $payload = payloadValido();
        $payload['website'] = 'https://bot.example';

        $resultado = processarAgendamento($payload, $pdo, '203.0.113.44');
        verificar($resultado['status'] === 202, 'Esperava rejeição silenciosa com status 202.');
        verificar(contarConsultas($pdo) === 0, 'O honeypot gravou uma consulta.');
    },
    'Rate limit bloqueia o quarto envio (429)' => static function (): void {
        $pdo = criarBancoEmMemoria();
        $payload = payloadValido();
        $ipCliente = '203.0.113.45';

        for ($envio = 1; $envio <= 3; $envio++) {
            $resultado = processarAgendamento($payload, $pdo, $ipCliente);
            verificar($resultado['status'] === 201, "O envio {$envio} deveria retornar 201.");
        }

        $resultado = processarAgendamento($payload, $pdo, $ipCliente);
        verificar($resultado['status'] === 429, 'O quarto envio deveria retornar 429.');
        verificar(contarConsultas($pdo) === 3, 'O envio bloqueado foi persistido.');
    },
];

$falhas = 0;
foreach ($testes as $nome => $teste) {
    if (!executarTeste($nome, $teste)) {
        $falhas++;
    }
}

if ($falhas > 0) {
    fwrite(STDERR, "{$falhas} teste(s) falharam.\n");
    exit(1);
}

echo 'Todos os ' . count($testes) . " testes passaram.\n";
