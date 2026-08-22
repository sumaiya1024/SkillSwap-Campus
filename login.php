<?php
$pageTitle = 'Login';
require_once 'config/db.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $errors = [];

    if (empty($email)) $errors[] = 'Email is required.';
    if (empty($password)) $errors[] = 'Password is required.';

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT user_id, email, password, role FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Get student name if student
            if ($user['role'] === 'student') {
                $stmtName = mysqli_prepare($conn, "SELECT full_name FROM students WHERE student_id = ?");
                mysqli_stmt_bind_param($stmtName, "i", $user['user_id']);
                mysqli_stmt_execute($stmtName);
                $nameResult = mysqli_stmt_get_result($stmtName);
                $student = mysqli_fetch_assoc($nameResult);
                $_SESSION['full_name'] = $student['full_name'] ?? 'Student';
            } else {
                $_SESSION['full_name'] = 'Admin';
            }

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}

include 'includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="text-center mb-4">
            <i class="bi bi-arrow-left-right" style="font-size: 2.5rem; color: var(--accent);"></i>
            <h2 class="mt-2">Welcome Back</h2>
            <p class="subtitle">Sign in to your SkillSwap account</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                       placeholder="you@university.edu" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 mt-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> Login
            </button>
        </form>

        <div class="text-center mt-3">
            <span class="text-muted">Don't have an account?</span>
            <a href="register.php" class="ms-1">Register here</a>
        </div>

        <hr style="border-color: var(--border-color);">
        <div class="text-center">
            <small class="text-muted">
                Demo: <code>alice@university.edu</code> / <code>password123</code><br>
                Admin: <code>admin@skillswap.com</code> / <code>admin123</code>
            </small>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
