<?php

declare(strict_types=1);

function log_access(?int $userId, string $path): void
{
    $stmt = db()->prepare('INSERT INTO access_logs (user_id, path, ip_address, user_agent) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        $userId,
        $path,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255)
    ]);
}

function log_access_if_possible(?int $userId): void
{
    try {
        log_access($userId, $_SERVER['REQUEST_URI'] ?? '/');
    } catch (Throwable $error) {
        // Ignore logging failures during setup.
    }
}
