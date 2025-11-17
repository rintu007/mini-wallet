# Mini Wallet - Setup and Running Instructions

## Quick Start

### 1. Install Dependencies
```bash
composer install
npm install
```

### 2. Setup Environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mini_wallet
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password

# For real-time features (optional)
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1
```

### 3. Setup Database
```bash
php artisan migrate
php artisan db:seed
```

### 4. Build Frontend
```bash
npm run build
```

### 5. Start Application
```bash
# Start Laravel server
php artisan serve

# For real-time features, open new terminal and run:
php artisan queue:work
```

### 6. Access Application
Open: http://localhost:8000

## Demo Accounts
- **Email:** john@example.com / **Password:** password
- **Email:** jane@example.com / **Password:** password  
- **Email:** bob@example.com / **Password:** password

## Testing Real-time Features
1. Open two browser windows
2. Login with different users
3. Make a transfer - both users will see real-time updates

## Development Mode
```bash
# For frontend hot-reload
npm run dev

# In separate terminal
php artisan serve
```

The application is now ready to use!

## Scheduled Jobs & Maintenance

The application uses Laravel Scheduler for automated maintenance tasks. These jobs ensure data integrity and optimal performance.

### Scheduled Tasks

#### 1. Daily Balance Reconciliation
**Schedule:** Daily at 2:00 AM
**Command:** `wallet:reconcile-balances`
**Purpose:** Verifies that user balances match their transaction history and automatically corrects minor discrepancies.

#### 2. Monthly Transaction Archiving
**Schedule:** 1st of every month at 3:00 AM  
**Command:** `wallet:archive-transactions`
**Purpose:** Moves transactions older than 24 months to archive tables to maintain performance.

#### 3. Queue Worker Monitoring
**Schedule:** Every minute
**Command:** `queue:work --stop-when-empty`
**Purpose:** Processes any pending jobs in the queue.

### Setting Up the Scheduler

#### For Local Development:
The scheduler runs automatically when using `php artisan serve`.

#### For Production (Linux Server):
Add this cron entry to your server:

```bash
# Edit crontab
crontab -e

# Add this line (adjust path to your project):
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1

# Run balance reconciliation
php artisan wallet:reconcile-balances

# Run transaction archiving
php artisan wallet:archive-transactions

# Test the scheduler
php artisan schedule:list
php artisan schedule:run
```

