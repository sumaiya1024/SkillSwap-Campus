<?php
$pageTitle = 'Student Profile';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$userId = getCurrentUserId();
$studentId = intval($_GET['id'] ?? 0);

// Redirect to own profile if viewing self
if ($studentId === $userId) {
    header("Location: profile.php");
    exit();
}

// Get student info
$stmt = mysqli_prepare($conn, "SELECT s.*, u.email, u.created_at FROM students s JOIN users u ON s.student_id = u.user_id WHERE s.student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $studentId);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$student) {
    setFlash('error', 'Student not found.');
    header("Location: browse.php");
    exit();
}

$pageTitle = $student['full_name'];

// Get student's skills
$stmt = mysqli_prepare($conn, "SELECT ss.student_skill_id, sk.skill_id, sk.skill_name, sc.category_name, ss.proficiency_level 
    FROM student_skills ss 
    JOIN skills sk ON ss.skill_id = sk.skill_id 
    JOIN skill_categories sc ON sk.category_id = sc.category_id 
    WHERE ss.student_id = ? ORDER BY sc.category_name, sk.skill_name");
mysqli_stmt_bind_param($stmt, "i", $studentId);
mysqli_stmt_execute($stmt);
$skills = mysqli_stmt_get_result($stmt);

// Get average rating
$stmt = mysqli_prepare($conn, "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE reviewee_id = ?");
mysqli_stmt_bind_param($stmt, "i", $studentId);
mysqli_stmt_execute($stmt);
$ratingData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Get reviews
$stmt = mysqli_prepare($conn, "SELECT r.*, st.full_name as reviewer_name FROM reviews r JOIN students st ON r.reviewer_id = st.student_id WHERE r.reviewee_id = ? ORDER BY r.created_at DESC LIMIT 5");
mysqli_stmt_bind_param($stmt, "i", $studentId);
mysqli_stmt_execute($stmt);
$reviews = mysqli_stmt_get_result($stmt);

// Handle skill request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_skill'])) {
    $skillId = intval($_POST['skill_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($skillId > 0) {
        // Check for existing pending request
        $stmt = mysqli_prepare($conn, "SELECT request_id FROM skill_requests WHERE requester_id = ? AND provider_id = ? AND skill_id = ? AND status = 'pending'");
        mysqli_stmt_bind_param($stmt, "iii", $userId, $studentId, $skillId);
        mysqli_stmt_execute($stmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
            setFlash('error', 'You already have a pending request for this skill.');
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO skill_requests (requester_id, provider_id, skill_id, message) VALUES (?, ?, ?, ?)");
            $msgVal = !empty($message) ? $message : null;
            mysqli_stmt_bind_param($stmt, "iiis", $userId, $studentId, $skillId, $msgVal);
            mysqli_stmt_execute($stmt);
            setFlash('success', 'Skill request sent successfully!');
        }
        header("Location: student_detail.php?id=" . $studentId);
        exit();
    }
}

include 'includes/header.php';
?>

<div class="mb-3">
    <a href="browse.php" class="btn btn-outline-light btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Browse
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
                <p class="text-muted mb-2"><?php echo htmlspecialchars($student['department'] ?? 'No department'); ?></p>
                
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
                    <?php if ($student['university_id']): ?>
                    <p class="mb-2"><i class="bi bi-card-text me-2 text-muted"></i><?php echo htmlspecialchars($student['university_id']); ?></p>
                    <?php endif; ?>
                    <p class="mb-0"><i class="bi bi-calendar me-2 text-muted"></i>Joined <?php echo date('M Y', strtotime($student['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <!-- Request Skill -->
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-send me-2"></i>Request to Learn</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="request_skill" value="1">
                    <div class="mb-3">
                        <label for="skill_id" class="form-label">Select Skill</label>
                        <select class="form-select" id="skill_id" name="skill_id" required>
                            <option value="">Choose a skill...</option>
                            <?php 
                            mysqli_data_seek($skills, 0);
                            while ($sk = mysqli_fetch_assoc($skills)): ?>
                                <option value="<?php echo $sk['skill_id']; ?>"><?php echo htmlspecialchars($sk['skill_name']); ?></option>
                            <?php endwhile;
                            mysqli_data_seek($skills, 0);
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message (optional)</label>
                        <textarea class="form-control" id="message" name="message" rows="3" 
                                  placeholder="Tell them why you want to learn this skill..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-accent w-100">
                        <i class="bi bi-send me-1"></i> Send Request
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bio, Skills & Reviews -->
    <div class="col-lg-8">
        <!-- Bio -->
        <?php if ($student['bio']): ?>
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-journal-text me-2"></i>About</div>
            <div class="card-body">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($student['bio'])); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Skills -->
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-lightbulb me-2"></i>Skills</div>
            <div class="card-body">
                <?php 
                mysqli_data_seek($skills, 0);
                if (mysqli_num_rows($skills) > 0): 
                    while ($skill = mysqli_fetch_assoc($skills)): ?>
                        <span class="skill-tag">
                            <?php echo htmlspecialchars($skill['skill_name']); ?>
                            <span class="badge badge-<?php echo strtolower($skill['proficiency_level']); ?> ms-1"><?php echo $skill['proficiency_level']; ?></span>
                        </span>
                    <?php endwhile;
                else: ?>
                    <p class="text-muted mb-0">No skills listed yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reviews -->
        <div class="card">
            <div class="card-header"><i class="bi bi-chat-square-quote me-2"></i>Reviews</div>
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
