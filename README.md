# Mini Wallet – Implementation Summary & Setup Guide

This document explains:

- Which parts of the **technical assignment** are covered by the current implementation.
- How to **run the application** locally.
- How to **seed the database with 2 demo users** for testing.
- How to **log in from two sessions** and test real‑time transfers.
- How to **configure Pusher**.
- A high‑level overview of the **architecture**.

---

## 1. Requirements Coverage (vs Technical Assignment)

Below is a mapping between the technical assignment requirements and what the project implements.

### 1.1 Core Features

- **Wallet & balance**
  - Each user has a `balance` column on the `users` table (`DECIMAL(18,4)`).
  - The **current balance is always stored** on the user, not recalculated from transactions.
  - Balance is updated atomically when a transfer is made.

- **Transactions**
  - `transactions` table stores:
    - `id` (ULID primary key)
    - `sender_id`, `receiver_id`
    - `amount`
    - `commission_fee`
    - timestamps
  - Indexes are present on `sender_id`, `receiver_id`, and `created_at` to support high‑traffic queries.

- **Transfer & commission**
  - Money transfers are handled by a dedicated domain service (`WalletTransferService`) behind an interface (`TransfersMoney`).
  - A **1.5% commission** is charged to the sender:
    - If the sender transfers `100.0000`, they are debited `101.5000`.
    - The receiver gets `100.0000`.
  - All money math uses **BCMath** with 4 decimal places to avoid floating‑point errors.

### 1.2 Concurrency, Atomicity & Integrity

- **Atomic updates**
  - The core transfer is wrapped in a **single database transaction**:
    - Check sender balance.
    - Debit sender (including commission).
    - Credit receiver.
    - Insert transaction row.
  - If anything fails, all changes are rolled back.

- **Row‑level locking**
  - `SELECT ... FOR UPDATE` is used on both users in a deterministic order (sorted IDs) to:
    - Prevent race conditions.
    - Avoid deadlocks when users send to each other concurrently.

- **Idempotency**
  - Optional `Idempotency-Key` request header:
    - First request with a given key succeeds and stores a lock in cache.
    - Subsequent requests with the same key return `409 Duplicate transfer request.`

- **Rate limiting**
  - A custom `wallet-transfers` rate limiter is configured.
  - `POST /api/transactions` is protected by `throttle:wallet-transfers`, returning `429` if the limit is exceeded.

### 1.3 API Endpoints

- **Authentication**
  - `POST /api/login`
    - Accepts credentials, returns a **Sanctum token**.
  - Protected routes use `auth:sanctum`.

- **User & wallet**
  - `GET /api/user`
    - Returns the authenticated user (including current balance).
  - `GET /api/transactions`
    - Returns paginated transactions for the authenticated user (incoming + outgoing).
    - Also returns `meta.balance` with the current balance.

- **Transfers**
  - `POST /api/transactions`
    - Validates:
      - `receiver_username` exists and is not the current user.
      - `amount` is numeric and greater than 0.
    - Uses `WalletTransferService` to perform an atomic transfer.
    - Returns:
      - `data` → newly created transaction resource (with sender/receiver info & direction).
      - `meta.balance` → updated sender balance.

### 1.4 Real‑Time Updates (Pusher)

- **Backend**
  - Event: `App\Events\TransactionCreated` implements `ShouldBroadcast`.
  - Broadcasts on private channels:
    - `wallet.user.{sender_id}`
    - `wallet.user.{receiver_id}`
  - Broadcast payload includes:
    - `transaction` (with sender/receiver relations)
    - `sender_balance`
    - `receiver_balance`

- **Frontend**
  - Laravel Echo is configured in `resources/ts/lib/echo.ts`.
  - A composable (`useWalletRealtime`) listens on:
    - `private('wallet.user.{userId}')`
    - Event name: `.wallet.transaction.created`
  - Wallet UI updates in real time:
    - Prepends the new transaction to the list.
    - Updates the balance for the current user (sender or receiver).

### 1.5 Frontend SPA (Vue 3)

- Vue 3 + TypeScript + Vite SPA.
- Main pages:
  - **Login** page.
  - **Wallet** page:
    - Shows current balance.
    - Displays transaction history (incoming/outgoing).
    - Provides a form to send money by `receiver_username` and `amount`.
    - Shows inline validation errors and a general error message.
    - Reflects real‑time updates via Pusher.

---

## 2. Running the Application Locally

### 2.1 Prerequisites

Make sure you have:

- PHP (8.1+ recommended)
- Composer
- Node.js (LTS) & npm or pnpm/yarn
- A database (MySQL / MariaDB / PostgreSQL)
- Redis (for cache / idempotency / rate limiting)
- A Pusher account (for real‑time testing)

### 2.2 Environment Setup

1. **Install PHP dependencies:**

   ```bash
   composer install
   ```

2. **Install frontend dependencies:**

   ```bash
   npm install
   # or
   pnpm install
   # or
   yarn install
   ```

3. **Create an `.env` file:**

   ```bash
   cp .env.example .env
   ```

4. **Generate an application key:**

   ```bash
   php artisan key:generate
   ```

5. **Configure the database** in `.env`:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=mini_wallet
   DB_USERNAME=your_db_user
   DB_PASSWORD=your_db_password
   ```

6. **Configure Redis** (optional but recommended):

   ```env
   CACHE_DRIVER=redis
   QUEUE_CONNECTION=sync
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

---

## 3. Database Migrations & Seeding Two Users

### 3.1 Run migrations

```bash
php artisan migrate
```

This creates the `users`, `transactions`, and any other required tables.

### 3.2 Seed exactly two demo users

You can create a dedicated seeder for two test users.

1. **Create the seeder (if not already created):**

   ```bash
   php artisan make:seeder DemoUsersSeeder
   ```

2. **Edit `database/seeders/DemoUsersSeeder.php`:**

   ```php
   <?php

   namespace Database\Seeders;

   use App\Models\User;
   use Illuminate\Database\Seeder;
   use Illuminate\Support\Facades\Hash;

   class DemoUsersSeeder extends Seeder
   {
       public function run(): void
       {
           // Sender
           User::updateOrCreate(
               ['email' => 'alice@example.com'],
               [
                   'name' => 'Alice Sender',
                   'username' => 'alice',
                   'password' => Hash::make('password'),
                   'balance' => '1000.0000',
               ]
           );

           // Receiver
           User::updateOrCreate(
               ['email' => 'bob@example.com'],
               [
                   'name' => 'Bob Receiver',
                   'username' => 'bob',
                   'password' => Hash::make('password'),
                   'balance' => '500.0000',
               ]
           );
       }
   }
   ```

3. **Register the seeder in `DatabaseSeeder` (optional):**

   In `database/seeders/DatabaseSeeder.php`:

   ```php
   public function run(): void
   {
       $this->call([
           DemoUsersSeeder::class,
       ]);
   }
   ```

4. **Run the seeder:**

   ```bash
   php artisan db:seed --class=DemoUsersSeeder
   # or, if registered in DatabaseSeeder:
   php artisan db:seed
   ```

Now you have two users:

- **Alice**
  - Username: `alice`
  - Email: `alice@example.com`
  - Password: `password`
  - Initial balance: `1000.0000`
- **Bob**
  - Username: `bob`
  - Email: `bob@example.com`
  - Password: `password`
  - Initial balance: `500.0000`

---

## 4. Running the App & Testing with Two Sessions

### 4.1 Start the backend API

```bash
php artisan serve
```

This usually runs on `http://127.0.0.1:8000`.

### 4.2 Start the frontend dev server

```bash
npm run dev
```

By default, Vite serves the SPA on `http://127.0.0.1:5173` (or similar).  
The SPA will make API calls to your Laravel backend.

> If needed, configure the dev server proxy in `vite.config.ts` so `/api` routes go to `http://127.0.0.1:8000`.

### 4.3 Login and open two sessions

1. **Open two browser windows:**
   - Window A: normal window.
   - Window B: incognito/private window (or a different browser).

2. **In Window A:**
   - Go to the SPA (e.g. `http://127.0.0.1:5173`).
   - Log in as **Alice**:
     - Username/email: `alice@example.com` (depending on your login form).
     - Password: `password`.
   - Navigate to the **Wallet** page.

3. **In Window B:**
   - Open the SPA again.
   - Log in as **Bob** (`bob@example.com` / `password`).
   - Navigate to the **Wallet** page.

4. **Test a transfer:**
   - From Alice’s session:
     - Use the **Send money** form.
     - `receiver_username`: `bob`
     - `amount`: `100.0000`
   - Submit the form.

5. **Observe the result:**
   - Alice’s wallet should:
     - Show a new **outgoing** transaction.
     - Balance should update from `1000.0000` → `898.5000`.
   - Bob’s wallet should:
     - Show a new **incoming** transaction.
     - Balance should update from `500.0000` → `600.0000`.

If Pusher is correctly configured and both sessions are connected, Bob’s screen should update **immediately** without a manual refresh.

---

## 5. Configuring Pusher

To enable real‑time events via Pusher, you’ll need:

1. A **Pusher app** (from https://dashboard.pusher.com/).
2. The following keys from the Pusher dashboard:
   - `app_id`
   - `key`
   - `secret`
   - `cluster`

### 5.1 Backend (.env)

Set these in your `.env`:

```env
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster

# Make sure these match your dev environment
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
```

The `config/broadcasting.php` file should define a `pusher` connection that uses these environment variables.

### 5.2 Frontend (Vite env)

In `.env` or `.env.local` for the frontend:

```env
VITE_PUSHER_APP_KEY=your_app_key
VITE_PUSHER_APP_CLUSTER=your_cluster
VITE_PUSHER_APP_HOST=
VITE_PUSHER_APP_PORT=443
VITE_PUSHER_APP_SCHEME=https
```

The Echo setup in `resources/ts/lib/echo.ts` uses these `VITE_*` variables to connect.

### 5.3 Broadcast auth (Sanctum)

- Broadcast routes are protected with `auth:sanctum`.
- Echo is configured to send the **Bearer token** in the `Authorization` header for `/broadcasting/auth`.

Make sure:

- Users log in and store the token (e.g. in `localStorage`).
- Echo is initialized **after** a valid token exists, so private channel auth succeeds.

---

## 6. High‑Level Architecture Overview

### 6.1 Backend (Laravel)

- **API layer**:
  - Controllers in `App\Http\Controllers\Api`:
    - `LoginController` – handles login and token issuance.
    - `TransactionController` – lists and creates transactions.

- **Domain layer**:
  - `App\Domain\Wallet\Contracts\TransfersMoney` – interface for money transfers.
  - `App\Domain\Wallet\Services\WalletTransferService` – concrete implementation:
    - Encapsulates all wallet business logic.
    - Uses DB transactions and row‑level locks.
    - Performs commission calculation and balance updates.

- **Validation & Resources**:
  - `TransactionStoreRequest` – validates incoming transfer requests.
  - `TransactionResource` – shapes the JSON response for transactions (including direction, usernames, and nested relations).

- **Events & broadcasting**:
  - `TransactionCreated` event:
    - Broadcast via Pusher on private channels.
    - Includes updated balances and full transaction data.

- **Security & infrastructure**:
  - Laravel Sanctum for API authentication.
  - Rate limiting for transfer endpoint (`wallet-transfers`).
  - Cache/Redis used for idempotency and rate limiting.

### 6.2 Frontend (Vue 3 SPA)

- **App shell & routing**:
  - Vue 3 + TypeScript, single‑page app.
  - Routes for:
    - `/login` – login form.
    - `/wallet` – protected wallet page (requires auth).

- **Wallet page**:
  - Displays:
    - Current balance.
    - Transaction list (incoming/outgoing).
  - Provides:
    - Send‑money form with `receiver_username` and `amount`.
    - Inline validation error display and general error messages.
  - Integrates real‑time updates through a `useWalletRealtime` composable.

- **API utilities**:
  - A small `api` helper around `fetch` to:
    - Attach Bearer token from `localStorage`.
    - Parse JSON.
    - Normalize validation errors into a consistent shape.

### 6.3 Real‑Time Flow

1. User submits a transfer from the wallet page.
2. Backend:
   - Validates input.
   - Performs atomic transfer in `WalletTransferService`.
   - Emits `TransactionCreated` event with updated balances.
3. Pusher broadcasts the event on:
   - `private-wallet.user.{sender_id}`
   - `private-wallet.user.{receiver_id}`
4. Frontend:
   - Echo receives `.wallet.transaction.created`.
   - The wallet composable updates:
     - The transaction list (prepend the new transaction).
     - The current user’s balance, depending on whether they are sender or receiver.

---

## 7. Summary

- The project **implements the core wallet assignment**:
  - Atomic transfers with commission and concurrency control.
  - Stored balance on the user for scalability.
  - Proper validation, idempotency, and rate limiting.
  - Real‑time updates via Pusher and Laravel Echo.
  - A Vue 3 SPA wallet UI with login, balance, history, and transfer form.

- This document gives you:
  - A high‑level overview of the architecture.
  - Step‑by‑step instructions to run the app.
  - A simple way to seed **exactly two demo users** for end‑to‑end testing from two browser sessions.
  - Pusher configuration guidelines for real‑time testing.
