# Fitness Tracker

A self-hosted, small-group training tracker for 1–10 users.

## Features

- **User Management** - Open signup, login/logout, password hashing, session-based auth
- **Training Tracking** - Log sessions with type, duration, intensity, notes
- **Body Tracking** - Weight and body fat history
- **Analytics** - Weekly/monthly progress, trend graphs
- **Calendar View** - Monthly layout with session overview
- **Dark Mode UI** - Clean, mobile-first design

## Requirements

- PHP 8.1+
- MariaDB 10.5+ (or MySQL 8.0+)
- Apache 2.4+ or Nginx
- PHP Extensions: pdo_mysql, mbstring, json, openssl, session

## Installation

### 1. Clone/Copy Files

Place the `fitness-tracker` folder on your server.

### 2. Create Database

```bash
mysql -u root -p
```

```sql
CREATE DATABASE fitness_tracker;
CREATE USER 'fitness_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON fitness_tracker.* TO 'fitness_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Import Schema

```bash
mysql -u fitness_user -p fitness_tracker < database/schema.sql
```

### 4. Configure Database Connection

Edit `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'fitness_tracker');
define('DB_USER', 'fitness_user');
define('DB_PASS', 'your_secure_password');  // Change this!
```

### 5. Configure Web Server

**Apache** - Point DocumentRoot to the `public/` folder:

```apache
<VirtualHost *:80>
    ServerName fitness.local
    DocumentRoot /path/to/fitness-tracker/public
    
    <Directory /path/to/fitness-tracker/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx**:

```nginx
server {
    listen 80;
    server_name fitness.local;
    root /path/to/fitness-tracker/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 6. Set Permissions

```bash
chmod 755 logs/
chmod 755 backups/
```

### 7. Enable HTTPS (Recommended)

```bash
sudo certbot --apache -d fitness.yourdomain.com
```

## Usage

1. Visit your domain/localhost
2. Click "Create an account" to register
3. Log in with your credentials
4. Start logging sessions and body metrics!

## Directory Structure

```
fitness-tracker/
├── public/           <- Web root (only accessible folder)
│   ├── index.php
│   ├── dashboard.php
│   ├── login.php
│   ├── register.php
│   ├── log-session.php
│   ├── log-weight.php
│   ├── calendar.php
│   ├── progress.php
│   └── assets/
│       ├── css/
│       └── js/
├── app/
│   ├── models/
│   ├── middleware/
│   └── utils/
├── config/
│   ├── config.php
│   └── database.php
├── database/
│   └── schema.sql
├── logs/
└── backups/
```

## Backup

Manual backup:

```bash
mysqldump -u fitness_user -p fitness_tracker > backups/backup_$(date +%Y%m%d).sql
```

## Security Notes

- Only the `public/` folder should be web-accessible
- Change the default database password
- Enable HTTPS in production
- Set `APP_ENV` to `production` in config/config.php
- Regularly backup your database

## License

MIT License - Feel free to modify and use as needed.
