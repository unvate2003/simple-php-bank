# Simple Online Bank (Native PHP + Bootstrap) - Learning Demo

This is a simple demo project of an online banking management system written in native PHP, using Bootstrap 5 for the user interface.

**SECURITY WARNING:** This project is **STRICTLY FOR EDUCATIONAL PURPOSES ONLY**. It lacks many essential security features required for a real-world application. **DO NOT USE IN A PRODUCTION ENVIRONMENT.**

## Core Features (Basic)

* New user registration (with demo bonus feature)
* User login / logout
* View account information (Balance)
* View transaction history (with pagination)
* Internal fund transfers between accounts within the system

## Technology Stack

* PHP (Native/Procedural)
* MySQL / MariaDB
* HTML
* Bootstrap 5 (CSS + JS via CDN)
* JavaScript (Vanilla JS for basic AJAX)
* MySQLi Extension (for database interaction)

## Installation Guide for cPanel

1.  **Database:**
    * Create a new Database and User in cPanel (MySQL Databases section).
    * Grant appropriate privileges (e.g., `ALL PRIVILEGES`) to the User on the Database.
    * Import the provided SQL file (`.sql`) into the newly created database using phpMyAdmin.
2.  **Upload Code:**
    * Upload all project files and directories to your web hosting root directory (usually `public_html` or the specific domain/subdomain directory) on cPanel.
3.  **Configuration:**
    * Rename the `config.php.sample` file to `config.php`.
    * Open `config.php` and edit the `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` values with the database details created in Step 1.
    * Update the `BASE_URL` constant to your website's address.
4.  **Check `.htaccess`:** Ensure the `.htaccess` file is uploaded and functioning to protect sensitive files. Try accessing `yourdomain.com/config.php` directly in your browser; you should receive a "Forbidden" error (403), not see the code or a blank page.
5.  **Access:** Open your web browser and navigate to your domain. You should see the registration/login page.

## Notes

* Passwords are securely hashed using PHP's `password_hash()` function.
* Uses **MySQLi Prepared Statements** to provide basic protection against SQL Injection.
* Uses `htmlspecialchars()` for basic protection against Cross-Site Scripting (XSS) when displaying data.
* **Missing:** CSRF Protection, Rate Limiting, Two-Factor Authentication (2FA), advanced Input Validation, detailed Error Logging, complex permission controls, and many other critical security measures.

## License

(Consider adding an open-source license here, for example: MIT License)
