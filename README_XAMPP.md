# MedicarePlus — XAMPP setup & DB connection guide

Follow these steps to connect this project to a local XAMPP MySQL server and run the PHP backend.

1. Install & start XAMPP

- Download and install XAMPP for Windows: https://www.apachefriends.org
- Start **Apache** and **MySQL** from the XAMPP Control Panel.

2. Place the project in `htdocs`

- Copy the entire `MedicarePlus` project folder into XAMPP's `htdocs` directory, e.g. `C:\xampp\htdocs\MedicarePlus`.

3. Create the database and tables

- Open phpMyAdmin: http://localhost/phpmyadmin
- Create a new database named `MedicarePlus` OR import the provided SQL file:

  - Use the Import tab and choose `create_db.sql` from this project.

4. Configure DB credentials

- Open `dbconnect.php` and update the `$username` and `$password` values to match your MySQL user (default XAMPP is `root` with empty password).

Example (XAMPP default):

```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "MedicarePlus";
```

5. Access the app in a browser

- Visit: http://localhost/MedicarePlus/login.html

6. Notes & next steps

- After importing the schema, create an admin account manually in the `users` table or extend the registration to support an `admin` role.
- The PHP backend files are:
  - `register.php`, `login.php`, `logout.php`
  - `appointments.php`, `contact_submit.php`
  - `dbconnect.php`, `helpers.php`
- The front-end forms have been wired to those endpoints. Use phpMyAdmin to inspect data.

7. Troubleshooting

- If you see database connection errors, confirm MySQL is running and credentials in `dbconnect.php` are correct.
- Check Apache error logs in XAMPP Control Panel -> Apache -> Logs.
