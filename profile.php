<?php
$pageTitle = 'My Profile';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$userId = currentUserId();
$errors = [];

// Handle Profile Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name     = trim($_POST['full_name'] ?? '');
    $university_id = trim($_POST['university_id'] ?? '');
    $department    = trim($_POST['department'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $bio           = trim($_POST['bio'] ?? '');

    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }

    // Check unique university_id if changed
    if (!empty($university_id)) {
        $stmt = $pdo->prepare("SELECT student_id FROM students WHERE university_id = ? AND student_id != ?");
        $stmt->execute([$university_id, $userId]);
        if ($stmt->fetch()) {
            $errors[] = 'University ID is already registered by another student.';
        }
    }

    // Handle Profile Picture Upload
    $profile_picture = null;
    $stmt = $pdo->prepare("SELECT profile_picture FROM students WHERE student_id = ?");
    $stmt->execute([$userId]);
    $existingPic = $stmt->fetchColumn();
    $profile_picture = $existingPic;

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES['profile_picture']['type'];
        $fileSize = $_FILES['profile_picture']['size'];

        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = 'Invalid file type. Only JPG, PNG, GIF, and WEBP images are allowed.';
        } elseif ($fileSize > 2 * 1024 * 1024) {
            $errors[] = 'Image size exceeds 2MB limit.';
        } else {
            $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $newFileName = 'profile_' . $userId . '_' . time() . '.' . strtolower($ext);
            $uploadDir = 'uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $targetPath = $uploadDir . $newFileName;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath)) {
                $profile_picture = $targetPath;
            } else {
                $errors[] = 'Failed to upload photo.';
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE students SET 
            full_name = ?, 
            university_id = ?, 
            department = ?, 
            phone = ?, 
            bio = ?, 
            profile_picture = ? 
            WHERE student_id = ?");
        $stmt->execute([
            $full_name,
            $university_id ?: null,
            $department ?: null,
            $phone ?: null,
            $bio ?: null,
            $profile_picture ?: null,
            $userId
        ]);

        $_SESSION['full_name'] = $full_name;
        flash('success', 'Profile updated successfully!');
        header('Location: profile.php');
        exit;
    }
}

// Fetch Student Profile & Account Details
$stmt = $pdo->prepare("SELECT s.*, u.email, u.created_at AS member_since 
    FROM students s 
    JOIN users u ON s.student_id = u.user_id 
    WHERE s.student_id = ?");
$stmt->execute([$userId]);
$student = $stmt->fetch();

// Fetch Skills taught by this student
$stmt = $pdo->prepare("SELECT ss.student_skill_id, ss.proficiency_level, sk.skill_name, sc.category_name 
    FROM student_skills ss 
    JOIN skills sk ON ss.skill_id = sk.skill_id 
    JOIN skill_categories sc ON sk.category_id = sc.category_id 
    WHERE ss.student_id = ? 
    ORDER BY sc.category_name, sk.skill_name");
$stmt->execute([$userId]);
$mySkills = $stmt->fetchAll();

// Fetch Reviews Received
$stmt = $pdo->prepare("SELECT r.*, st.full_name AS reviewer_name, sk.skill_name 
    FROM reviews r 
    JOIN students st ON r.reviewer_id = st.student_id 
    JOIN sessions ses ON r.session_id = ses.session_id 
    JOIN skill_requests sr ON ses.request_id = sr.request_id 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    WHERE r.reviewee_id = ? 
    ORDER BY r.created_at DESC");
$stmt->execute([$userId]);
$reviews = $stmt->fetchAll();

// Review summary
$stmt = $pdo->prepare("SELECT COALESCE(AVG(rating), 0) AS avg_rating, COUNT(*) AS count FROM reviews WHERE reviewee_id = ?");
$stmt->execute([$userId]);
$ratingStats = $stmt->fetch();
$avgRating = (float)$ratingStats['avg_rating'];
$totalReviews = (int)$ratingStats['count'];

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-person-badge me-2 text-gradient"></i>Student Profile</h1>
    <p class="text-secondary">View and manage your student identity and skill bio.</p>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $err): ?>
            <div><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Left Column: Profile Card & Summary -->
    <div class="col-lg-4">
        <div class="card text-center mb-4">
            <div class="card-body py-4">
                <?php if (!empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                    <img src="<?= htmlspecialchars($student['profile_picture']) ?>" alt="Avatar" class="avatar avatar-lg mb-3">
                <?php else: ?>
                    <div class="avatar-placeholder lg mx-auto mb-3">
                        <?= strtoupper(substr($student['full_name'] ?? 'U', 0, 1)) ?>
                    </div>
                <?php endif; ?>

                <h4 class="mb-1"><?= htmlspecialchars($student['full_name'] ?? 'Student') ?></h4>
                <p class="text-secondary mb-2"><?= htmlspecialchars($student['department'] ?: 'Department not set') ?></p>

                <?php if ($totalReviews > 0): ?>
                    <div class="stars mb-3">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi <?= $i <= round($avgRating) ? 'bi-star-fill' : 'bi-star' ?>"></i>
                        <?php endfor; ?>
                        <span class="ms-1 text-secondary">(<?= number_format($avgRating, 1) ?> / 5 from <?= $totalReviews ?> reviews)</span>
                    </div>
                <?php else: ?>
                    <span class="badge bg-secondary mb-3">No reviews yet</span>
                <?php endif; ?>

                <hr style="border-color: var(--border-color);">

                <div class="text-start px-2">
                    <div class="mb-2">
                        <small class="text-muted d-block"><i class="bi bi-envelope me-1"></i>Email</small>
                        <span class="text-light"><?= htmlspecialchars($student['email'] ?? '') ?></span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block"><i class="bi bi-card-heading me-1"></i>University ID</small>
                        <span class="text-light"><?= htmlspecialchars($student['university_id'] ?: 'Not provided') ?></span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block"><i class="bi bi-telephone me-1"></i>Phone</small>
                        <span class="text-light"><?= htmlspecialchars($student['phone'] ?: 'Not provided') ?></span>
                    </div>
                    <div>
                        <small class="text-muted d-block"><i class="bi bi-calendar-check me-1"></i>Member Since</small>
                        <span class="text-light"><?= date('F j, Y', strtotime($student['member_since'])) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Skills Summary Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-lightbulb me-2 text-warning"></i>My Teaching Skills</span>
                <a href="skills.php" class="btn btn-sm btn-outline-light"><i class="bi bi-gear me-1"></i>Edit</a>
            </div>
            <div class="card-body">
                <?php if (!empty($mySkills)): ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($mySkills as $sk): ?>
                            <span class="skill-tag">
                                <?= htmlspecialchars($sk['skill_name']) ?>
                                <span class="badge badge-<?= strtolower($sk['proficiency_level']) ?> ms-1">
                                    <?= $sk['proficiency_level'] ?>
                                </span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0 small">You haven't listed any teaching skills yet. <a href="skills.php">Add some now</a>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Edit Profile Form & Reviews -->
    <div class="col-lg-8">
        <!-- Edit Profile Card -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-pencil-square me-2 text-accent"></i>Edit Profile Information
            </div>
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="update_profile" value="1">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?= htmlspecialchars($_POST['full_name'] ?? $student['full_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="university_id" class="form-label">University ID / Roll</label>
                            <input type="text" class="form-control" id="university_id" name="university_id" 
                                   value="<?= htmlspecialchars($_POST['university_id'] ?? $student['university_id'] ?? '') ?>" placeholder="e.g. STU-2024-001">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department" class="form-label">Department / Major</label>
                            <input type="text" class="form-control" id="department" name="department" 
                                   value="<?= htmlspecialchars($_POST['department'] ?? $student['department'] ?? '') ?>" placeholder="e.g. Computer Science">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Contact Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" 
                                   value="<?= htmlspecialchars($_POST['phone'] ?? $student['phone'] ?? '') ?>" placeholder="e.g. 01712345678">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="bio" class="form-label">About Me / Learning Interests</label>
                        <textarea class="form-control" id="bio" name="bio" rows="3" 
                                  placeholder="Share what skills you love, what you're passionate about teaching, or what you want to learn..."><?= htmlspecialchars($_POST['bio'] ?? $student['bio'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="profile_picture" class="form-label">Profile Photo (Optional)</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                        <small class="text-muted">Max 2MB (JPG, PNG, GIF, WEBP)</small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Save Changes
                    </button>
                </form>
            </div>
        </div>

        <!-- Reviews Given to this Student -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-chat-quote me-2 text-warning"></i>Student Reviews & Feedback</span>
                <span class="badge bg-secondary"><?= count($reviews) ?> total</span>
            </div>
            <div class="card-body">
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div class="p-3 mb-3" style="background: var(--bg-input); border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <strong><?= htmlspecialchars($rev['reviewer_name']) ?></strong>
                                    <span class="text-muted small ms-2">for learning <span class="text-info"><?= htmlspecialchars($rev['skill_name']) ?></span></span>
                                </div>
                                <div class="stars" style="font-size: 0.9rem;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi <?= $i <= $rev['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php if (!empty($rev['comment'])): ?>
                                <p class="mb-1 text-light small"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>
                            <?php endif; ?>
                            <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('M d, Y h:i A', strtotime($rev['created_at'])) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-star d-block mb-2" style="font-size: 2rem;"></i>
                        No reviews received yet. Complete sessions to build your campus reputation!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
