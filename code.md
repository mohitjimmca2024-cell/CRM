# Small CRM - Full Application Code

This document contains all project source files for SmallCRM.

## README.md

```markdown
# Small CRM (PHP + MySQL)

A compact CRM-style system that focuses on user interaction and admin management. It includes user registration, profile management, ticketing, quote requests, and an admin panel with dashboards, logs, and status controls.

## Features

User Module:
- User registration and login
- Profile management
- Request a quote
- Ticketing system
- Change password

Admin Panel:
- Dynamic dashboard with visit graph
- Manage users
- Manage tickets
- Manage quotes
- User access logs

## Tech

- Backend: PHP (PDO), MySQL
- Frontend: HTML, CSS, JavaScript, jQuery
- Optional: Chart.js for the visit graph

## Setup

1) Create database and tables

Import [setup.sql](setup.sql) into MySQL. It creates the database `small_crm`, tables, and a default admin user.

2) Configure database

Update DB settings in [includes/config.php](includes/config.php).

3) Run locally

From this folder:

```bash
php -S localhost:8000 -t public
```

Open:

- http://localhost:8000
- http://localhost:8000/admin

## Default admin login

- Email: admin@crm.local
- Password: admin123

## Notes

- Access logs are recorded for each page request.
- Passwords are stored using `password_hash`.
- This is a starter project designed for learning and extension.
```

## setup.sql

```sql
CREATE DATABASE IF NOT EXISTS small_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE small_crm;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE profiles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  company VARCHAR(160) DEFAULT '',
  phone VARCHAR(60) DEFAULT '',
  location VARCHAR(120) DEFAULT '',
  bio TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tickets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  subject VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('open','in_progress','closed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_tickets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE quotes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  service VARCHAR(180) NOT NULL,
  details TEXT NOT NULL,
  budget VARCHAR(80) DEFAULT '',
  status ENUM('new','reviewing','approved','rejected') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_quotes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE access_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  path VARCHAR(255) NOT NULL,
  ip_address VARCHAR(64) NOT NULL,
  user_agent VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_logs_created (created_at),
  CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO users (name, email, password_hash, role)
VALUES ('Admin User', 'admin@crm.local', '$2y$12$n3pn7avr4QUGZiccTPZ0zO0hT7WxfHnjqcRcf6uyoe1EOVc2mw/Vu', 'admin');

INSERT INTO profiles (user_id, company, phone, location, bio)
VALUES (1, 'Small CRM', '', '', 'Default admin profile.');
```

## includes/config.php

```php
<?php

declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'small_crm';
const DB_USER = 'root';
const DB_PASS = '';

const APP_NAME = 'Small CRM';
const BASE_URL = 'http://localhost:8000';
```

## includes/db.php

```php
<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    return $pdo;
}
```

## includes/functions.php

```php
<?php

declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}
```

## includes/auth.php

```php
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
```

## includes/logger.php

```php
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
```

## includes/layout.php

```php
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
```

## includes/bootstrap.php

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/layout.php';

date_default_timezone_set('UTC');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

## public/index.php

```php
<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$user = current_user();
log_access_if_possible($user['id'] ?? null);

render_header('Welcome');
?>

<section class="card">
  <h1>Small CRM System</h1>
  <p>Manage user interactions, track quotes, and handle support tickets from one simple dashboard.</p>
  <div style="margin-top: 16px;">
    <?php if ($user): ?>
      <a class="button" href="/dashboard.php">Go to Dashboard</a>
    <?php else: ?>
      <a class="button" href="/register.php">Create an account</a>
      <a class="button" href="/login.php" style="margin-left: 8px;">Login</a>
    <?php endif; ?>
  </div>
</section>

<section class="grid">
  <div class="card">
    <h3>User Module</h3>
    <ul>
      <li>Registration and login</li>
      <li>Profile management</li>
      <li>Quote requests</li>
      <li>Ticketing system</li>
    </ul>
  </div>
  <div class="card">
    <h3>Admin Panel</h3>
    <ul>
      <li>Dashboard overview</li>
      <li>Manage users</li>
      <li>Manage tickets and quotes</li>
      <li>User access logs</li>
    </ul>
  </div>
</section>

<?php
render_footer();
```

## public/register.php

```php
<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$user = current_user();
if ($user) {
    redirect('/dashboard.php');
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already registered.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, $hash]);
        $userId = (int) db()->lastInsertId();

        $profileStmt = db()->prepare('INSERT INTO profiles (user_id) VALUES (?)');
        $profileStmt->execute([$userId]);

        login_user([
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'role' => 'user'
        ]);

        set_flash('success', 'Welcome! Your account is ready.');
        redirect('/dashboard.php');
    }
}

log_access_if_possible(null);
render_header('Register');
?>

<div class="card">
  <h2>Create account</h2>
  <?php if ($errors): ?>
    <div class="flash error">
      <?php echo e(implode(' ', $errors)); ?>
    </div>
  <?php endif; ?>
  <form method="post">
    <div class="field">
      <label for="name">Full name</label>
      <input id="name" name="name" value="<?php echo e($name); ?>" required>
    </div>
    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" value="<?php echo e($email); ?>" required>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" required>
    </div>
    <button type="submit">Register</button>
  </form>
  <p style="margin-top: 12px;">Already have an account? <a href="/login.php">Login</a></p>
</div>

<?php
render_footer();
```

## public/login.php

```php
<?php

require_once __DIR__ . '/../includes/bootstrap.php';

if (current_user()) {
    redirect('/dashboard.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $error = 'Invalid email or password.';
    } else {
        login_user($user);
        set_flash('success', 'Welcome back.');
        redirect('/dashboard.php');
    }
}

log_access_if_possible(null);
render_header('Login');
?>

<div class="card">
  <h2>Login</h2>
  <?php if ($error): ?>
    <div class="flash error"><?php echo e($error); ?></div>
  <?php endif; ?>
  <form method="post">
    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" value="<?php echo e($email); ?>" required>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" required>
    </div>
    <button type="submit">Login</button>
  </form>
  <p style="margin-top: 12px;">No account yet? <a href="/register.php">Register</a></p>
</div>

<?php
render_footer();
```

## public/logout.php

```php
<?php

require_once __DIR__ . '/../includes/bootstrap.php';

logout_user();
set_flash('success', 'You have been logged out.');
redirect('/');
```

## public/dashboard.php

```php
<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();
$user = current_user();
log_access_if_possible($user['id']);

$ticketStmt = db()->prepare('SELECT status, COUNT(*) AS total FROM tickets WHERE user_id = ? GROUP BY status');
$ticketStmt->execute([$user['id']]);
$ticketStats = $ticketStmt->fetchAll();

$quoteStmt = db()->prepare('SELECT status, COUNT(*) AS total FROM quotes WHERE user_id = ? GROUP BY status');
$quoteStmt->execute([$user['id']]);
$quoteStats = $quoteStmt->fetchAll();

$latestTickets = db()->prepare('SELECT subject, status, created_at FROM tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$latestTickets->execute([$user['id']]);

$latestQuotes = db()->prepare('SELECT service, status, created_at FROM quotes WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$latestQuotes->execute([$user['id']]);

render_header('Dashboard');
?>

<div class="grid">
  <div class="card">
    <h3>Ticket status</h3>
    <?php if (!$ticketStats): ?>
      <p>No tickets yet.</p>
    <?php else: ?>
      <ul>
        <?php foreach ($ticketStats as $row): ?>
          <li><?php echo e($row['status']); ?>: <?php echo e((string) $row['total']); ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <div class="card">
    <h3>Quote status</h3>
    <?php if (!$quoteStats): ?>
      <p>No quotes yet.</p>
    <?php else: ?>
      <ul>
        <?php foreach ($quoteStats as $row): ?>
          <li><?php echo e($row['status']); ?>: <?php echo e((string) $row['total']); ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<div class="grid">
  <div class="card">
    <h3>Recent tickets</h3>
    <ul>
      <?php foreach ($latestTickets as $ticket): ?>
        <li><?php echo e($ticket['subject']); ?> · <?php echo e($ticket['status']); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="card">
    <h3>Recent quotes</h3>
    <ul>
      <?php foreach ($latestQuotes as $quote): ?>
        <li><?php echo e($quote['service']); ?> · <?php echo e($quote['status']); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<?php
render_footer();
```

## public/profile.php

```php
<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();
$user = current_user();
log_access_if_possible($user['id']);

$stmt = db()->prepare('SELECT users.name, users.email, profiles.company, profiles.phone, profiles.location, profiles.bio FROM users LEFT JOIN profiles ON profiles.user_id = users.id WHERE users.id = ?');
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

if (!$profile) {
    set_flash('error', 'Profile not found.');
    redirect('/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if ($name === '') {
        set_flash('error', 'Name is required.');
    } else {
        $updateUser = db()->prepare('UPDATE users SET name = ? WHERE id = ?');
        $updateUser->execute([$name, $user['id']]);

        $updateProfile = db()->prepare('UPDATE profiles SET company = ?, phone = ?, location = ?, bio = ?, updated_at = NOW() WHERE user_id = ?');
        $updateProfile->execute([$company, $phone, $location, $bio, $user['id']]);

        login_user([
            'id' => $user['id'],
            'name' => $name,
            'email' => $user['email'],
            'role' => $user['role']
        ]);

        set_flash('success', 'Profile updated.');
        redirect('/profile.php');
    }
}

render_header('Profile');
?>

<div class="card">
  <h2>Profile</h2>
  <form method="post">
    <div class="field">
      <label for="name">Full name</label>
      <input id="name" name="name" value="<?php echo e($profile['name']); ?>" required>
    </div>
    <div class="field">
      <label>Email</label>
      <input value="<?php echo e($profile['email']); ?>" disabled>
    </div>
    <div class="field">
      <label for="company">Company</label>
      <input id="company" name="company" value="<?php echo e($profile['company'] ?? ''); ?>">
    </div>
    <div class="field">
      <label for="phone">Phone</label>
      <input id="phone" name="phone" value="<?php echo e($profile['phone'] ?? ''); ?>">
    </div>
    <div class="field">
      <label for="location">Location</label>
      <input id="location" name="location" value="<?php echo e($profile['location'] ?? ''); ?>">
    </div>
    <div class="field">
      <label for="bio">Bio</label>
      <textarea id="bio" name="bio" rows="4"><?php echo e($profile['bio'] ?? ''); ?></textarea>
    </div>
    <button type="submit">Save profile</button>
  </form>
</div>

<div class="card">
  <h3>Security</h3>
  <p>Update your password from the security page.</p>
  <a class="button" href="/change-password.php">Change password</a>
</div>

<?php
render_footer();
```

## public/request-quote.php

```php
<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();
$user = current_user();
log_access_if_possible($user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service = trim($_POST['service'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $budget = trim($_POST['budget'] ?? '');

    if ($service === '' || $details === '') {
        set_flash('error', 'Service and details are required.');
    } else {
        $stmt = db()->prepare('INSERT INTO quotes (user_id, service, details, budget) VALUES (?, ?, ?, ?)');
        $stmt->execute([$user['id'], $service, $details, $budget]);
        set_flash('success', 'Quote request submitted.');
        redirect('/request-quote.php');
    }
}

$quotes = db()->prepare('SELECT service, status, created_at FROM quotes WHERE user_id = ? ORDER BY created_at DESC');
$quotes->execute([$user['id']]);

render_header('Quotes');
?>

<div class="card">
  <h2>Request a quote</h2>
  <form method="post">
    <div class="field">
      <label for="service">Service</label>
      <input id="service" name="service" required>
    </div>
    <div class="field">
      <label for="details">Project details</label>
      <textarea id="details" name="details" rows="4" required></textarea>
    </div>
    <div class="field">
      <label for="budget">Budget (optional)</label>
      <input id="budget" name="budget">
    </div>
    <button type="submit">Submit request</button>
  </form>
</div>

<div class="card">
  <h3>Your quote requests</h3>
  <table class="table">
    <thead>
      <tr>
        <th>Service</th>
        <th>Status</th>
        <th>Submitted</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($quotes as $quote): ?>
        <tr>
          <td><?php echo e($quote['service']); ?></td>
          <td><span class="badge"><?php echo e($quote['status']); ?></span></td>
          <td><?php echo e($quote['created_at']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
render_footer();
```

## public/tickets.php

```php
<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();
$user = current_user();
log_access_if_possible($user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($subject === '' || $message === '') {
        set_flash('error', 'Subject and message are required.');
    } else {
        $stmt = db()->prepare('INSERT INTO tickets (user_id, subject, message) VALUES (?, ?, ?)');
        $stmt->execute([$user['id'], $subject, $message]);
        set_flash('success', 'Ticket submitted.');
        redirect('/tickets.php');
    }
}

$tickets = db()->prepare('SELECT subject, status, created_at FROM tickets WHERE user_id = ? ORDER BY created_at DESC');
$tickets->execute([$user['id']]);

render_header('Tickets');
?>

<div class="card">
  <h2>Create a ticket</h2>
  <form method="post">
    <div class="field">
      <label for="subject">Subject</label>
      <input id="subject" name="subject" required>
    </div>
    <div class="field">
      <label for="message">Message</label>
      <textarea id="message" name="message" rows="4" required></textarea>
    </div>
    <button type="submit">Submit ticket</button>
  </form>
</div>

<div class="card">
  <h3>Your tickets</h3>
  <table class="table">
    <thead>
      <tr>
        <th>Subject</th>
        <th>Status</th>
        <th>Submitted</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($tickets as $ticket): ?>
        <tr>
          <td><?php echo e($ticket['subject']); ?></td>
          <td><span class="badge"><?php echo e($ticket['status']); ?></span></td>
          <td><?php echo e($ticket['created_at']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
render_footer();
```

## public/change-password.php

```php
<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();
$user = current_user();
log_access_if_possible($user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new === '' || $new !== $confirm) {
        set_flash('error', 'New passwords do not match.');
    } else {
        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $record = $stmt->fetch();

        if (!$record || !password_verify($current, $record['password_hash'])) {
            set_flash('error', 'Current password is incorrect.');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $update = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $update->execute([$hash, $user['id']]);
            set_flash('success', 'Password updated.');
            redirect('/change-password.php');
        }
    }
}

render_header('Change password');
?>

<div class="card">
  <h2>Change password</h2>
  <form method="post">
    <div class="field">
      <label for="current_password">Current password</label>
      <input id="current_password" name="current_password" type="password" required>
    </div>
    <div class="field">
      <label for="new_password">New password</label>
      <input id="new_password" name="new_password" type="password" required>
    </div>
    <div class="field">
      <label for="confirm_password">Confirm new password</label>
      <input id="confirm_password" name="confirm_password" type="password" required>
    </div>
    <button type="submit">Update password</button>
  </form>
</div>

<?php
render_footer();
```

## public/404.php

```php
<?php

require_once __DIR__ . '/../includes/bootstrap.php';

render_header('Not found');
?>

<div class="card">
  <h2>Page not found</h2>
  <p>The page you are looking for does not exist.</p>
  <a class="button" href="/">Back to home</a>
</div>

<?php
render_footer();
```

## public/admin/index.php

```php
<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin();
$user = current_user();
log_access_if_possible($user['id']);

$totalUsers = db()->query('SELECT COUNT(*) AS total FROM users')->fetch();
$totalTickets = db()->query('SELECT COUNT(*) AS total FROM tickets')->fetch();
$totalQuotes = db()->query('SELECT COUNT(*) AS total FROM quotes')->fetch();

render_header('Admin dashboard', true);
?>

<div class="grid">
  <div class="card">
    <h3>Total users</h3>
    <p class="badge"><?php echo e((string) $totalUsers['total']); ?></p>
  </div>
  <div class="card">
    <h3>Total tickets</h3>
    <p class="badge"><?php echo e((string) $totalTickets['total']); ?></p>
  </div>
  <div class="card">
    <h3>Total quotes</h3>
    <p class="badge"><?php echo e((string) $totalQuotes['total']); ?></p>
  </div>
</div>

<div class="card">
  <h3>User visit graph</h3>
  <canvas id="visitsChart" height="90"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  $(function () {
    $.getJSON('/api/visits.php', function (data) {
      const ctx = document.getElementById('visitsChart').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: data.labels,
          datasets: [{
            label: 'Visits',
            data: data.values,
            borderColor: '#0f172a',
            backgroundColor: 'rgba(15, 23, 42, 0.1)',
            tension: 0.3,
            fill: true
          }]
        },
        options: {
          scales: {
            y: { beginAtZero: true }
          }
        }
      });
    });
  });
</script>

<?php
render_footer();
```

## public/admin/users.php

```php
<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin();
$admin = current_user();
log_access_if_possible($admin['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? 'user';

    if ($userId === $admin['id']) {
        set_flash('error', 'You cannot change your own role.');
    } else {
        $update = db()->prepare('UPDATE users SET role = ? WHERE id = ?');
        $update->execute([$role, $userId]);
        set_flash('success', 'User role updated.');
    }

    redirect('/admin/users.php');
}

$users = db()->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC');

render_header('Manage users', true);
?>

<div class="card">
  <h2>Users</h2>
  <table class="table">
    <thead>
      <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Created</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $row): ?>
        <tr>
          <td><?php echo e($row['name']); ?></td>
          <td><?php echo e($row['email']); ?></td>
          <td><span class="badge"><?php echo e($row['role']); ?></span></td>
          <td><?php echo e($row['created_at']); ?></td>
          <td>
            <?php if ((int) $row['id'] !== $admin['id']): ?>
              <form method="post" style="display:inline-block;">
                <input type="hidden" name="user_id" value="<?php echo e((string) $row['id']); ?>">
                <select name="role" onchange="this.form.submit()">
                  <option value="user" <?php echo $row['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                  <option value="admin" <?php echo $row['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
              </form>
            <?php else: ?>
              <span class="badge">Current</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
render_footer();
```

## public/admin/tickets.php

```php
<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin();
$admin = current_user();
log_access_if_possible($admin['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);
    $status = $_POST['status'] ?? 'open';

    $update = db()->prepare('UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?');
    $update->execute([$status, $ticketId]);

    set_flash('success', 'Ticket updated.');
    redirect('/admin/tickets.php');
}

$tickets = db()->query('SELECT tickets.id, tickets.subject, tickets.status, tickets.created_at, users.name FROM tickets JOIN users ON users.id = tickets.user_id ORDER BY tickets.created_at DESC');

render_header('Manage tickets', true);
?>

<div class="card">
  <h2>Tickets</h2>
  <table class="table">
    <thead>
      <tr>
        <th>User</th>
        <th>Subject</th>
        <th>Status</th>
        <th>Created</th>
        <th>Update</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($tickets as $ticket): ?>
        <tr>
          <td><?php echo e($ticket['name']); ?></td>
          <td><?php echo e($ticket['subject']); ?></td>
          <td><span class="badge"><?php echo e($ticket['status']); ?></span></td>
          <td><?php echo e($ticket['created_at']); ?></td>
          <td>
            <form method="post">
              <input type="hidden" name="ticket_id" value="<?php echo e((string) $ticket['id']); ?>">
              <select name="status" onchange="this.form.submit()">
                <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>In progress</option>
                <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
              </select>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
render_footer();
```

## public/admin/quotes.php

```php
<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin();
$admin = current_user();
log_access_if_possible($admin['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quoteId = (int) ($_POST['quote_id'] ?? 0);
    $status = $_POST['status'] ?? 'new';

    $update = db()->prepare('UPDATE quotes SET status = ?, updated_at = NOW() WHERE id = ?');
    $update->execute([$status, $quoteId]);

    set_flash('success', 'Quote updated.');
    redirect('/admin/quotes.php');
}

$quotes = db()->query('SELECT quotes.id, quotes.service, quotes.status, quotes.created_at, users.name FROM quotes JOIN users ON users.id = quotes.user_id ORDER BY quotes.created_at DESC');

render_header('Manage quotes', true);
?>

<div class="card">
  <h2>Quotes</h2>
  <table class="table">
    <thead>
      <tr>
        <th>User</th>
        <th>Service</th>
        <th>Status</th>
        <th>Created</th>
        <th>Update</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($quotes as $quote): ?>
        <tr>
          <td><?php echo e($quote['name']); ?></td>
          <td><?php echo e($quote['service']); ?></td>
          <td><span class="badge"><?php echo e($quote['status']); ?></span></td>
          <td><?php echo e($quote['created_at']); ?></td>
          <td>
            <form method="post">
              <input type="hidden" name="quote_id" value="<?php echo e((string) $quote['id']); ?>">
              <select name="status" onchange="this.form.submit()">
                <option value="new" <?php echo $quote['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                <option value="reviewing" <?php echo $quote['status'] === 'reviewing' ? 'selected' : ''; ?>>Reviewing</option>
                <option value="approved" <?php echo $quote['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo $quote['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
              </select>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
render_footer();
```

## public/admin/logs.php

```php
<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin();
$admin = current_user();
log_access_if_possible($admin['id']);

$logs = db()->query('SELECT access_logs.path, access_logs.ip_address, access_logs.user_agent, access_logs.created_at, users.name AS user_name FROM access_logs LEFT JOIN users ON users.id = access_logs.user_id ORDER BY access_logs.created_at DESC LIMIT 200');

render_header('Access logs', true);
?>

<div class="card">
  <h2>Access logs</h2>
  <table class="table">
    <thead>
      <tr>
        <th>User</th>
        <th>Path</th>
        <th>IP</th>
        <th>User agent</th>
        <th>Time</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($logs as $log): ?>
        <tr>
          <td><?php echo e($log['user_name'] ?? 'Guest'); ?></td>
          <td><?php echo e($log['path']); ?></td>
          <td><?php echo e($log['ip_address']); ?></td>
          <td><?php echo e($log['user_agent']); ?></td>
          <td><?php echo e($log['created_at']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
render_footer();
```

## public/api/visits.php

```php
<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin();

$start = new DateTimeImmutable('-6 days');
$labels = [];
$values = [];

for ($i = 0; $i < 7; $i++) {
    $day = $start->modify('+' . $i . ' days');
    $labels[$day->format('Y-m-d')] = 0;
}

$stmt = db()->prepare('SELECT DATE(created_at) AS day, COUNT(*) AS total FROM access_logs WHERE created_at >= ? GROUP BY day ORDER BY day');
$stmt->execute([$start->format('Y-m-d 00:00:00')]);
$rows = $stmt->fetchAll();

foreach ($rows as $row) {
    $labels[$row['day']] = (int) $row['total'];
}

foreach ($labels as $day => $total) {
    $values[] = $total;
}

header('Content-Type: application/json');
echo json_encode([
    'labels' => array_keys($labels),
    'values' => $values
]);
```

## public/assets/css/style.css

```css
:root {
  color: #0f172a;
  background: #f6f4f1;
}

* {
  box-sizing: border-box;
}

body {
  margin: 0;
  font-family: "Segoe UI", Arial, sans-serif;
  background: #f6f4f1;
  color: #0f172a;
}

.page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.site-header {
  background: #0f172a;
  color: #ffffff;
  padding: 16px 24px;
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  align-items: center;
  justify-content: space-between;
}

.site-header .brand {
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.site-header nav {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.site-header nav a {
  color: #ffffff;
  text-decoration: none;
  font-size: 14px;
}

.user-area {
  display: flex;
  gap: 12px;
  align-items: center;
  font-size: 14px;
}

.user-area .link {
  color: #fbbf24;
  text-decoration: none;
}

main {
  flex: 1;
  padding: 24px;
  max-width: 1000px;
  width: 100%;
  margin: 0 auto;
}

.card {
  background: #ffffff;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
  margin-bottom: 20px;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 16px;
}

.flash {
  margin: 16px auto;
  max-width: 1000px;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 14px;
}

.flash.error {
  background: #fee2e2;
  color: #991b1b;
}

.flash.success {
  background: #dcfce7;
  color: #166534;
}

form .field {
  display: flex;
  flex-direction: column;
  margin-bottom: 14px;
}

form label {
  font-size: 13px;
  margin-bottom: 6px;
  color: #475569;
}

form input,
form select,
form textarea {
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid #cbd5f5;
  font-size: 14px;
}

button,
.button {
  background: #0f172a;
  color: #ffffff;
  border: none;
  padding: 10px 18px;
  border-radius: 8px;
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
  font-size: 14px;
}

.table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.table th,
.table td {
  padding: 10px 12px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.badge {
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  display: inline-block;
  background: #e2e8f0;
}

.site-footer {
  text-align: center;
  padding: 16px;
  background: #0f172a;
  color: #ffffff;
  font-size: 12px;
}
```
