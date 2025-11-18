# Mini Wallet – Technical Assignment Coverage & Setup Guide

## 1. Architecture overview (Mini Wallet)

Mini Wallet is implemented as a single Laravel 12 API backend with a Vue 3 SPA frontend under `resources/ts`. The major building blocks are:

- **Domain layer (`App\Domain\Wallet`)**
  - `App\Domain\Wallet\Services\WalletTransferService` implements the core money transfer logic.
  - `App\Domain\Wallet\Contracts\TransfersMoney` is the domain interface used to decouple controllers from the transfer implementation.
  - `App\Domain\Wallet\Exceptions\InsufficientBalanceException` and `App\Domain\Wallet\Exceptions\InvalidTransferException` encapsulate domain‑level validation errors (e.g. insufficient funds, invalid amount, self‑transfer).

- **HTTP API & application layer**
  - `App\Http\Controllers\Api\LoginController` handles authentication and issues API tokens using Laravel Sanctum.
  - `App\Http\Controllers\Api\TransactionController` exposes:
    - `GET /api/transactions` – list authenticated user transactions and current balance.
    - `POST /api/transactions` – perform a transfer between two users.
  - `App\Http\Requests\Auth\LoginRequest` and `App\Http\Requests\TransactionStoreRequest` validate inbound payloads.
  - `App\Http\Resources\TransactionResource` shapes the JSON API response for each transaction (amount, commission, direction, sender/receiver info, usernames, timestamps).

- **Persistence layer**
  - `App\Models\User` stores the wallet balance as a high‑precision decimal field (`balance`) and defines relations to transactions.
  - `App\Models\Transaction` stores each transfer with `sender_id`, `receiver_id`, `amount`, and `commission_fee`, plus relationships back to the users.
  - Migrations under `database/migrations` define the `users` table with a `balance` column and the `transactions` table with indexed foreign keys.

- **Service providers & configuration**
  - `App\Providers\WalletServiceProvider` binds `TransfersMoney` to `WalletTransferService` in the Laravel service container.
  - `App\Providers\RouteServiceProvider` configures the `wallet-transfers` rate limiter for `POST /api/transactions`.
  - `config/wallet.php` holds wallet‑specific configuration such as idempotency TTL.
  - `config/broadcasting.php` configures broadcasting connections including the Pusher driver.

- **Realtime & broadcasting**
  - `App\Events\TransactionCreated` is a `ShouldBroadcast` event fired after each successful transfer.
    - Broadcasts on two private channels: `wallet.user.{sender_id}` and `wallet.user.{receiver_id}`.
    - Broadcast name: `wallet.transaction.created`.
    - Broadcast payload includes the transaction data and the updated balances for both users.
  - On the frontend, `resources/ts/lib/echo.ts` configures Laravel Echo with Pusher and authenticates via Sanctum bearer token.
  - `resources/ts/composables/useWalletRealtime.ts` subscribes the SPA to the private `wallet.user.{id}` channel and listens for the `wallet.transaction.created` event to update the UI in real time.

- **SPA frontend**
  - `resources/ts/App.vue` is the root Vue 3 component.
  - `resources/ts/pages/Login.vue` provides the login form and stores the API token in `localStorage`.
  - `resources/ts/pages/Wallet.vue` is the main wallet screen showing:
    - Current balance for the authenticated user.
    - A “Send money” form bound to `/api/transactions`.
    - Transaction history with direction (incoming/outgoing), amounts and commission.
    - Realtime updates via the `useWalletRealtime` composable.

- **Testing**
  - Feature tests under `tests/Feature/API/Transactions` cover the wallet API behaviour (authentication, validation, idempotency, rate limiting, event dispatch).
  - `tests/Feature/Domain/WalletDomainTest` and `tests/Feature/Wallet/WalletTransferServiceTest` exercise the domain layer and atomic balance updates.
  - `tests/Feature/Database/SeederTest` verifies seeded users and demo transactions.
  - `tests/Feature/SpaEntryTest` ensures the SPA entrypoint is reachable.

---

## 2. What the project covers from the technical assignment

Based on the “Technical Assignment” specification, Mini Wallet implements the following requirements:

- **Wallet balances stored as high‑precision decimals**
  - User balances are stored directly on the `users` table as a decimal field (`balance`) to avoid recalculating balances from transaction history.

- **Transactions with fee and proper relations**
  - Each transfer creates exactly one `Transaction` row with:
    - `sender_id` and `receiver_id` (foreign keys to `users`).
    - `amount` (the amount received by the receiver).
    - `commission_fee` (the fee paid by the sender).
  - Eloquent relationships on `User` (`sentTransactions`, `receivedTransactions`) and `Transaction` (`sender`, `receiver`) support efficient loading and JSON serialization.

- **1.5% commission fee**
  - The domain service `WalletTransferService` calculates a 1.5% commission on each transfer:
    - Example: sending `100.0000` charges the sender `101.5000` and credits the receiver `100.0000`.
  - Commission logic uses decimal math and is verified by the dedicated wallet transfer tests.

- **Atomic transfers and consistency**
  - `WalletTransferService` performs each transfer inside a single database transaction.
  - It uses pessimistic row‑level locking on the two user rows in a consistent order, preventing race conditions and deadlocks under high concurrency.
  - If any part of the transfer fails (validation, insufficient balance, DB error), neither balances nor the transaction record are persisted.

- **Validation and error handling**
  - `TransactionStoreRequest` validates:
    - Existence of the receiver via `receiver_username` (cannot send to yourself).
    - `amount` as a positive numeric value.
  - Domain‑level exceptions (`InsufficientBalanceException`, `InvalidTransferException`) are translated into structured JSON errors with HTTP 422 responses.
  - Feature tests verify validation and error responses.

- **Idempotency for transfers**
  - `TransactionController::store` honours an `Idempotency-Key` request header.
  - Idempotency keys are stored in cache (using `WALLET_IDEMPOTENCY_TTL` from `config/wallet.php` / `.env`) to prevent duplicate transfers on retries.

- **Rate limiting**
  - A custom `wallet-transfers` limiter is configured in `RouteServiceProvider`.
  - The `POST /api/transactions` route uses this limiter to enforce a per‑minute cap per user (or IP) for transfer requests.
  - Feature tests validate that excessive requests are rejected with HTTP 429.

- **Realtime notifications via Pusher**
  - On each successful transfer, `TransactionCreated` is dispatched and broadcast via Pusher:
    - Both the sender and receiver subscribe to their own private `wallet.user.{id}` channels.
    - The SPA uses Laravel Echo to update balances and transaction history instantly whenever the event is received.
  - This matches the requirement for a real‑time wallet UI.

- **Single-page application frontend**
  - The Vue 3 SPA under `resources/ts` consumes the `/api/login`, `/api/user`, `/api/transactions` endpoints.
  - `Wallet.vue` implements the live wallet UI with current balance, list of transactions, and the send‑money form.
  - `Login.vue` handles authentication and redirects to the wallet screen.

- **Automated tests**
  - `composer.json` defines a `test` script that runs Laravel’s test suite.
  - Tests cover both API endpoints and domain behaviour, as requested in the assignment.

---

## 3. Running the application

### 3.1. Requirements

To run Mini Wallet locally you will need:

- PHP 8.2 or newer (the project requires `php: ^8.2` in `composer.json`).
- Composer.
- Node.js and npm (for the Vue 3 SPA and Vite).
- A database (e.g. MySQL, PostgreSQL, or SQLite) configured in `.env`.

### 3.2. Initial setup

From the project root (`mini-wallet-master`):

1. **Install PHP dependencies**  
   Use Composer to install backend dependencies defined in `composer.json`.

2. **Create the `.env` file**  
   - Copy `.env.example` to `.env`.  
   - Set at least:
     - `APP_NAME="Mini Wallet"`
     - `APP_ENV=local`
     - `APP_URL` (for example `http://localhost:8000`)
     - Database connection settings (`DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
     - Sanctum / SPA settings:
       - `FRONTEND_URL=http://localhost:5173`
       - `SANCTUM_STATEFUL_DOMAINS=localhost:5173`
       - `SESSION_DOMAIN=localhost`

3. **Generate the application key**  
   Run the Artisan key generation command once to set `APP_KEY` in `.env`.

4. **Install frontend dependencies**  
   From the same project root, install the Node dependencies defined in `package.json`.

### 3.3. Database migration and seeding (creating the demo users)

Mini Wallet uses standard Laravel migrations and seeders. The main database seeder is `Database\Seeders\DatabaseSeeder`, which calls:

- `Database\Seeders\UserSeeder`
- `Database\Seeders\TransactionSeeder`

To prepare the database and create the demo users:

1. Configure your database connection in `.env`.
2. Run Laravel migrations **with seeding enabled** so that `UserSeeder` and `TransactionSeeder` are executed in one step.

After this, two demo users will be created by `Database\Seeders\UserSeeder` with the following details:

| Name  | Username | Email              | Password | Initial balance |
|-------|----------|--------------------|----------|-----------------|
| Alice | alice    | alice@example.com  | password | 1000.0000       |
| Bob   | bob      | bob@example.com    | password | 500.0000        |

These two users will also have some example transaction history created by `Database\Seeders\TransactionSeeder` for UI verification.

### 3.4. Starting the backend server

You can either run the Laravel development server directly or use the Composer helper script defined in `composer.json`.

- **Option A: Direct Artisan server**  
  From the project root, start the Laravel HTTP server on port 8000.

- **Option B: Composer “dev” script**  
  `composer.json` defines a `dev` script that uses `concurrently` to run:
  - The HTTP server.
  - Background processes (such as queue workers / log tailing).
  - Vite (`npm run dev`) for the SPA.

If you use the `dev` script, it will start both the backend and Vite dev server together.

### 3.5. Starting the frontend

The SPA is built with Vite. To run it separately (if you are not using the `dev` composer script):

1. In the project root, start the Vite dev server.
2. Open the frontend URL configured in `.env` (by default `http://localhost:5173`).

The SPA will communicate with the Laravel backend at the URL defined by `APP_URL`.

---

## 4. Configuring Pusher for realtime updates

Mini Wallet uses Pusher for broadcasting the `TransactionCreated` event to the frontend. You can configure it with the existing environment variables in `.env.example`:

- Backend broadcasting settings:
  - `PUSHER_APP_ID=`
  - `PUSHER_APP_KEY=`
  - `PUSHER_APP_SECRET=`
  - `PUSHER_APP_CLUSTER=mt1`
  - `PUSHER_HOST=`
  - `PUSHER_PORT=`
  - `PUSHER_SCHEME=https`
  - `PUSHER_APP_USE_TLS=true`

- Frontend (Vite) settings:
  - `VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"`
  - `VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"`
  - `VITE_PUSHER_HOST=`
  - `VITE_PUSHER_PORT=`
  - `VITE_PUSHER_SCHEME="https"`

To test realtime behaviour:

1. Create a Pusher app in your Pusher dashboard.
2. Fill in the above Pusher variables in `.env` with your app credentials.
3. Ensure broadcasting is using the Pusher connection (for example by setting `BROADCAST_CONNECTION=pusher` in your `.env` and aligning `config/broadcasting.php` with that connection).

When a transfer succeeds, the backend will dispatch `App\Events\TransactionCreated`, and the frontend will receive the `wallet.transaction.created` event through Laravel Echo and Pusher.

---

## 5. Logging in and testing with two sessions

Once the backend and frontend are running and the database is seeded:

1. **Open the SPA as Alice**
   - Navigate to the frontend URL (for example `http://localhost:5173`).
   - Log in using the credentials from `UserSeeder`:
     - Username or email: `alice` / `alice@example.com` (depending on your login form).
     - Password: `password`.

2. **Open a second session as Bob**
   - Open a new browser window or an incognito/private window.
   - Go to the same frontend URL.
   - Log in as Bob:
     - Username or email: `bob` / `bob@example.com`.
     - Password: `password`.

3. **Testing a transfer**
   - In Alice’s session, open the Wallet screen (`resources/ts/pages/Wallet.vue` in the SPA).
   - Use the “Send money” form to send an amount from Alice to Bob by entering Bob’s username (`bob`) and an amount.
   - After submission:
     - Alice’s balance should decrease by the amount plus 1.5% commission.
     - Bob’s balance should increase by the amount.
     - Both sessions should see the new transaction appear in their transaction list.
     - With Pusher configured, the Bob session should update in real time without manual refresh.

This manual test flow validates both the domain logic (balances and commission) and the realtime broadcasting integration.

---

## 6. Running tests

Mini Wallet ships with a test suite configured via `composer.json`:

- The `test` script runs Laravel’s test runner with configuration clearing beforehand.

To run all tests from the project root:

1. Ensure your `.env` test configuration is valid (for example an in‑memory SQLite database or a dedicated test database).
2. Run the Composer script that executes the test suite.

The tests cover:

- API behaviour for `/api/transactions` (authentication, validation, successful transfer, idempotency, rate limiting, event dispatch).
- Domain behaviour in `WalletTransferService` (atomic updates, commission, invalid transfer handling).
- Database seeders (`UserSeeder`, `TransactionSeeder`) to ensure the example data is created as expected.
- SPA entrypoint availability.

This provides confidence that the implementation matches the technical assignment and remains stable as the code evolves.
