<?php
session_start();
// Admin Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}
$conn = new mysqli('localhost', 'root', '', 'event_management');
// --- ACTIONS ---
// Create Event
if (isset($_POST['create_event'])) {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $loc = $_POST['location'];
    $status = $_POST['status'];
    $sensitive = isset($_POST['is_sensitive']) ? 1 : 0;
    $creator = $_SESSION['user_id'];
    $sql = "INSERT INTO events (title, description, start_date, end_date, location, status, is_sensitive, created_by) 
            VALUES ('$title', '$desc', '$start', '$end', '$loc', '$status', $sensitive, $creator)";
    $conn->query($sql);
}
// Update Event
if (isset($_POST['update_event'])) {
    $id = $_POST['event_id'];
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $loc = $_POST['location'];
    $status = $_POST['status'];
    $sensitive = isset($_POST['is_sensitive']) ? 1 : 0;
    $sql = "UPDATE events SET title='$title', description='$desc', start_date='$start', end_date='$end', location='$loc', status='$status', is_sensitive=$sensitive WHERE id=$id";
    $conn->query($sql);
}
// Delete Event
if (isset($_GET['delete_event'])) {
    $id = $_GET['delete_event'];
    $conn->query("DELETE FROM events WHERE id = $id");
    header("Location: admin_panel.php");
    exit();
}
// Assign Task
if (isset($_POST['assign_task'])) {
    $event_id = $_POST['event_id'];
    $staff_id = $_POST['assigned_to'];
    $desc = $_POST['description'];
    $deadline = $_POST['deadline'];
    $conn->query("INSERT INTO tasks (event_id, assigned_to, description, deadline) VALUES ($event_id, $staff_id, '$desc', '$deadline')");
}
// Update Registration Status (Approve/Reject) - accept POST for actions
// ============================= Prantik ========================================
// Feature: Participation & Registration Tracking (admin approval flow)
// Implemented here by admin_panel.php — admin can approve/reject pending registrations.
// Prantik
// This affects the registrations table and participant counts shown on event cards.
if ((isset($_POST['reg_action']) && isset($_POST['reg_id'])) || (isset($_GET['reg_action']) && isset($_GET['reg_id']))) { // using both for flexibility
    // Prefer POST for state changes
    $status = isset($_POST['reg_action']) ? $_POST['reg_action'] : $_GET['reg_action']; // 'approved' or 'rejected'
    $id = (int)(isset($_POST['reg_id']) ? $_POST['reg_id'] : $_GET['reg_id']);

    // Basic validation
    $allowed = ['approved', 'rejected'];
    if (!in_array($status, $allowed)) {
        die('Invalid action');
    }

    // Perform update
    if ($conn->query("UPDATE registrations SET status = '" . $conn->real_escape_string($status) . "' WHERE id = $id") === TRUE) {
        header("Location: admin_panel.php");
        exit();
    } else {
        die("Error updating record: " . $conn->error);
    }
}
// Update User Role
if (isset($_POST['update_role'])) {
    $uid = $_POST['user_id'];
    $new_role = $_POST['role'];
    $conn->query("UPDATE users SET role = '$new_role' WHERE id = $uid");
}
// --- DATA FETCHING ---
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$events = $conn->query("SELECT * FROM events ORDER BY start_date DESC");
$staff_members = $conn->query("SELECT * FROM users WHERE role IN ('coordinator', 'executive', 'senior_executive')");
$pending_registrations = $conn->query("
    SELECT r.id, u.username, e.title 
    FROM registrations r 
    JOIN users u ON r.user_id = u.id 
    JOIN events e ON r.event_id = e.id 
    WHERE r.status = 'pending'
");
// ...existing data fetching...
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand logo-container" href="index.php">
                <span class="logo-manager">Manage</span>
                <span class="logo-e">IT</span>
                <span class="logo-manager"> Admin</span>
            </a>
            <div class="d-flex gap-3">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
                <a href="index.php?logout=true" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>
    <main class="container">
        <!-- KPIs -->
        <section class="row mb-4" aria-label="Key Performance Indicators">
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <h2><?php echo $users->num_rows; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Active Events</h5>
                        <h2><?php echo $events->num_rows; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Pending Approvals</h5>
                        <h2><?php echo $pending_registrations->num_rows; ?></h2>
                    </div>
                </div>
            </div>
        </section>
        <!-- PENDING APPROVALS -->
        <?php if ($pending_registrations->num_rows > 0): ?>
            <section class="card shadow-sm mb-4 border-warning" aria-labelledby="approvals-heading">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0" id="approvals-heading">Pending Registration Requests</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Event</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($req = $pending_registrations->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $req['username']; ?></td>
                                    <td><?php echo $req['title']; ?></td>
                                    <td>
                                        <form method="POST" style="display:inline; margin-right:6px;">
                                            <input type="hidden" name="reg_id" value="<?php echo $req['id']; ?>">
                                            <input type="hidden" name="reg_action" value="approved">
                                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="reg_id" value="<?php echo $req['id']; ?>">
                                            <input type="hidden" name="reg_action" value="rejected">
                                            <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

            <!-- ...assigned tasks moved to dashboard.php as requested... -->
        <div class="row">
            <!-- EVENT MANAGEMENT -->
            <div class="col-lg-8">
                <section class="card shadow-sm mb-4" aria-labelledby="events-heading">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" id="events-heading">Manage Events</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createEvent">New
                            Event</button>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $events->data_seek(0); // Reset pointer
                                while ($evt = $events->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $evt['title']; ?>
                                            <?php if ($evt['is_sensitive'])
                                                echo '<span class="badge bg-secondary">Sensitive</span>'; ?>
                                        </td>
                                        <td><?php echo date('M d', strtotime($evt['start_date'])); ?></td>
                                        <td><?php echo $evt['status']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                data-bs-target="#editEvent<?php echo $evt['id']; ?>">Edit</button>
                                            <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal"
                                                data-bs-target="#assignTask<?php echo $evt['id']; ?>">Assign Task</button>
                                            <a href="admin_panel.php?delete_event=<?php echo $evt['id']; ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Delete event?');">&times;</a>
                                            <!-- Edit Event Modal -->
                                            <div class="modal fade" id="editEvent<?php echo $evt['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header">
                                                                <h5>Edit Event</h5>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="event_id"
                                                                    value="<?php echo $evt['id']; ?>">
                                                                <div class="mb-2">
                                                                    <label>Title</label>
                                                                    <input type="text" name="title" class="form-control"
                                                                        value="<?php echo $evt['title']; ?>" required>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label>Description</label>
                                                                    <textarea name="description" class="form-control"
                                                                        rows="2"><?php echo $evt['description']; ?></textarea>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col mb-2">
                                                                        <label>Start</label>
                                                                        <input type="datetime-local" name="start_date"
                                                                            class="form-control"
                                                                            value="<?php echo date('Y-m-d\TH:i', strtotime($evt['start_date'])); ?>"
                                                                            required>
                                                                    </div>
                                                                    <div class="col mb-2">
                                                                        <label>End</label>
                                                                        <input type="datetime-local" name="end_date"
                                                                            class="form-control"
                                                                            value="<?php echo date('Y-m-d\TH:i', strtotime($evt['end_date'])); ?>"
                                                                            required>
                                                                    </div>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label>Location</label>
                                                                    <input type="text" name="location" class="form-control"
                                                                        value="<?php echo $evt['location']; ?>" required>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label>Status</label>
                                                                    <select name="status" class="form-select">
                                                                        <option value="draft" <?php echo ($evt['status'] == 'draft') ? 'selected' : ''; ?>>
                                                                            Draft</option>
                                                                        <option value="open" <?php echo ($evt['status'] == 'open') ? 'selected' : ''; ?>>Open
                                                                            (Public)</option>
                                                                        <option value="closed" <?php echo ($evt['status'] == 'closed') ? 'selected' : ''; ?>>
                                                                            Closed</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input type="checkbox" name="is_sensitive"
                                                                        class="form-check-input"
                                                                        id="isSens<?php echo $evt['id']; ?>" <?php echo ($evt['is_sensitive']) ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label"
                                                                        for="isSens<?php echo $evt['id']; ?>">Sensitive
                                                                        Event</label>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer"><button type="submit"
                                                                    name="update_event" class="btn btn-primary">Save
                                                                    Changes</button></div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Assign Task Modal (Nested per row for simplicity) -->
                                            <div class="modal fade" id="assignTask<?php echo $evt['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header">
                                                                <h5>Assign Task for "<?php echo $evt['title']; ?>"</h5>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="event_id"
                                                                    value="<?php echo $evt['id']; ?>">
                                                                <div class="mb-2">
                                                                    <label>Description</label>
                                                                    <input type="text" name="description"
                                                                        class="form-control" required>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label>Assign To</label>
                                                                    <select name="assigned_to" class="form-select">
                                                                        <?php
                                                                        $staff_members->data_seek(0);
                                                                        while ($s = $staff_members->fetch_assoc()) {
                                                                            echo "<option value='" . $s['id'] . "'>" . $s['username'] . " (" . $s['role'] . ")</option>";
                                                                        } ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label>Deadline</label>
                                                                    <input type="date" name="deadline" class="form-control"
                                                                        required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer"><button type="submit"
                                                                    name="assign_task"
                                                                    class="btn btn-primary">Assign</button></div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
            <!-- USER MANAGEMENT -->
            <div class="col-lg-4">
                <section class="card shadow-sm mb-4" aria-labelledby="users-heading">
                    <div class="card-header bg-white">
                        <h5 class="mb-0" id="users-heading">Users & Roles</h5>
                    </div>
                    <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                        <ul class="list-group list-group-flush">
                            <?php
                            $users->data_seek(0);
                            while ($u = $users->fetch_assoc()): ?>
                                <li class="list-group-item">
                                    <form method="POST" class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <strong><?php echo $u['username']; ?></strong><br>
                                            <small class="text-muted"><?php echo $u['email']; ?></small>
                                        </div>
                                        <div class="d-flex">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <select name="role" class="form-select form-select-sm" style="width: auto;"
                                                onchange="this.form.submit()">
                                                <option value="user" <?php if ($u['role'] == 'user')
                                                    echo 'selected'; ?>>User
                                                </option>
                                                <option value="executive" <?php if ($u['role'] == 'executive')
                                                    echo 'selected'; ?>>Exec</option>
                                                <option value="coordinator" <?php if ($u['role'] == 'coordinator')
                                                    echo 'selected'; ?>>Coord</option>
                                                <option value="admin" <?php if ($u['role'] == 'admin')
                                                    echo 'selected'; ?>>
                                                    Admin</option>
                                            </select>
                                            <input type="hidden" name="update_role" value="1">
                                        </div>
                                    </form>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </section>
            </div>
        </div>
    </main>
    <!-- Create Event Modal -->
    <div class="modal fade" id="createEvent" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5>Create New Event</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col mb-2">
                                <label>Start</label>
                                <input type="datetime-local" name="start_date" class="form-control" required>
                            </div>
                            <div class="col mb-2">
                                <label>End</label>
                                <input type="datetime-local" name="end_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label>Location</label>
                            <input type="text" name="location" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label>Status</label>
                            <select name="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="open">Open (Public)</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_sensitive" class="form-check-input" id="isSens">
                            <label class="form-check-label" for="isSens">Sensitive Event (Requires Approval)</label>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="submit" name="create_event" class="btn btn-primary">Create
                            Event</button></div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>