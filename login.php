<?php
require_once __DIR__ . '/bootstrap.php';

try {
    $input = readJsonPayload();

    if (empty($input['email']) || empty($input['password'])) {
        throw new RuntimeException('Email and password are required.', 400);
    }

    $email = filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL);
    $password = trim($input['password']);

    if (!$email) {
        throw new RuntimeException('Please provide a valid email.', 400);
    }

    $pdo = getPdo();
    $stmt = $pdo->prepare(
        'SELECT id, first_name, last_name, email, password_hash 
         FROM users 
         WHERE email = ? 
         LIMIT 1'
    );
    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        throw new RuntimeException('Invalid email or password.', 401);
    }

    $sessionToken = createSession($user['email']);

    sendJson([
        'success' => true,
        'message' => 'Login successful.',
        'sessionToken' => $sessionToken,
        'email' => $user['email'],
        'issuedAt' => time(),
    ]);
} catch (Throwable $e) {
    $status = $e->getCode() ?: 401;
    sendJson(
        ['success' => false, 'message' => $e->getMessage()],
        $status
    );
}
