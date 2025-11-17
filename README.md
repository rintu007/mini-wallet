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
### 4. Setup Database
```bash
# Publish Sanctum files
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Run migrations (this will create personal_access_tokens table)
php artisan migrate
```

### 5. Build Frontend
```bash
npm run build
```

### 6. Start Application
```bash
# Start Laravel server
php artisan serve

# For real-time features, open new terminal and run:
php artisan queue:work
```

### 7. Access Application
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