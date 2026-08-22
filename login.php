<?php
$pageTitle = 'Sign In';
require_once 'config/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) { 
    header('Location: dashboard.php'); 
    exit; 
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email)    $errors[] = 'Please enter your email address.';
    if (!$password) $errors[] = 'Please enter your password.';

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
                $_SESSION['full_name'] = 'Administrator';
                header('Location: admin/dashboard.php');
            }
            exit;
        }
        $errors[] = 'Invalid email or password. Please try again.';
    }
}

include 'includes/header.php';
?>

<div class="auth-wrapper my-5">
    <div class="auth-card">
        <div class="text-center mb-4">
            <div class="avatar-placeholder md mx-auto mb-2" style="background: linear-gradient(135deg, var(--primary), var(--accent));">
                <i class="bi bi-mortarboard fs-3 text-white"></i>
            </div>
            <h2 class="mt-2 fw-bold">Sign In to Campus</h2>
            <p class="subtitle">Enter your student credentials to continue</p>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <?php foreach($errors as $e): ?>
                    <div><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">University Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" placeholder="student@university.edu" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 mt-3">
                <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
            </button>
        </form>

        <div class="text-center mt-3">
            <span class="text-secondary small">New to SkillSwap Campus?</span> 
            <a href="register.php" class="fw-semibold small ms-1">Create an account</a>
        </div>

        <hr style="border-color: var(--border-color);" class="my-4">

        <!-- Demo Accounts Box -->
        <div class="p-3 rounded" style="background: rgba(255, 255, 255, 0.02); border: 1px dashed var(--border-color);">
            <div class="d-flex align-items-center gap-1 mb-2">
                <i class="bi bi-info-circle text-accent"></i>
                <strong class="small text-light">Quick Demo Access</strong>
            </div>
            <div class="small text-secondary mb-1">
                <strong>Student:</strong> <code>alice@university.edu</code> / <code>password123</code>
            </div>
            <div class="small text-secondary">
                <strong>Admin:</strong> <code>admin@skillswap.com</code> / <code>admin123</code>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
