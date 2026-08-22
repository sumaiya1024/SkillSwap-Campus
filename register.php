<?php
$pageTitle = 'Register';
require_once 'config/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) { header('Location: dashboard.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name     = trim($_POST['full_name'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $university_id = trim($_POST['university_id'] ?? '');
    $department    = trim($_POST['department'] ?? '');
    $password      = $_POST['password'] ?? '';
    $confirm       = $_POST['confirm_password'] ?? '';

    if (!$full_name)                        $errors[] = 'Full name is required.';
    if (!$email)                            $errors[] = 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if (strlen($password) < 6)              $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)             $errors[] = 'Passwords do not match.';

    // Check duplicate email
    if (!$errors) {
        $chk = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) $errors[] = 'Email already registered.';
    }

    // Check duplicate university ID
    if (!$errors && $university_id) {
        $chk = $pdo->prepare("SELECT student_id FROM students WHERE university_id = ?");
        $chk->execute([$university_id]);
        if ($chk->fetch()) $errors[] = 'University ID already taken.';
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'student')");
        $stmt->execute([$email, $hash]);
        $uid = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO students (student_id, full_name, university_id, department) VALUES (?, ?, ?, ?)");
        $stmt->execute([$uid, $full_name, $university_id ?: null, $department ?: null]);
        $pdo->commit();

        flash('success', 'Registration successful! Please login.');
        header('Location: login.php');
        exit;
    }
}

include 'includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="text-center mb-4">
            <i class="bi bi-person-plus" style="font-size:2.5rem;color:var(--accent)"></i>
            <h2 class="mt-2">Create Account</h2>
            <p class="subtitle">Join SkillSwap Campus today</p>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-danger"><?php foreach($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($full_name ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" placeholder="you@university.edu" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="university_id" class="form-label">University ID</label>
                    <input type="text" class="form-control" id="university_id" name="university_id" value="<?= htmlspecialchars($university_id ?? '') ?>" placeholder="STU-2024-001">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="department" class="form-label">Department</label>
                    <input type="text" class="form-control" id="department" name="department" value="<?= htmlspecialchars($department ?? '') ?>" placeholder="Computer Science">
                </div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password" name="password" placeholder="At least 6 characters" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 mt-2"><i class="bi bi-person-plus me-1"></i>Create Account</button>
        </form>
        <div class="text-center mt-3"><span class="text-muted">Already have an account?</span> <a href="login.php">Login here</a></div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
