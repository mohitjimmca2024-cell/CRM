<?php

declare(strict_types=1);

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function login_user(array $user): void
{
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    session_destroy();
}

function require_login(): void
{
    if (!current_user()) {
        set_flash('error', 'Please log in to continue.');
        redirect('/login.php');
    }
}

function require_admin(): void
{
    $user = current_user();
    if (!$user || $user['role'] !== 'admin') {
        set_flash('error', 'Admin access required.');
        redirect('/login.php');
    }
}
