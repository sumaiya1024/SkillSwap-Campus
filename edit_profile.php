<?php
$pageTitle = 'Edit Profile';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$userId = getCurrentUserId();

// Get current student info
$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $university_id = trim($_POST['university_id'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $errors = [];

    if (empty($full_name)) $errors[] = 'Full name is required.';

    // Check university_id uniqueness (excluding self)
    if (!empty($university_id)) {
        $stmt = mysqli_prepare($conn, "SELECT student_id FROM students WHERE university_id = ? AND student_id != ?");
        mysqli_stmt_bind_param($stmt, "si", $university_id, $userId);
        mysqli_stmt_execute($stmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
            $errors[] = 'University ID is already taken.';
        }
    }

    // Handle profile picture upload
    $profile_picture = $student['profile_picture']; // keep existing
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['profile_picture']['type'];
        $fileSize = $_FILES['profile_picture']['size'];

        if (!in_array($fileType, $allowed)) {
            $errors[] = 'Only JPG, PNG and GIF images are allowed.';
        } elseif ($fileSize > 2 * 1024 * 1024) { // 2MB
            $errors[] = 'Image must be smaller than 2MB.';
        } else {
            $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $userId . '_' . time() . '.' . $ext;
            $uploadDir = 'uploads/profiles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $uploadPath = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                $profile_picture = $uploadPath;
            } else {
                $errors[] = 'Failed to upload image.';
            }
        }
    }

    if (empty($errors)) {
        $uni_id = !empty($university_id) ? $university_id : null;
        $dept = !empty($department) ? $department : null;
        $bioVal = !empty($bio) ? $bio : null;
        $phoneVal = !empty($phone) ? $phone : null;
        $picVal = !empty($profile_picture) ? $profile_picture : null;

        $stmt = mysqli_prepare($conn, "UPDATE students SET full_name = ?, university_id = ?, department = ?, bio = ?, phone = ?, profile_picture = ? WHERE student_id = ?");
        mysqli_stmt_bind_param($stmt, "ssssssi", $full_name, $uni_id, $dept, $bioVal, $phoneVal, $picVal, $userId);
        mysqli_stmt_execute($stmt);

        // Update session name
        $_SESSION['full_name'] = $full_name;

        setFlash('success', 'Profile updated successfully!');
        header("Location: profile.php");
        exit();
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-pencil me-2"></i>Edit Profile</h1>
    <p>Update your personal information</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <!-- Profile Picture -->
                    <div class="text-center mb-4">
                        <?php if ($student['profile_picture'] && file_exists($student['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="Profile" class="avatar avatar-lg mb-2" id="imagePreview">
                        <?php else: ?>
                            <div class="avatar-placeholder lg mx-auto mb-2" id="avatarPlaceholder">
                                <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                            </div>
                            <img src="" alt="Preview" class="avatar avatar-lg mb-2" id="imagePreview" style="display: none;">
                        <?php endif; ?>
                        <div>
                            <label for="profile_picture" class="btn btn-sm btn-outline-light mt-2">
                                <i class="bi bi-camera me-1"></i> Change Photo
                            </label>
                            <input type="file" class="d-none" id="profile_picture" name="profile_picture" accept="image/*"
                                   onchange="previewImage(this, 'imagePreview'); document.getElementById('avatarPlaceholder')?.remove();">
                            <div><small class="text-muted">Max 2MB. JPG, PNG, or GIF.</small></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($full_name ?? $student['full_name']); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="university_id" class="form-label">University ID</label>
                            <input type="text" class="form-control" id="university_id" name="university_id"
                                   value="<?php echo htmlspecialchars($university_id ?? $student['university_id'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department"
                                   value="<?php echo htmlspecialchars($department ?? $student['department'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone"
                               value="<?php echo htmlspecialchars($phone ?? $student['phone'] ?? ''); ?>"
                               placeholder="e.g., 01712345678">
                    </div>

                    <div class="mb-3">
                        <label for="bio" class="form-label">Bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="4" 
                                  placeholder="Tell others about yourself..."><?php echo htmlspecialchars($bio ?? $student['bio'] ?? ''); ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Save Changes
                        </button>
                        <a href="profile.php" class="btn btn-outline-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
