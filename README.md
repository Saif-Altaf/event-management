# Event Management System - Walkthrough

This guide attempts to help you set up and verify the newly created Event Management System.

## 1. Setup with XAMPP

### A. Move Files
1. Locate your XAMPP installation directory (usually `C:\xampp`).
2. Go to the `htdocs` folder: `C:\xampp\htdocs`.
3. Create a new folder named `event-management`.
4. Copy all project files (`index.php`, `dashboard.php`, `admin_panel.php`, `style.css`, `database.sql`) into `C:\xampp\htdocs\event-management`.

### B. Start Servers
1. Open **XAMPP Control Panel**.
2. Click **Start** for **Apache** and **MySQL**.
3. Ensure they turn green.

### C. Import Database
1. Open your browser and go to `http://localhost/phpmyadmin`.
2. Click **New** in the sidebar -> Create a database named `event_management`.
3. Click "Import" tab at the top.
4. Choose the `database.sql` file from your project folder.
5. Click **Go** at the bottom to run the script.

### D. Configuration Check
- Open `index.php` (and other php files) in a text editor.
- Ensure the database credentials match your XAMPP defaults (usually User: `root`, Password: `` (empty)).
  ```php
  $conn = new mysqli('localhost', 'root', '', 'event_management');
  ```
- If you set a password for MySQL in XAMPP, update it in all 3 PHP files.

## 2. Verification Steps

### A. Authentication
1. **Register**: Go to the homepage (`index.php`), click "Sign Up" and create a new user (Role defaults to `user`).
2. **Login**: Login with the new credentials. You should be redirected to `dashboard.php`.
3. **Admin Login**: Logout and login with the default admin credentials. You should see "Admin Panel" in the nav.

### B. Admin Features (Login as Admin)
1. **Create Event**: 
   - Go to `admin_panel.php`.
   - Click "New Event".
   - Create a Public Event (Status: Open).
   - Create a Sensitive Event (Status: Open, Check "Sensitive").
2. **Manage Users**: 
   - In the sidebar/list, find your new registered user.
   - Change their role to `coordinator`.
3. **Assign Task**:
   - In the Events list, click "Assign Task".
   - Assign a task to the `coordinator` user you just promoted.

### C. User/Staff Features (Login as Coordinator)
1. **Dashboard**:
   - You should see the task assigned by the Admin.
   - Change the task status to "In Progress" or "Completed".
2. **Event Registration**:
   - Go to `dashboard.php` or `index.php`.
   - "Register" for the Public Event -> Status should be `approved` immediately.
   - "Register" for the Sensitive Event -> Status should be `pending`.

### D. Approval Workflow (Login as Admin)
1. Go to `admin_panel.php`.
2. check "Pending Registration Requests".
3. Approve the request for the Sensitive Event.
4. Login as the User again to verify status is now `approved`.

## 3. Troubleshooting
- **Database Connection Error**: Check your MySQL credentials in the top PHP block of every file.
- **Permission Denied**: Ensure `session_start()` is working (cookies enabled).

## 🤝 Contributors
[@Hibrul-Anam-Prantik](https://github.com/Hibrul-Anam-Prantik)

