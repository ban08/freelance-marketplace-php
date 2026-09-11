# freelance-marketplace-php

A freelance-services marketplace, built from scratch in plain PHP with no framework, to learn how the web works underneath the abstractions.

## What it does

- **Two roles** — clients and freelancers, plus an admin.
- **Freelancers** create and manage service listings (categories, pricing, delivery time, image galleries), handle custom-order requests and mark orders complete.
- **Clients** browse and filter services, place and track orders through a simulated checkout, review completed services, and message freelancers.
- **Messaging** between clients and freelancers, tied to orders.
- **Admin panel** with users, categories and site statistics.

## Stack

Plain PHP (no framework), SQLite via PDO, a little JavaScript, HTML/CSS. Runs on the built-in PHP server.

## How to run

```bash
sqlite3 database.sqlite < db/database.sql   # create and seed the database
php -S localhost:8000                        # then open http://localhost:8000
```

## What I built

Group project of three for the Web Languages and Technologies course (2024/25). I did most of the application (15 of 19 commits): the database schema, login and registration, the client and freelancer feature sets, the order flow, and the front-end. Teammates contributed email validation on the register form and some CSS fixes.

## Security note

This was a learning project and I want to be accurate about it. What is real: every database query uses PDO prepared statements (no string-built SQL), all output is escaped with `htmlspecialchars`, and passwords are hashed with `password_hash`. What is **not** wired up: the codebase includes CSRF-token, rate-limiting and upload-validation helpers in `includes/security.php`, but they are not actually called from the pages — the final protection pass was never finished. If I took it further, wiring those in across every form is the first thing I would do.

## What I would do differently

Finish the security pass above, and put a small router and templating layer in front of the page-per-file structure so shared logic (auth checks, headers) is not repeated in every file.
