<?php
require_once __DIR__ . '/config.php';

function getPdo()
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s', MYSQL_HOST, MYSQL_PORT, MYSQL_DB);
        $pdo = new PDO($dsn, MYSQL_USER, MYSQL_PASSWORD, PDO_OPTIONS);
    }
    return $pdo;
}

function getRedis()
{
    static $redis = null;
    if ($redis === null) {
        $redis = new Redis();
        $redis->connect(REDIS_HOST, REDIS_PORT);
    }
    return $redis;
}

function getMongoClient()
{
    static $client = null;
    if ($client === null) {
        $client = new MongoDB\Client(MONGO_URI);
    }
    return $client;
}

function readJsonPayload()
{
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        throw new RuntimeException('Invalid request payload.');
    }
    return $data;
}

function sendJson(array $payload, int $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function getSessionTokenFromHeader()
{
    $token = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? null;
    if (!$token) {
        throw new RuntimeException('Session token missing.');
    }
    return trim($token);
}

function getSessionKey(string $token): string
{
    return sprintf('session:%s', $token);
}

function getEmailFromSession(string $token): string
{
    $email = getRedis()->get(getSessionKey($token));
    if (!$email) {
        throw new RuntimeException('Session expired.');
    }
    return $email;
}

function createSession(string $email): string
{
    $token = bin2hex(random_bytes(32));
    getRedis()->setex(getSessionKey($token), REDIS_TTL, $email);
    return $token;
}

function refreshSession(string $token)
{
    $email = getEmailFromSession($token);
    getRedis()->setex(getSessionKey($token), REDIS_TTL, $email);
}
