# KoperasiHub

KoperasiHub is a white-label cooperative management platform built with Laravel, Vue 3, Inertia.js, Tailwind CSS, and shadcn-vue.

This repository is currently at Phase 0: base application setup with placeholder public, admin, and member pages.

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

## Run Locally

Use two terminals:

```bash
php artisan serve
npm run dev
```

Then open:

```txt
http://127.0.0.1:8000
http://127.0.0.1:8000/admin
http://127.0.0.1:8000/member
```
