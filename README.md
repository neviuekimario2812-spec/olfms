# OLFMS
1. Copy folder to XAMPP htdocs as `olfms`.
2. Create database by importing `database/olfms.sql` in phpMyAdmin.
3. Edit `config/config.php` for database credentials and BASE_URL.
4. Ensure PHP 8+, PDO MySQL enabled, and `storage` is writable.
5. Register a client. To create staff, insert users with role admin/manager/lawyer using a PHP-generated password hash.

This starter implementation includes authentication, registration, role checks, lockout, 30-second reset-token flow, case submission, appointment request, dashboards, SQL schema, and responsive styling. Extend file upload/download and manager assignment screens before production deployment; use HTTPS and a real mail provider for reset links.
