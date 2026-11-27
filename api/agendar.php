<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/agendamento.php';

function respond(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(405, ['message' => 'Método não permitido.']);
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || strlen($rawBody) > 20000) {
    respond(413, ['message' => 'A solicitação excede o tamanho permitido.']);
}

try {
    $payload = json_decode($rawBody, false, 512, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    respond(400, ['message' => 'O corpo da solicitação deve conter JSON válido.']);
}

if (!$payload instanceof stdClass) {
    respond(400, ['message' => 'O corpo da solicitação deve ser um objeto JSON.']);
}

$payload = (array) $payload;
if (agendamentoTemHoneypot($payload)) {
    respond(202, ['message' => 'Solicitação recebida.']);
}

$databaseDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database';
if (!is_dir($databaseDirectory) && !mkdir($databaseDirectory, 0700, true) && !is_dir($databaseDirectory)) {
    error_log('Não foi possível criar o diretório do banco de consultas.');
    respond(500, ['message' => 'Não foi possível processar sua solicitação agora.']);
}

$databasePath = $databaseDirectory . DIRECTORY_SEPARATOR . 'consultas.sqlite';
$ipAddress = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP);
$ipCliente = $ipAddress === false ? 'unknown' : $ipAddress;

try {
    $pdo = new PDO('sqlite:' . $databasePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $result = processarAgendamento($payload, $pdo, $ipCliente);

    if ($result['status'] === 429) {
        header('Retry-After: 600');
    }

    respond($result['status'], $result['body']);
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erro ao salvar solicitação de consulta: ' . $exception->getMessage());
    respond(500, ['message' => 'Não foi possível processar sua solicitação agora.']);
}
