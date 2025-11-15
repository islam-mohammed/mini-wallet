
# Mini Wallet — High-Performance Digital Wallet  
### Laravel 12 · Vue 3 (TypeScript) · MySQL · Redis · Pusher · Domain-Driven Architecture · TDD with Pest

---

## Introduction

The **Mini Wallet** project is a production-grade, high-concurrency financial application designed to simulate real-world digital wallet systems. It demonstrates:

- Scalable architecture
- Safe balance operations
- Atomic money transfers
- Realtime updates using Pusher
- Clean domain separation
- Vue 3 SPA with TypeScript
- TDD-first (Test-Driven Development) approach

This application is built for **high incoming request volume**, handling **hundreds of transfers per second**, while guaranteeing data integrity and consistent user balances.

---


# 🧠 Architectural Principles

### **SOLID Principles**
- Single Responsibility for each service and action  
- Dependency Inversion: controllers rely on interfaces  
- Open/Closed: domain services can be extended safely  

### **Domain-Driven Design (DDD) Lite**
- The wallet logic lives inside `Domain/Wallet`  
- Controllers do not contain business logic  
- Transfer logic is isolated and fully testable  

### **Performance-Oriented Design**
- Uses `FOR UPDATE` row-level locking  
- Avoids computing balance from huge transaction tables  
- Uses indexed queries  
- Prevents deadlocks through **sorted locking order**  
- Idempotency keys prevent duplicate transfers  

### **Security**
- Full validation layer  
- Authentication-based access  
- Rate limiting  
- User-specific private broadcasting channels  

---

# Backend Technical Design

## Database Schema

### **users table**
| Column | Type | Description |
|--------|-------|-------------|
| id | bigint | PK |
| name | string | User full name |
| email | string | Login credential |
| balance | decimal(18,4) | Current wallet balance |
| password | string | Hashed |
| timestamps | | |

### **transactions table**
| Column | Type | Description |
|--------|-------|-------------|
| id | ULID | PK |
| sender_id | FK | User sending money |
| receiver_id | FK | Recipient |
| amount | decimal(18,4) | Transferred amount |
| commission_fee | decimal(18,4) | 1.5% fee charged |
| timestamps | | |

### Important Indexes:
- `sender_id`
- `receiver_id`
- `created_at`
- `id` (primary, ULID)

---

# Money Transfer Flow (Atomic & Concurrency-Safe)

The transfer operation must:

- Work under heavy load
- Prevent double spending
- Avoid race conditions
- Maintain strict ACID consistency

### Steps:

1. Validate request
2. Start MySQL transaction
3. **Lock rows** for sender and receiver:
   ```sql
   SELECT * FROM users WHERE id = ? FOR UPDATE
   ```
4. Recalculate sender’s available balance
5. Compute commission (1.5%)
6. Debit sender: `balance -= (amount + commission)`
7. Credit receiver: `balance += amount`
8. Insert transaction row
9. Commit transaction
10. Broadcast Realtime event to both users

This ensures **no double updates**, **no negative balance**, and **no inconsistent state**.

---

# Realtime Broadcasting

After a successful transfer:

1. `TransactionCreated` event is fired
2. Laravel Broadcasting sends it to Pusher
3. Users subscribe to:
   ```
   private-user.{id}
   ```
4. Frontend updates:
   - balance  
   - transaction list  
   - real-time feedback  

### Payload example:

```json
{
  "transaction": {
    "id": "01HYZX3Y6ZKKPGT5W4",
    "amount": "100.0000",
    "commission_fee": "1.5000",
    "sender_id": 1,
    "receiver_id": 2
  },
  "sender_balance": "848.5000",
  "receiver_balance": "600.0000"
}
```

---

# Frontend Architecture (Vue 3 + TypeScript)

### Tools used:
- Vue 3 (Composition API)
- TypeScript
- TailwindCSS
- Pusher + Laravel Echo


### UX Flow:

- User submits transfer form
- API validates + processes transaction
- Realtime event updates UI
- Balance & transactions refresh automatically

---

# Testing Strategy — TDD Approach (Pest)

This project uses **Test-Driven Development**, meaning:

1. Write tests first  
2. Watch them fail  
3. Implement logic  
4. Verify tests pass  
5. Refactor  

### Test Coverage:

#### **Unit Tests**
- Commission calculation  
- Sender/receiver balance math  
- Transfer service atomicity  
- Idempotency enforcement  
- Exceptions & domain rules  

#### **Feature Tests**
- `/api/transactions` listing  
- `/api/transactions` creation  
- Validation error responses  
- Sufficient balance enforcement  
- Realtime event dispatch assertions  
- Race condition prevention tests  

Run tests:

```
composer test
```

---

# 🗃️ Seeders

Seeders create:

| Email | Password | Balance |
|--------|----------|----------|
| demo1@mini-wallet.test | password | 1000.0000 |
| demo2@mini-wallet.test | password | 500.0000 |

Seeder file:

```
php artisan db:seed
```

---

# Dev Container (VSCode + Docker)

A fully prepared dev environment is included.

### Requirements:
- VS Code
- Dev Containers extension
- Docker Desktop

### Steps:

1. Clone repo  
2. Open folder in VS Code  
3. VS Code → **Reopen in Container**  
4. Auto-installs:

```
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
```

### Start backend:
```
php artisan serve --host=0.0.0.0 --port=8000
```

### Start frontend:
```
npm run dev -- --host 0.0.0.0
```

---

# API Summary

## GET `/api/transactions`
Returns user balance + paginated transactions.

## POST `/api/transactions`
```json
{
  "receiver_id": 2,
  "amount": "100.0000"
}
```

Headers:
```
Idempotency-Key: unique-string
```

---

# Production Build

```
npm run build
php artisan optimize
```

---

# Final Summary

This implementation fully covers:

### ✔ Full backend API  
### ✔ Scalable domain architecture  
### ✔ MySQL row-level locking  
### ✔ Atomic balance operations  
### ✔ Commission enforcement  
### ✔ Millions-of-rows scalability  
### ✔ Real-time Pusher events  
### ✔ Vue 3 SPA frontend  
### ✔ Dev containers for onboarding  
### ✔ Seeders  
### ✔ Complete TDD suite  
### ✔ Clean, maintainable code  

---
