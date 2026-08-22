<?php
$pageTitle = 'Browse Students & Skills';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$userId = currentUserId();
$errors = [];

// Handle Skill Request Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_request'])) {
    $providerId = (int)($_POST['provider_id'] ?? 0);
    $skillId    = (int)($_POST['skill_id'] ?? 0);
    $message    = trim($_POST['message'] ?? '');

    if ($providerId <= 0 || $providerId === $userId) {
        $errors[] = 'Invalid student selected.';
    } elseif ($skillId <= 0) {
        $errors[] = 'Please select the skill you wish to learn.';
    } else {
        // Verify provider actually offers this skill
        $stmt = $pdo->prepare("SELECT student_skill_id FROM student_skills WHERE student_id = ? AND skill_id = ?");
        $stmt->execute([$providerId, $skillId]);
        if (!$stmt->fetch()) {
            $errors[] = 'This student does not offer the selected skill.';
        } else {
            // Check for existing pending request for the same skill
            $stmt = $pdo->prepare("SELECT request_id FROM skill_requests WHERE requester_id = ? AND provider_id = ? AND skill_id = ? AND status = 'pending'");
            $stmt->execute([$userId, $providerId, $skillId]);
            if ($stmt->fetch()) {
                $errors[] = 'You already have a pending learning request with this student for that skill.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO skill_requests (requester_id, provider_id, skill_id, message, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->execute([$userId, $providerId, $skillId, $message ?: null]);
                flash('success', 'Learning request sent successfully! You can track it under Requests.');
                header('Location: browse_students.php');
                exit;
            }
        }
    }
}

// Search and Filter parameters
$search   = trim($_GET['search'] ?? '');
$category = (int)($_GET['category'] ?? 0);

// Build student search query
$query = "SELECT DISTINCT st.student_id, st.full_name, st.university_id, st.department, st.bio, st.profile_picture,
    (SELECT COALESCE(AVG(r.rating), 0) FROM reviews r WHERE r.reviewee_id = st.student_id) AS avg_rating,
    (SELECT COUNT(r.review_id) FROM reviews r WHERE r.reviewee_id = st.student_id) AS review_count
    FROM students st 
    JOIN student_skills ss ON st.student_id = ss.student_id 
    JOIN skills sk ON ss.skill_id = sk.skill_id 
    WHERE st.student_id != ?";

$params = [$userId];

if (!empty($search)) {
    $query .= " AND (sk.skill_name LIKE ? OR st.full_name LIKE ? OR st.department LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($category > 0) {
    $query .= " AND sk.category_id = ?";
    $params[] = $category;
}

$query .= " ORDER BY avg_rating DESC, st.full_name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Fetch categories for the filter dropdown
$categories = $pdo->query("SELECT * FROM skill_categories ORDER BY category_name ASC")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-search me-2 text-gradient"></i>Browse Students & Skills</h1>
    <p class="text-secondary">Search peers by skill, category, department or name and request to learn from them.</p>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $err): ?>
            <div><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Search Filter Bar -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 text-muted" style="border-color: var(--border-color);"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search skill (e.g. Python, Guitar, UI/UX) or student...">
                </div>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="category">
                    <option value="0">All Skill Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>" <?= $category === (int)$cat['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <?php if (!empty($search) || $category > 0): ?>
                    <a href="browse_students.php" class="btn btn-outline-light" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Student Cards Grid -->
<?php if (!empty($students)): ?>
    <div class="row g-4">
        <?php foreach ($students as $stu): 
            // Fetch skills for this individual student
            $stmt = $pdo->prepare("SELECT sk.skill_id, sk.skill_name, ss.proficiency_level 
                FROM student_skills ss 
                JOIN skills sk ON ss.skill_id = sk.skill_id 
                WHERE ss.student_id = ?");
            $stmt->execute([$stu['student_id']]);
            $stuSkills = $stmt->fetchAll();
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="student-card">
                <div class="student-info">
                    <?php if (!empty($stu['profile_picture']) && file_exists($stu['profile_picture'])): ?>
                        <img src="<?= htmlspecialchars($stu['profile_picture']) ?>" class="avatar avatar-sm" alt="Photo">
                    <?php else: ?>
                        <div class="avatar-placeholder sm">
                            <?= strtoupper(substr($stu['full_name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div class="student-name"><?= htmlspecialchars($stu['full_name']) ?></div>
                        <div class="student-dept"><?= htmlspecialchars($stu['department'] ?: 'Campus Student') ?></div>
                    </div>
                </div>

                <!-- Rating -->
                <div class="mb-2">
                    <?php if ($stu['review_count'] > 0): ?>
                        <div class="stars small">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi <?= $i <= round($stu['avg_rating']) ? 'bi-star-fill' : 'bi-star' ?>"></i>
                            <?php endfor; ?>
                            <span class="text-muted ms-1">(<?= number_format($stu['avg_rating'], 1) ?> · <?= $stu['review_count'] ?> <?= $stu['review_count'] == 1 ? 'review' : 'reviews' ?>)</span>
                        </div>
                    <?php else: ?>
                        <small class="text-muted"><i class="bi bi-star me-1"></i>New Peer</small>
                    <?php endif; ?>
                </div>

                <?php if (!empty($stu['bio'])): ?>
                    <p class="small text-secondary mb-3" style="min-height: 38px;">
                        <?= htmlspecialchars(mb_strimwidth($stu['bio'], 0, 85, '...')) ?>
                    </p>
                <?php endif; ?>

                <!-- Skills List -->
                <div class="skills-list">
                    <small class="text-muted d-block mb-1">Teaches:</small>
                    <?php foreach ($stuSkills as $sk): ?>
                        <span class="skill-tag" title="<?= $sk['proficiency_level'] ?>">
                            <?= htmlspecialchars($sk['skill_name']) ?>
                            <span class="badge badge-<?= strtolower($sk['proficiency_level']) ?> ms-1"><?= $sk['proficiency_level'] ?></span>
                        </span>
                    <?php endforeach; ?>
                </div>

                <!-- Request Learning Button (Triggers Modal) -->
                <button type="button" class="btn btn-accent btn-sm w-100 mt-3" data-bs-toggle="modal" data-bs-target="#requestModal<?= $stu['student_id'] ?>">
                    <i class="bi bi-send me-1"></i>Request to Learn
                </button>
            </div>
        </div>

        <!-- Request Modal for this student -->
        <div class="modal fade" id="requestModal<?= $stu['student_id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST">
                        <input type="hidden" name="send_request" value="1">
                        <input type="hidden" name="provider_id" value="<?= $stu['student_id'] ?>">
                        
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi bi-send me-2 text-accent"></i>Request Skill from <?= htmlspecialchars($stu['full_name']) ?>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Which skill do you want to learn? <span class="text-danger">*</span></label>
                                <select class="form-select" name="skill_id" required>
                                    <option value="">-- Choose a skill --</option>
                                    <?php foreach ($stuSkills as $sk): ?>
                                        <option value="<?= $sk['skill_id'] ?>">
                                            <?= htmlspecialchars($sk['skill_name']) ?> (<?= $sk['proficiency_level'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message / Learning Goals (Optional)</label>
                                <textarea class="form-control" name="message" rows="3" placeholder="Hi <?= htmlspecialchars($stu['full_name']) ?>, I'd love to learn this skill for my course project..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-accent"><i class="bi bi-send me-1"></i>Send Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state py-5">
        <i class="bi bi-person-x"></i>
        <h5>No students found matching your criteria</h5>
        <p class="text-muted">Try searching with a broader keyword or select a different category.</p>
        <a href="browse_students.php" class="btn btn-outline-light btn-sm mt-2"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset Filters</a>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
