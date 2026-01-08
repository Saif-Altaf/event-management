<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Database Connection
$conn = new mysqli('localhost', 'root', '', 'event_management');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Task Status Update (for Staff)
if (isset($_POST['update_task'])) {
    $task_id = $_POST['task_id'];
    $status = $_POST['status'];
    $conn->query("UPDATE tasks SET status = '$status' WHERE id = $task_id AND assigned_to = $user_id");
}

// Cancel Registration
if (isset($_GET['cancel_event'])) {
    $event_id = $_GET['cancel_event'];
    // Only if pending or approved
    $conn->query("DELETE FROM registrations WHERE event_id = $event_id AND user_id = $user_id");
    header("Location: dashboard.php");
    exit();
}

// Register for an Event (from Index or here)
// ============================= Prantik ========================================
// Feature: Event Discovery & Booking System (booking/registration handler)
// Prantik — booking logic exists here (dashboard.php)
// registration status 'pending' for sensitive events, otherwise 'approved'.
if (isset($_GET['register_event'])) {
    $event_id = $_GET['register_event'];

    // Check if sensitive
    $evt = $conn->query("SELECT is_sensitive FROM events WHERE id = $event_id")->fetch_assoc();
    $status = ($evt['is_sensitive']) ? 'pending' : 'approved';

    // Check duplication
    $check = $conn->query("SELECT id FROM registrations WHERE user_id = $user_id AND event_id = $event_id");
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO registrations (user_id, event_id, status) VALUES ($user_id, $event_id, '$status')");
    }
    header("Location: dashboard.php");
    exit();
}

// Fetch My Registrations
$my_events = $conn->query("
    SELECT e.title, e.start_date, e.location, r.status, r.event_id 
    FROM registrations r 
    JOIN events e ON r.event_id = e.id 
    WHERE r.user_id = $user_id
");

// Fetch My Tasks (if staff)
$my_tasks = null;
if (in_array($role, ['coordinator', 'executive', 'senior_executive'])) {
    $my_tasks = $conn->query("
        SELECT t.*, e.title as event_title 
        FROM tasks t 
        JOIN events e ON t.event_id = e.id 
        WHERE t.assigned_to = $user_id
    ");
}

// Fetch all assigned tasks for admin overview
$all_tasks = null;
if ($role == 'admin') {
    $all_tasks = $conn->query(
        "SELECT t.id, t.description, t.deadline, t.status, e.title as event_title, u.username as assigned_user \n         FROM tasks t \n         JOIN events e ON t.event_id = e.id \n         LEFT JOIN users u ON t.assigned_to = u.id \n         ORDER BY (t.deadline IS NULL), t.deadline ASC"
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - ManageIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand logo-container" href="index.php">
                <span class="logo-manager">Manage</span>
                <span class="logo-e">IT</span>
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><span class="nav-link text-light">Welcome, <?php echo $username; ?>
                            (<?php echo ucfirst($role); ?>)</span></li>
                    <?php if ($role == 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="admin_panel.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link btn btn-danger btn-sm text-white ms-2"
                            href="index.php?logout=true">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-5">

       <!-- My Registrations -->
       <!-- Feature: Participation & Registration Tracking
           Implemented here: this section displays which events the current user has registered for.
           Prantik
       -->
        <section class="card shadow-sm mb-5" aria-labelledby="registrations-heading">
            <div class="card-header bg-white">
                <h4 class="mb-0" id="registrations-heading">My Event Registrations</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($my_events->num_rows > 0): ?>
                                <?php while ($row = $my_events->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['title']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['start_date'])); ?></td>
                                        <td><?php echo $row['location']; ?></td>
                                        <td>
                                            <span
                                                class="badge bg-<?php echo ($row['status'] == 'approved') ? 'success' : (($row['status'] == 'pending') ? 'warning' : 'secondary'); ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="dashboard.php?cancel_event=<?php echo $row['event_id']; ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Are you sure?');">Cancel</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">You haven't registered for any events
                                        yet. <a href="index.php">Browse Events</a></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Assigned Tasks (Staff Only) -->
        <?php if ($my_tasks): ?>
            <section class="card shadow-sm" aria-labelledby="tasks-heading">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0" id="tasks-heading">My Assigned Tasks</h4>
                    <?php if ($role == 'admin'): ?>
                        <a class="btn btn-sm btn-outline-primary" href="#admin-tasks">View All Assigned Tasks</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Event</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                    <th>Update</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($my_tasks->num_rows > 0): ?>
                                    <?php while ($task = $my_tasks->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $task['description']; ?></td>
                                            <td><?php echo $task['event_title']; ?></td>
                                            <td><?php echo date('M d', strtotime($task['deadline'])); ?></td>
                                            <td>
                                                <span
                                                    class="badge bg-<?php echo ($task['status'] == 'completed') ? 'success' : 'primary'; ?>">
                                                    <?php echo ucfirst($task['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" action="dashboard.php" class="d-flex gap-2">
                                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                    <select name="status" class="form-select form-select-sm">
                                                        <option value="pending" <?php echo ($task['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="in_progress" <?php echo ($task['status'] == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                                        <option value="completed" <?php echo ($task['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                    </select>
                                                    <button type="submit" name="update_task"
                                                        class="btn btn-sm btn-dark">Save</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No tasks assigned to you.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- All Assigned Tasks (Admin Only) -->
        <?php if ($role == 'admin' && $all_tasks): ?>
            <section id="admin-tasks" class="card shadow-sm mt-4" aria-labelledby="admin-tasks-heading">
                <div class="card-header bg-white">
                    <h4 class="mb-0" id="admin-tasks-heading">All Assigned Tasks</h4>
                </div>
                <div class="card-body">
                    <?php if ($all_tasks->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Event</th>
                                        <th>Assigned To</th>
                                        <th>Deadline</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($t = $all_tasks->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($t['description']); ?></td>
                                            <td><?php echo htmlspecialchars($t['event_title']); ?></td>
                                            <td><?php echo $t['assigned_user'] ? htmlspecialchars($t['assigned_user']) : '<em>Unassigned</em>'; ?></td>
                                            <td><?php echo $t['deadline'] ? date('M d, Y', strtotime($t['deadline'])) : '-'; ?></td>
                                            <td><?php echo ucfirst($t['status']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No tasks assigned yet.</p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

    </main>

</body>

</html>