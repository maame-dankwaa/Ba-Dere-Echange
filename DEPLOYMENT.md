# Deployment Guide - Ba Dɛre Exchange

## Environment Variables Setup

### Option 1: Using .htaccess (Recommended for Apache servers)

1. **Copy the example file:**
   ```bash
   cp .htaccess.example .htaccess
   ```

2. **Edit `.htaccess` with your production credentials:**
   ```bash
   nano .htaccess
   ```

3. **Update these values:**
   ```apache
   SetEnv DB_HOST "localhost"
   SetEnv DB_NAME "ecommerce_2025A_maame_afranie"
   SetEnv DB_USER "your_actual_username"
   SetEnv DB_PASS "your_actual_password"
   ```

4. **Protect the .htaccess file:**
   - Add `.htaccess` to `.gitignore` if it contains real credentials
   - Set proper permissions: `chmod 644 .htaccess`

### Option 2: Using cPanel Environment Variables

1. **Login to cPanel**
2. **Navigate to:** Software → Select PHP Version (or similar)
3. **Click:** Switch to PHP Options
4. **Add these environment variables:**
   - `DB_HOST` = `localhost`
   - `DB_PORT` = `3306`
   - `DB_NAME` = `ecommerce_2025A_maame_afranie`
   - `DB_USER` = `your_username`
   - `DB_PASS` = `your_password`
   - `APP_ENV` = `production`
   - `DB_LOG_QUERIES` = `false`

### Option 3: Using .env file with PHP

1. **Create `.env` file from example:**
   ```bash
   cp .env.example .env
   ```

2. **Edit with your credentials:**
   ```bash
   nano .env
   ```

3. **Load in your PHP code** (requires additional setup)

## Database Setup

### 1. Create Database and User

Run this in phpMyAdmin or MySQL:

```sql
CREATE DATABASE IF NOT EXISTS ecommerce_2025A_maame_afranie
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Grant privileges (if needed)
GRANT ALL PRIVILEGES ON ecommerce_2025A_maame_afranie.*
TO 'maame.afranie'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Import Database Schema

```bash
mysql -u maame.afranie -p ecommerce_2025A_maame_afranie < db/database_schema.sql
```

Or use phpMyAdmin:
1. Select your database
2. Click "Import"
3. Choose `db/database_schema.sql`
4. Click "Go"

### 3. Verify Tables Created

```sql
SHOW TABLES LIKE 'fp_%';
```

You should see 16 tables with the `fp_` prefix.

## File Upload

### Using Option 3: Selective Upload (Recommended)

**Upload these folders/files:**
- ✓ `index.php`
- ✓ `view/`
- ✓ `js/`
- ✓ `services/`
- ✓ `login/`
- ✓ `helpers/`
- ✓ `includes/`
- ✓ `classes/`
- ✓ `config/Config.php` (only this file)
- ✓ `.htaccess` (with your credentials)

**Do NOT upload (preserve server versions):**
- ✗ `config/settings/` (create manually on server)
- ✗ `uploads/` (preserve existing uploads)
- ✗ `logs/` (preserve existing logs)
- ✗ `.git/` (never upload)
- ✗ `.env` (create manually on server)

### After Upload

1. **Create config/settings directory:**
   ```bash
   mkdir -p config/settings
   chmod 755 config/settings
   ```

2. **Create/Edit db_class.php on server:**
   - Copy content from your local file
   - Update credentials for production
   - Set permissions: `chmod 644 config/settings/db_class.php`

3. **Set proper permissions:**
   ```bash
   chmod 755 uploads
   chmod 755 uploads/books
   chmod 755 logs
   ```

4. **Test database connection:**
   - Visit: `https://yoursite.com/`
   - Check for any database connection errors
   - Check logs: `logs/database-*.log`

## Security Checklist

- [ ] Environment variables set correctly
- [ ] `config/settings/db_class.php` has correct permissions (644)
- [ ] `.git/` folder excluded from upload
- [ ] `DB_LOG_QUERIES` set to `false` in production
- [ ] `uploads/` directory writable but not executable
- [ ] Error reporting disabled in production
- [ ] HTTPS enabled on domain
- [ ] Database credentials are strong
- [ ] Default admin password changed

## Troubleshooting

### Database Connection Failed
- Check credentials in `config/settings/db_class.php`
- Verify database exists: `SHOW DATABASES;`
- Check user permissions: `SHOW GRANTS FOR 'maame.afranie'@'localhost';`

### Tables Not Found
- Ensure tables have `fp_` prefix
- Re-run database schema: `db/database_schema.sql`

### Permission Denied
```bash
chmod 755 uploads uploads/books logs
chmod 644 config/settings/db_class.php
```

## Support

For issues, check:
1. Error logs: `logs/database-*.log`
2. PHP error logs on server
3. Browser console for JavaScript errors
