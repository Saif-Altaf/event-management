<?php
// Start or resume the session so $_SESSION is available for login state (user_id, username, role)
session_start();
session_start();

// Database Connection (MySQLi) - credentials for local demo
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'event_management';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Login
// - Validates credentials and stores user information in $_SESSION on success
$login_error = '';
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Direct SQL as requested (No Prepared Statements)
    $sql = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'admin') {
            header("Location: admin_panel.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    } else {
        $login_error = "Invalid email or password.";
    }
}

// Handle Registration
// - Registers a new user (simple demo flow, plain-text passwords for assignment demo only)
$register_success = '';
$register_error = '';
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Simple check if email exists
    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $register_error = "Email already registered.";
    } else {
        $sql = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password', 'user')";
        if ($conn->query($sql) === TRUE) {
            $register_success = "Registration successful! You can now login.";
        } else {
            $register_error = "Error: " . $conn->error;
        }
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Support optional search via GET param `q` (searches title, description, location)
// Fetch Public Events (include approved registrations count)
// - Supports optional search via GET param `q` (server-side LIKE on title/description/location)
// - Adds `approved_count` per event using a scalar subquery (counts registrations with status='approved')
$search = '';
$where = "e.status = 'open'";
if (isset($_GET['q']) && strlen(trim($_GET['q'])) > 0) {
    $search = trim($_GET['q']);
    $esc = $conn->real_escape_string($search);
    $where .= " AND (e.title LIKE '%" . $esc . "%' OR e.description LIKE '%" . $esc . "%' OR e.location LIKE '%" . $esc . "%')";
}
$sql = "SELECT e.*, (
         SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.status = 'approved'
      ) as approved_count
      FROM events e WHERE " . $where . " ORDER BY e.start_date ASC";
$events = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management System</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .hero-section {
            background-color: #f8f9fa;
            padding: 80px 0;
            text-align: center;
        }

        .event-card {
            transition: transform 0.2s;
        }

        .event-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>

<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand logo-container" href="index.php">
                <span class="logo-manager">Manage</span>
                <span class="logo-e">IT</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="admin_panel.php">Admin Panel</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link btn btn-danger btn-sm text-white ms-2"
                                href="index.php?logout=true">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="#login-modal" data-bs-toggle="modal">Login</a></li>
                        <li class="nav-item"><a class="nav-link btn btn-primary btn-sm text-white ms-2"
                                href="#register-modal" data-bs-toggle="modal">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold">Manage Events with Ease</h1>
            <p class="lead text-muted">Streamline event planning, task assignments, and team collaboration.</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="#register-modal" data-bs-toggle="modal" class="btn btn-dark btn-lg mt-3">Get Started</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Events Section -->
    <main class="container my-5" id="events">
        <h2 class="text-center mb-4">Upcoming Events</h2>
        <div class="row justify-content-center mb-3">
            <div class="col-md-8">
                <form method="GET" action="index.php" class="d-flex">
                    <input type="search" name="q" class="form-control" placeholder="Search events by title, description or location"
                        value="<?php echo htmlspecialchars($search ?? '', ENT_QUOTES); ?>">
                    <button type="submit" class="btn btn-primary ms-2">Search</button>
                </form>
            </div>
        </div>
        <div class="row">
            <?php if ($events->num_rows > 0): ?>
                <?php while ($row = $events->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <article class="card event-card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $row['title']; ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">
                                    <?php echo date('M d, Y', strtotime($row['start_date'])); ?></h6>
                                <p class="card-text text-truncate"><?php echo $row['description']; ?></p>
                                <p class="small text-muted mb-1"><i class="bi bi-geo-alt"></i> <?php echo $row['location']; ?>
                                </p>
                                <span class="badge bg-success"><?php echo ucfirst($row['status']); ?></span>
                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <div class="d-flex align-items-center">
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <a href="dashboard.php?register_event=<?php echo $row['id']; ?>"
                                            class="btn btn-outline-primary btn-sm flex-grow-1 me-2"
                                            onclick="return confirm('Register for this event?');">View Details &amp; Register</a>
                                    <?php else: ?>
                                        <a href="#login-modal" data-bs-toggle="modal" class="btn btn-outline-dark btn-sm flex-grow-1 me-2">Login to
                                            Register</a>
                                    <?php endif; ?>
                                    <div class="text-muted small d-flex align-items-center" title="<?php echo $row['approved_count']; ?> registered">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" style="margin-right:6px;">
                                            <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z"/>
                                            <path fill-rule="evenodd" d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                        </svg>
                                        <span><?php echo (int)$row['approved_count']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p class="text-muted">No upcoming events found.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Login Modal -->
    <div class="modal fade" id="login-modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Login</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if ($login_error): ?>
                        <div class="alert alert-danger"><?php echo $login_error; ?></div>
                    <?php endif; ?>
                    <form method="POST" action="index.php">
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-dark w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="register-modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sign Up</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if ($register_success): ?>
                        <div class="alert alert-success"><?php echo $register_success; ?></div>
                    <?php endif; ?>
                    <?php if ($register_error): ?>
                        <div class="alert alert-danger"><?php echo $register_error; ?></div>
                    <?php endif; ?>
                    <form method="POST" action="index.php">
                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" name="register" class="btn btn-primary w-100">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php
    // Re-open login modal if error
    if ($login_error) {
        echo "<script>var loginModal = new bootstrap.Modal(document.getElementById('login-modal')); loginModal.show();</script>";
    }
    // Re-open register modal if error or success
    if ($register_success || $register_error) {
        echo "<script>var registerModal = new bootstrap.Modal(document.getElementById('register-modal')); registerModal.show();</script>";
    }
    ?>
</body>

</html>