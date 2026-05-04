# CRM (PHP + MySQL)

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
