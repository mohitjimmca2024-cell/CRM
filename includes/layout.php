<?php

declare(strict_types=1);

function render_header(string $title, bool $isAdmin = false): void
{
    $user = current_user();
    $flash = get_flash();
    $navLinks = $isAdmin
        ? [
            ['Dashboard', '/admin/index.php'],
            ['Users', '/admin/users.php'],
            ['Tickets', '/admin/tickets.php'],
            ['Quotes', '/admin/quotes.php'],
            ['Logs', '/admin/logs.php']
        ]
        : [
            ['Dashboard', '/dashboard.php'],
            ['Profile', '/profile.php'],
            ['Quotes', '/request-quote.php'],
            ['Tickets', '/tickets.php']
        ];

    echo '<!doctype html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . e($title) . ' · ' . e(APP_NAME) . '</title>';
    echo '<link rel="stylesheet" href="/assets/css/style.css">';
    echo '<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>';
    echo '</head>';
    echo '<body>';
    echo '<div class="page">';
    echo '<header class="site-header">';
    echo '<div class="brand">' . e(APP_NAME) . '</div>';
    echo '<nav>';
    foreach ($navLinks as $link) {
        echo '<a href="' . e($link[1]) . '">' . e($link[0]) . '</a>';
    }
    echo '</nav>';
    echo '<div class="user-area">';
    if ($user) {
        echo '<span>' . e($user['name']) . '</span>';
        echo '<a class="link" href="/logout.php">Logout</a>';
    } else {
        echo '<a class="link" href="/login.php">Login</a>';
    }
    echo '</div>';
    echo '</header>';
    if ($flash) {
        echo '<div class="flash ' . e($flash['type']) . '">' . e($flash['message']) . '</div>';
    }
    echo '<main>';
}

function render_footer(): void
{
    echo '</main>';
    echo '<footer class="site-footer">Small CRM · PHP & MySQL</footer>';
    echo '</div>';
    echo '</body>';
    echo '</html>';
}
