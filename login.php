<?php
$pageTitle = 'Login';
require_once 'config/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) { header('Location: dashboard.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email)    $errors[] = 'Email is required.';
    if (!$password) $errors[] = 'Password is required.';

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT user_id, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            if ($user['role'] === 'student') {
                $s = $pdo->prepare("SELECT full_name FROM students WHERE student_id = ?");
                $s->execute([$user['user_id']]);
                $_SESSION['full_name'] = $s->fetchColumn() ?: 'Student';
                header('Location: dashboard.php');
            } else {
                $_SESSION['full_name'] = 'Admin';
                header('Location: admin/dashboard.php');
            }
            exit;
        }
        $errors[] = 'Invalid email or password.';
    }
}

include 'includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="text-center mb-4">
            <i class="bi bi-arrow-left-right" style="font-size:2.5rem;color:var(--accent)"></i>
            <h2 class="mt-2">Welcome Back</h2>
            <p class="subtitle">Sign in to your SkillSwap account</p>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-danger"><?php foreach($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" placeholder="you@university.edu" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 mt-2"><i class="bi bi-box-arrow-in-right me-1"></i>Login</button>
        </form>

        <div class="text-center mt-3">
            <span class="text-muted">Don't have an account?</span> <a href="register.php">Register here</a>
        </div>
        <hr style="border-color:var(--border-color)">
        <div class="text-center">
            <small class="text-muted">Demo: <code>alice@university.edu</code> / <code>password123</code><br>Admin: <code>admin@skillswap.com</code> / <code>admin123</code></small>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
