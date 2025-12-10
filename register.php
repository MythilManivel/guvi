<?php
require_once __DIR__ . '/bootstrap.php';

try {
    $input = readJsonPayload();

    $required = ['firstName', 'lastName', 'email', 'password', 'confirmPassword'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new RuntimeException('Missing ' . $field);
        }
    }

    if ($input['password'] !== $input['confirmPassword']) {
        throw new RuntimeException('Passwords must match.');
    }

    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new RuntimeException('Invalid email.');
    }

    $pdo = getPdo();
    $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new RuntimeException('Email already registered.');
    }

    $passwordHash = password_hash($input['password'], PASSWORD_DEFAULT);
    $insertStmt = $pdo->prepare(
        'INSERT INTO users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)'
    );
    $insertStmt->execute([
        $input['firstName'],
        $input['lastName'],
        $email,
        $passwordHash,
    ]);

    $usersCollection = getMongoClient()
        ->selectDatabase(MONGO_DB)
        ->selectCollection(MONGO_PROFILE_COLLECTION);
    $usersCollection->insertOne([
        'email' => $email,
        'profile' => [
            'age' => $input['age'] ?? null,
            'dob' => $input['dob'] ?? null,
            'contact' => $input['contact'] ?? null,
            'address' => '',
        ],
    ]);

    sendJson(['success' => true, 'message' => 'Registration successful.']);
} catch (Throwable $e) {
    sendJson(['success' => false, 'message' => $e->getMessage()]);
}
