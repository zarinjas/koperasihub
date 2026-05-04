# KoperasiHub

KoperasiHub is a white-label cooperative management platform built with Laravel, Vue 3, Inertia.js, Tailwind CSS, and shadcn-vue.

This repository currently includes the public website, custom admin panel, member portal, and demo seed data through the completed MVP phases requested so far.

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
npm run build
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

## Demo Accounts

Use these seeded accounts after `php artisan migrate:fresh --seed`:

```txt
admin@koperasihub.test / password
member@koperasihub.test / password
```
