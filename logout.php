<?php
require_once __DIR__ . '/bootstrap.php';

try {
    $token = getSessionTokenFromHeader();
    $redis = getRedis();
    $redis->del(getSessionKey($token));
    sendJson(['success' => true, 'message' => 'Logged out successfully.']);
} catch (Throwable $e) {
    sendJson(['success' => false, 'message' => $e->getMessage()], 400);
}
