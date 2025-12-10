<?php
require_once __DIR__ . '/bootstrap.php';

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'GET') {
        $token = getSessionTokenFromHeader();
        $email = getEmailFromSession($token);

        $pdo = getPdo();
        $stmt = $pdo->prepare('SELECT first_name, last_name, email FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) {
            throw new RuntimeException('User record not found.');
        }

        $profileDoc = getMongoClient()
            ->selectDatabase(MONGO_DB)
            ->selectCollection(MONGO_PROFILE_COLLECTION)
            ->findOne(['email' => $email]);
        $profile = $profileDoc['profile'] ?? [];

        sendJson([
            'success' => true,
            'data' => [
                'firstName' => $user['first_name'],
                'lastName' => $user['last_name'],
                'email' => $user['email'],
                'profile' => $profile,
            ],
        ]);
    } elseif ($method === 'POST') {
        $token = getSessionTokenFromHeader();
        $email = getEmailFromSession($token);
        $payload = readJsonPayload();

        $firstName = trim($payload['firstName'] ?? '');
        $lastName = trim($payload['lastName'] ?? '');
        if ($firstName === '' || $lastName === '') {
            throw new RuntimeException('First name and last name are required.');
        }

        $pdo = getPdo();
        $updateStmt = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ? WHERE email = ?');
        $updateStmt->execute([$firstName, $lastName, $email]);

        $profileFields = [
            'age' => $payload['age'] ?? null,
            'dob' => $payload['dob'] ?? null,
            'contact' => $payload['contact'] ?? null,
            'address' => $payload['address'] ?? null,
        ];
        getMongoClient()
            ->selectDatabase(MONGO_DB)
            ->selectCollection(MONGO_PROFILE_COLLECTION)
            ->updateOne(
                ['email' => $email],
                ['$set' => ['profile' => $profileFields]],
                ['upsert' => true]
            );

        sendJson(['success' => true, 'message' => 'Profile updated successfully.']);
    } else {
        throw new RuntimeException('Method not allowed.');
    }
} catch (Throwable $e) {
    sendJson(['success' => false, 'message' => $e->getMessage()], 400);
}
