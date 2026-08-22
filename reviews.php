<?php
$pageTitle = 'Ratings & Reviews';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$userId = currentUserId();
$errors = [];

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    $sessionId = (int)($_POST['session_id'] ?? 0);
    $rating    = (int)($_POST['rating'] ?? 0);
    $comment   = trim($_POST['comment'] ?? '');

    if ($sessionId <= 0) {
        $errors[] = 'Invalid session selected.';
    } elseif ($rating < 1 || $rating > 5) {
        $errors[] = 'Please choose a valid rating between 1 and 5 stars.';
    } else {
        // Validate session is completed and user participated in it
        $stmt = $pdo->prepare("SELECT ses.session_id, sr.requester_id, sr.provider_id 
            FROM sessions ses 
            JOIN skill_requests sr ON ses.request_id = sr.request_id 
            WHERE ses.session_id = ? AND ses.status = 'completed' AND (sr.requester_id = ? OR sr.provider_id = ?)");
        $stmt->execute([$sessionId, $userId, $userId]);
        $session = $stmt->fetch();

        if (!$session) {
            $errors[] = 'Session not found, not completed, or you were not a participant.';
        } else {
            // Check if already reviewed
            $stmt = $pdo->prepare("SELECT review_id FROM reviews WHERE session_id = ? AND reviewer_id = ?");
            $stmt->execute([$sessionId, $userId]);
            if ($stmt->fetch()) {
                $errors[] = 'You have already submitted a review for this session.';
            } else {
                // Determine who is being reviewed (the partner)
                $revieweeId = ($session['requester_id'] == $userId) ? $session['provider_id'] : $session['requester_id'];

                $stmt = $pdo->prepare("INSERT INTO reviews (session_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$sessionId, $userId, $revieweeId, $rating, $comment ?: null]);
                flash('success', 'Thank you! Your review has been published.');
                header('Location: reviews.php');
                exit;
            }
        }
    }
}

// Check if specific session ID is requested for review form
$targetSessionId = (int)($_GET['session_id'] ?? 0);
$reviewTargetSession = null;

if ($targetSessionId > 0) {
    $stmt = $pdo->prepare("SELECT ses.*, sk.skill_name, 
        st_req.student_id AS requester_id, st_req.full_name AS requester_name,
        st_prov.student_id AS provider_id, st_prov.full_name AS provider_name
        FROM sessions ses 
        JOIN skill_requests sr ON ses.request_id = sr.request_id 
        JOIN skills sk ON sr.skill_id = sk.skill_id 
        JOIN students st_req ON sr.requester_id = st_req.student_id 
        JOIN students st_prov ON sr.provider_id = st_prov.student_id 
        WHERE ses.session_id = ? AND ses.status = 'completed' AND (sr.requester_id = ? OR sr.provider_id = ?)");
    $stmt->execute([$targetSessionId, $userId, $userId]);
    $reviewTargetSession = $stmt->fetch();
}

// Completed Sessions pending review by current user
$stmt = $pdo->prepare("SELECT ses.session_id, ses.session_date, sk.skill_name,
    st_req.student_id AS requester_id, st_req.full_name AS requester_name,
    st_prov.student_id AS provider_id, st_prov.full_name AS provider_name 
    FROM sessions ses 
    JOIN skill_requests sr ON ses.request_id = sr.request_id 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    JOIN students st_req ON sr.requester_id = st_req.student_id 
    JOIN students st_prov ON sr.provider_id = st_prov.student_id 
    WHERE ses.status = 'completed' 
      AND (sr.requester_id = ? OR sr.provider_id = ?)
      AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.session_id = ses.session_id AND r.reviewer_id = ?)
    ORDER BY ses.session_date DESC");
$stmt->execute([$userId, $userId, $userId]);
$pendingReviewSessions = $stmt->fetchAll();

// Reviews Received by Current Student
$stmt = $pdo->prepare("SELECT r.*, sk.skill_name, st.full_name AS reviewer_name, st.department AS reviewer_dept 
    FROM reviews r 
    JOIN students st ON r.reviewer_id = st.student_id 
    JOIN sessions ses ON r.session_id = ses.session_id 
    JOIN skill_requests sr ON ses.request_id = sr.request_id 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    WHERE r.reviewee_id = ? 
    ORDER BY r.created_at DESC");
$stmt->execute([$userId]);
$receivedReviews = $stmt->fetchAll();

// Reviews Given by Current Student
$stmt = $pdo->prepare("SELECT r.*, sk.skill_name, st.full_name AS reviewee_name, st.department AS reviewee_dept 
    FROM reviews r 
    JOIN students st ON r.reviewee_id = st.student_id 
    JOIN sessions ses ON r.session_id = ses.session_id 
    JOIN skill_requests sr ON ses.request_id = sr.request_id 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    WHERE r.reviewer_id = ? 
    ORDER BY r.created_at DESC");
$stmt->execute([$userId]);
$givenReviews = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
    <div>
        <h1><i class="bi bi-star me-2 text-gradient"></i>Ratings & Reviews</h1>
        <p class="text-secondary">Share feedback after completing skill sessions and view peer ratings.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $err): ?>
            <div><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Review Submission Box (If session selected or pending reviews exist) -->
<?php if ($reviewTargetSession || !empty($pendingReviewSessions)): ?>
    <div class="card mb-4 border-warning">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="bi bi-pencil me-2 text-warning"></i>Write a Review for a Completed Session</span>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="action" value="submit_review">

                <div class="mb-3">
                    <label for="session_id" class="form-label">Select Session to Review <span class="text-danger">*</span></label>
                    <select class="form-select" id="session_id" name="session_id" required>
                        <?php if ($reviewTargetSession): 
                            $partner = ($reviewTargetSession['requester_id'] == $userId) ? $reviewTargetSession['provider_name'] : $reviewTargetSession['requester_name'];
                        ?>
                            <option value="<?= $reviewTargetSession['session_id'] ?>" selected>
                                Skill: <?= htmlspecialchars($reviewTargetSession['skill_name']) ?> with <?= htmlspecialchars($partner) ?> (<?= date('M d, Y', strtotime($reviewTargetSession['session_date'])) ?>)
                            </option>
                        <?php else: ?>
                            <option value="">-- Choose a completed session --</option>
                            <?php foreach ($pendingReviewSessions as $p): 
                                $partner = ($p['requester_id'] == $userId) ? $p['provider_name'] : $p['requester_name'];
                            ?>
                                <option value="<?= $p['session_id'] ?>">
                                    Skill: <?= htmlspecialchars($p['skill_name']) ?> with <?= htmlspecialchars($partner) ?> (<?= date('M d, Y', strtotime($p['session_date'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Rating <span class="text-danger">*</span></label>
                    <div class="d-flex gap-4 align-items-center">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="rating" id="rate5" value="5" checked>
                            <label class="form-check-label text-warning" for="rate5"><i class="bi bi-star-fill"></i> 5 - Excellent</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="rating" id="rate4" value="4">
                            <label class="form-check-label text-warning" for="rate4"><i class="bi bi-star-fill"></i> 4 - Very Good</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="rating" id="rate3" value="3">
                            <label class="form-check-label text-warning" for="rate3"><i class="bi bi-star-fill"></i> 3 - Average</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="rating" id="rate2" value="2">
                            <label class="form-check-label text-warning" for="rate2"><i class="bi bi-star-fill"></i> 2 - Fair</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="rating" id="rate1" value="1">
                            <label class="form-check-label text-warning" for="rate1"><i class="bi bi-star-fill"></i> 1 - Poor</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="comment" class="form-label">Review / Feedback</label>
                    <textarea class="form-control" id="comment" name="comment" rows="3" placeholder="Share how the skill exchange went, how clear the explanations were, or helpful takeaways..."></textarea>
                </div>

                <button type="submit" class="btn btn-warning text-dark fw-bold"><i class="bi bi-send me-1"></i>Submit Review</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Tabs for Received vs Given Reviews -->
<ul class="nav nav-pills mb-4" id="reviewTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="received-tab" data-bs-toggle="pill" data-bs-target="#received" type="button" role="tab">
            <i class="bi bi-chat-left-quote me-2"></i>Reviews Received
            <span class="badge bg-secondary ms-1"><?= count($receivedReviews) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="given-tab" data-bs-toggle="pill" data-bs-target="#given" type="button" role="tab">
            <i class="bi bi-send-check me-2"></i>Reviews Given
            <span class="badge bg-secondary ms-1"><?= count($givenReviews) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content" id="reviewTabsContent">
    <!-- Tab 1: Received Reviews -->
    <div class="tab-pane fade show active" id="received" role="tabpanel">
        <?php if (!empty($receivedReviews)): ?>
            <div class="row g-3">
                <?php foreach ($receivedReviews as $rev): ?>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($rev['reviewer_name']) ?></h6>
                                        <small class="text-secondary"><?= htmlspecialchars($rev['reviewer_dept'] ?: 'Campus Student') ?></small>
                                    </div>
                                    <div class="stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi <?= $i <= $rev['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <span class="badge badge-beginner">Skill: <?= htmlspecialchars($rev['skill_name']) ?></span>
                                </div>
                                <?php if (!empty($rev['comment'])): ?>
                                    <p class="text-light small mb-2">"<?= nl2br(htmlspecialchars($rev['comment'])) ?>"</p>
                                <?php endif; ?>
                                <small class="text-muted d-block"><i class="bi bi-clock me-1"></i><?= date('M d, Y h:i A', strtotime($rev['created_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state py-5 card">
                <i class="bi bi-star"></i>
                <h5>No reviews received yet</h5>
                <p class="text-muted">Teach sessions to fellow students to earn feedback and star ratings!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tab 2: Given Reviews -->
    <div class="tab-pane fade" id="given" role="tabpanel">
        <?php if (!empty($givenReviews)): ?>
            <div class="row g-3">
                <?php foreach ($givenReviews as $rev): ?>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-0 fw-bold">Review for: <?= htmlspecialchars($rev['reviewee_name']) ?></h6>
                                        <small class="text-secondary"><?= htmlspecialchars($rev['reviewee_dept'] ?: 'Campus Student') ?></small>
                                    </div>
                                    <div class="stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi <?= $i <= $rev['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <span class="badge badge-intermediate">Skill: <?= htmlspecialchars($rev['skill_name']) ?></span>
                                </div>
                                <?php if (!empty($rev['comment'])): ?>
                                    <p class="text-light small mb-2">"<?= nl2br(htmlspecialchars($rev['comment'])) ?>"</p>
                                <?php endif; ?>
                                <small class="text-muted d-block"><i class="bi bi-clock me-1"></i><?= date('M d, Y h:i A', strtotime($rev['created_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state py-5 card">
                <i class="bi bi-chat-square-text"></i>
                <h5>No reviews submitted yet</h5>
                <p class="text-muted">Once you complete a learning session, submit your review here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
