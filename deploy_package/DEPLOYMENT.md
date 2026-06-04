# Chat App Deployment

## Easy Option: cPanel / Shared Hosting

1. Open cPanel.
2. Create a MySQL database.
3. Create a MySQL user and password.
4. Give the user all permissions on the database.
5. Open phpMyAdmin.
6. Select your database.
7. Import `chartApp.sql`.
8. Upload all project files into `public_html` or your domain folder.
9. Open `connection.php`.
10. Change these values:

```php
$host = getenv("DB_HOST") ?: "localhost";
$user = getenv("DB_USER") ?: "YOUR_DB_USERNAME";
$password = getenv("DB_PASSWORD") ?: "YOUR_DB_PASSWORD";
$database = getenv("DB_NAME") ?: "YOUR_DB_NAME";
```

11. Open your domain in the browser.

## Important Files to Upload

- `index.php`
- `login.php`
- `registration.php`
- `dashboard.php`
- `chartroom.php`
- `connection.php`
- `asset/chart.css`
- `asset/chart.js`
- `chartApp.sql` is only for import. It does not need to remain public after import.

## Security Before Going Live

- Change the database password in `connection.php`.
- Do not use the `root` user on hosting.
- After importing the database, you can remove `chartApp.sql` from the public folder.
- Make sure your hosting account has PHP 8 or newer.
