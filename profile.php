<?php
$pageTitle = 'My Profile';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$userId = getCurrentUserId();

// Get student info
$stmt = mysqli_prepare($conn, "SELECT s.*, u.email, u.created_at FROM students s JOIN users u ON s.student_id = u.user_id WHERE s.student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Get student's skills
$stmt = mysqli_prepare($conn, "SELECT sk.skill_name, sc.category_name, ss.proficiency_level 
    FROM student_skills ss 
    JOIN skills sk ON ss.skill_id = sk.skill_id 
    JOIN skill_categories sc ON sk.category_id = sc.category_id 
    WHERE ss.student_id = ? ORDER BY sc.category_name, sk.skill_name");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$skills = mysqli_stmt_get_result($stmt);

// Get average rating
$stmt = mysqli_prepare($conn, "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE reviewee_id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$ratingData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Get recent reviews
$stmt = mysqli_prepare($conn, "SELECT r.*, st.full_name as reviewer_name 
    FROM reviews r 
    JOIN students st ON r.reviewer_id = st.student_id 
    WHERE r.reviewee_id = ? 
    ORDER BY r.created_at DESC LIMIT 5");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$reviews = mysqli_stmt_get_result($stmt);

include 'includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-person me-2"></i>My Profile</h1>
    </div>
    <a href="edit_profile.php" class="btn btn-primary">
        <i class="bi bi-pencil me-1"></i> Edit Profile
    </a>
</div>

<div class="row g-4">
    <!-- Profile Card -->
    <div class="col-lg-4">
        <div class="card text-center">
            <div class="card-body py-4">
                <?php if ($student['profile_picture'] && file_exists($student['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="Profile" class="avatar avatar-lg mb-3">
                <?php else: ?>
                    <div class="avatar-placeholder lg mx-auto mb-3">
                        <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>

                <h4><?php echo htmlspecialchars($student['full_name']); ?></h4>
                <p class="text-muted mb-2"><?php echo htmlspecialchars($student['department'] ?? 'No department set'); ?></p>
                
                <?php if ($ratingData['review_count'] > 0): ?>
                <div class="stars mb-2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="bi <?php echo $i <= round($ratingData['avg_rating']) ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                    <?php endfor; ?>
                    <span class="ms-1 text-muted">(<?php echo $ratingData['review_count']; ?>)</span>
                </div>
                <?php endif; ?>

                <hr style="border-color: var(--border-color);">

                <div class="text-start px-2">
                    <p class="mb-2"><i class="bi bi-envelope me-2 text-muted"></i><?php echo htmlspecialchars($student['email']); ?></p>
                    <?php if ($student['university_id']): ?>
                    <p class="mb-2"><i class="bi bi-card-text me-2 text-muted"></i><?php echo htmlspecialchars($student['university_id']); ?></p>
                    <?php endif; ?>
                    <?php if ($student['phone']): ?>
                    <p class="mb-2"><i class="bi bi-phone me-2 text-muted"></i><?php echo htmlspecialchars($student['phone']); ?></p>
                    <?php endif; ?>
                    <p class="mb-0"><i class="bi bi-calendar me-2 text-muted"></i>Joined <?php echo date('M Y', strtotime($student['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bio & Skills -->
    <div class="col-lg-8">
        <!-- Bio -->
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-journal-text me-2"></i>About Me</div>
            <div class="card-body">
                <p class="mb-0"><?php echo $student['bio'] ? nl2br(htmlspecialchars($student['bio'])) : '<span class="text-muted">No bio added yet.</span>'; ?></p>
            </div>
        </div>

        <!-- Skills -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-lightbulb me-2"></i>Skills I Teach</span>
                <a href="my_skills.php" class="btn btn-sm btn-outline-light">Manage</a>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($skills) > 0): ?>
                    <?php while ($skill = mysqli_fetch_assoc($skills)): ?>
                        <span class="skill-tag">
                            <?php echo htmlspecialchars($skill['skill_name']); ?>
                            <span class="badge badge-<?php echo strtolower($skill['proficiency_level']); ?> ms-1"><?php echo $skill['proficiency_level']; ?></span>
                        </span>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">No skills added yet. <a href="my_skills.php">Add some!</a></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reviews -->
        <div class="card">
            <div class="card-header"><i class="bi bi-chat-square-quote me-2"></i>Recent Reviews</div>
            <div class="card-body">
                <?php if (mysqli_num_rows($reviews) > 0): ?>
                    <?php while ($review = mysqli_fetch_assoc($reviews)): ?>
                        <div class="mb-3 pb-3" style="border-bottom: 1px solid var(--border-color);">
                            <div class="d-flex justify-content-between align-items-start">
                                <strong><?php echo htmlspecialchars($review['reviewer_name']); ?></strong>
                                <div class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi <?php echo $i <= $review['rating'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php if ($review['comment']): ?>
                                <p class="text-muted mt-1 mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
                            <?php endif; ?>
                            <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">No reviews yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
