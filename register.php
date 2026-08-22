<?php
$pageTitle = 'Register';
require_once 'config/db.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $university_id = trim($_POST['university_id'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $errors = [];

    // Validation
    if (empty($full_name)) $errors[] = 'Full name is required.';
    if (empty($email)) $errors[] = 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if (empty($password)) $errors[] = 'Password is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';

    // Check if email already exists
    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_fetch_assoc($result)) {
            $errors[] = 'Email is already registered.';
        }
    }

    // Check if university ID already exists
    if (empty($errors) && !empty($university_id)) {
        $stmt = mysqli_prepare($conn, "SELECT student_id FROM students WHERE university_id = ?");
        mysqli_stmt_bind_param($stmt, "s", $university_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_fetch_assoc($result)) {
            $errors[] = 'University ID is already taken.';
        }
    }

    // Insert if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into users table
        $stmt = mysqli_prepare($conn, "INSERT INTO users (email, password, role) VALUES (?, ?, 'student')");
        mysqli_stmt_bind_param($stmt, "ss", $email, $hashed_password);
        mysqli_stmt_execute($stmt);
        $user_id = mysqli_insert_id($conn);

        // Insert into students table
        $stmt = mysqli_prepare($conn, "INSERT INTO students (student_id, full_name, university_id, department) VALUES (?, ?, ?, ?)");
        $uni_id = !empty($university_id) ? $university_id : null;
        $dept = !empty($department) ? $department : null;
        mysqli_stmt_bind_param($stmt, "isss", $user_id, $full_name, $uni_id, $dept);
        mysqli_stmt_execute($stmt);

        setFlash('success', 'Registration successful! Please login.');
        header("Location: login.php");
        exit();
    }
}

include 'includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="text-center mb-4">
            <i class="bi bi-person-plus" style="font-size: 2.5rem; color: var(--accent);"></i>
            <h2 class="mt-2">Create Account</h2>
            <p class="subtitle">Join SkillSwap Campus today</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="mb-3">
                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="full_name" name="full_name" 
                       value="<?php echo htmlspecialchars($full_name ?? ''); ?>" 
                       placeholder="Enter your full name" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                       placeholder="you@university.edu" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="university_id" class="form-label">University ID</label>
                    <input type="text" class="form-control" id="university_id" name="university_id" 
                           value="<?php echo htmlspecialchars($university_id ?? ''); ?>" 
                           placeholder="e.g., STU-2024-001">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="department" class="form-label">Department</label>
                    <input type="text" class="form-control" id="department" name="department" 
                           value="<?php echo htmlspecialchars($department ?? ''); ?>" 
                           placeholder="e.g., Computer Science">
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="At least 6 characters" required>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                       placeholder="Repeat your password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 mt-2">
                <i class="bi bi-person-plus me-1"></i> Create Account
            </button>
        </form>

        <div class="text-center mt-3">
            <span class="text-muted">Already have an account?</span>
            <a href="login.php" class="ms-1">Login here</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
