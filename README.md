# Laravel + React Starter Kit

## Introduction

Our React starter kit provides a robust, modern starting point for building Laravel applications with a React frontend using [Inertia](https://inertiajs.com).

Inertia allows you to build modern, single-page React applications using classic server-side routing and controllers. This lets you enjoy the frontend power of React combined with the incredible backend productivity of Laravel and lightning-fast Vite compilation.

This React starter kit utilizes React 19, TypeScript, Tailwind, and the [shadcn/ui](https://ui.shadcn.com) and [radix-ui](https://www.radix-ui.com) component libraries.

## Docker Development

This project includes a Docker-based development environment for easy local setup.

### Prerequisites

- Docker and Docker Compose installed on your system

### Getting Started

1. **Copy the environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Start the Docker environment:**
   ```bash
   docker compose up -d
   ```

3. **Install PHP dependencies:**
   ```bash
   docker compose exec app composer install
   ```

4. **Generate application key:**
   ```bash
   docker compose exec app php artisan key:generate
   ```

5. **Install Node.js dependencies:**
   ```bash
   docker compose exec app npm install
   ```

6. **Run database migrations:**
   ```bash
   docker compose exec app php artisan migrate
   ```

7. **Configure Google OAuth (required for authentication):**
   
   The application uses Google OAuth and Magic Link for authentication. To enable Google OAuth:
   
   a. Go to the [Google Cloud Console](https://console.cloud.google.com/)
   
   b. Create a new project or select an existing one
   
   c. Enable the Google+ API
   
   d. Go to "Credentials" → "Create Credentials" → "OAuth client ID"
   
   e. Configure the OAuth consent screen if prompted
   
   f. Choose "Web application" as the application type
   
   g. Add authorized redirect URIs:
      - For local development: `http://localhost:8000/auth/google/callback`
      - For production: `https://yourdomain.com/auth/google/callback`
   
   h. Copy the Client ID and Client Secret
   
   i. Add them to your `.env` file:
   ```env
   GOOGLE_CLIENT_ID=your-client-id-here
   GOOGLE_CLIENT_SECRET=your-client-secret-here
   GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
   ```
   
   **Note:** Magic Link authentication works without Google OAuth configuration, but Google OAuth login will not be available.

8. **Create an admin user (optional):**
   ```bash
   docker compose exec app php artisan db:seed --class=AdminUserSeeder
   ```
   This will create an admin user with:
   - Email: `admin@example.com`
   - Password: `password`
   - Admin privileges enabled
   
   **Note:** Since password-based authentication is disabled, you'll need to log in via Google OAuth or Magic Link. For the seeder user, you can either:
   - Use Google OAuth with the `admin@example.com` email (if you have access to that Google account)
   - Request a Magic Link for `admin@example.com`
   - Manually create a user via tinker and set them as admin

   Alternatively, you can manually set a user as admin by updating the `is_admin` column in the database:
   ```bash
   docker compose exec app php artisan tinker
   ```
   Then run:
   ```php
   $user = App\Domain\Users\Models\User::where('email', 'your@email.com')->first();
   $user->is_admin = true;
   $user->save();
   ```

9. **Access the application:**
   - Application: http://localhost:8000
   - Vite dev server: http://localhost:5173 (hot reloading enabled)

### Common Commands

**Stop the environment:**
```bash
docker compose down
```

**Run Artisan commands:**
```bash
docker compose exec app php artisan <command>
```

**Run database seeders:**
```bash
docker compose exec app php artisan db:seed
```

**Run tests:**
```bash
docker compose exec app php artisan test
```

**Run npm commands:**
```bash
docker compose exec app npm <command>
```

**View logs:**
```bash
docker compose logs -f app
```

### Services Included

- **PHP 8.4** with required extensions (PostgreSQL, Redis, etc.)
- **Node.js 24** for Vite and frontend tooling
- **PostgreSQL 18** database
- **Redis 8** for caching and queues

The Laravel development server and Vite dev server start automatically when you run `docker compose up`.

## Authentication

This application uses two authentication methods:

1. **Google OAuth**: Users can sign in with their Google account
2. **Magic Link**: Users can request a passwordless login link sent to their email

Both methods automatically create user accounts if they don't exist (auto-create strategy). Users created via Google OAuth have their email automatically verified.

**Note:** Email+password authentication and Two-Factor Authentication (2FA) are disabled in this application.

## Official Documentation

Documentation for all Laravel starter kits can be found on the [Laravel website](https://laravel.com/docs/starter-kits).

## Contributing

Thank you for considering contributing to our starter kit! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## License

The Laravel + React starter kit is open-sourced software licensed under the MIT license.
