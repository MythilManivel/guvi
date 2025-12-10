<?php
require_once __DIR__ . '/bootstrap.php';

try {
    $input = readJsonPayload();
    if (empty($input['email']) || empty($input['password'])) {
        throw new RuntimeException('Email and password are required.');
    }

    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new RuntimeException('Please provide a valid email.');
    }

    $pdo = getPdo();
    $stmt = $pdo->prepare('SELECT id, first_name, last_name, email, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($input['password'], $user['password_hash'])) {
        throw new RuntimeException('Invalid email or password.');
    }

    $token = createSession($user['email']);

    sendJson([ 
        'success' => true,
        'message' => 'Login successful.',
        'sessionToken' => $token,
        'email' => $user['email'],
    ]);
} catch (Throwable $e) {
    sendJson(['success' => false, 'message' => $e->getMessage()], 401);
}
