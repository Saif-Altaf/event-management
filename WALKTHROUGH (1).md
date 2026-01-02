# 🚀 Event Management System — Project Walkthrough

This walkthrough explains how to **set up**, **run**, and **verify** the Event Management System on a local machine using **XAMPP**.
You can upload this file directly to GitHub as `WALKTHROUGH.md` or merge it into `README.md`.

---

## 1️⃣ Setup Using XAMPP

### A. Move Project Files
1. Locate your XAMPP installation directory (usually `C:\xampp`).
2. Open the `htdocs` folder:
   ```
   C:\xampp\htdocs
   ```
3. Create a new folder named:
   ```
   event-management
   ```
4. Copy all project files into this folder, including:
   - `index.php`
   - `dashboard.php`
   - `admin_panel.php`
   - `style.css`
   - `database.sql`
   - any additional folders (`css/`, `js/`, `db/`, `admin/`)

Final structure:
```
C:\xampp\htdocs\event-management
```

---

### B. Start Required Services
1. Open **XAMPP Control Panel**.
2. Click **Start** for:
   - Apache
   - MySQL
3. Ensure both services turn **green**.

---

### C. Import the Database
1. Open a browser and go to:
   ```
   http://localhost/phpmyadmin
   ```
2. Click **New** in the left sidebar.
3. Create a database named:
   ```
   event_management
   ```
4. Select the database → click **Import**.
5. Choose the `database.sql` file.
6. Click **Go**.

---

### D. Database Configuration Check
Ensure database credentials match XAMPP defaults in all PHP files:

```php
$conn = new mysqli("localhost", "root", "", "event_management");
```

If you have set a MySQL password, update it everywhere the connection appears.

---

## 2️⃣ Verification & Feature Testing

### A. Authentication
1. Open:
   ```
   http://localhost/event-management/index.php
   ```
2. Register a new user (default role: `user`).
3. Login and verify redirect to `dashboard.php`.
4. Logout and login as **Admin**.
5. Verify **Admin Panel** access is visible.

---

### B. Admin Features
(Login as Admin)

1. **Create Events**
   - Go to `admin_panel.php`
   - Create:
     - One **Public Event**
     - One **Sensitive Event**

2. **Manage Users**
   - Promote a user to `coordinator`

3. **Assign Tasks**
   - Assign a task to the coordinator

---

### C. Coordinator Features
(Login as Coordinator)

1. View assigned tasks
2. Update task status
3. Register for:
   - Public Event → auto-approved
   - Sensitive Event → pending

---

### D. Approval Workflow
(Login as Admin)

1. Open Admin Panel
2. View pending registrations
3. Approve sensitive event request
4. Login as user and confirm approval

---

## 3️⃣ Troubleshooting

### ❌ Database Connection Error
- Check credentials
- Ensure MySQL is running

### ❌ Session Issues
- Ensure `session_start()` is present
- Enable cookies in browser

### ❌ 404 Errors
- Confirm correct folder name:
  ```
  http://localhost/event-management/
  ```

---

## ✅ Setup Complete
If all steps pass, the Event Management System is running correctly with role-based access, task management, approvals, and event workflows.