<?php
$pageTitle = 'Leave a Review';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$userId = getCurrentUserId();
$sessionId = intval($_GET['session_id'] ?? 0);

// Get session info
$stmt = mysqli_prepare($conn, "SELECT ses.*, sk.skill_name, 
    sr.requester_id, sr.provider_id,
    st_req.full_name as requester_name, st_prov.full_name as provider_name
    FROM sessions ses 
    JOIN skill_requests sr ON ses.request_id = sr.request_id 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    JOIN students st_req ON sr.requester_id = st_req.student_id
    JOIN students st_prov ON sr.provider_id = st_prov.student_id
    WHERE ses.session_id = ? AND ses.status = 'completed' AND (sr.requester_id = ? OR sr.provider_id = ?)");
mysqli_stmt_bind_param($stmt, "iii", $sessionId, $userId, $userId);
mysqli_stmt_execute($stmt);
$session = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$session) {
    setFlash('error', 'Invalid session or session not completed yet.');
    header("Location: my_sessions.php");
    exit();
}

// Check if already reviewed
$stmt = mysqli_prepare($conn, "SELECT review_id FROM reviews WHERE session_id = ? AND reviewer_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $sessionId, $userId);
mysqli_stmt_execute($stmt);
if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
    setFlash('error', 'You have already reviewed this session.');
    header("Location: my_sessions.php");
    exit();
}

// Determine reviewee
$revieweeId = ($session['requester_id'] == $userId) ? $session['provider_id'] : $session['requester_id'];
$revieweeName = ($session['requester_id'] == $userId) ? $session['provider_name'] : $session['requester_name'];

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $errors = [];

    if ($rating < 1 || $rating > 5) $errors[] = 'Please select a rating (1-5).';

    if (empty($errors)) {
        $commentVal = !empty($comment) ? $comment : null;
        $stmt = mysqli_prepare($conn, "INSERT INTO reviews (session_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iiiis", $sessionId, $userId, $revieweeId, $rating, $commentVal);
        mysqli_stmt_execute($stmt);
        setFlash('success', 'Review submitted successfully!');
        header("Location: my_sessions.php");
        exit();
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-star me-2"></i>Leave a Review</h1>
    <p>Rate your experience from the session</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body p-4">
                <!-- Session Summary -->
                <div class="text-center mb-4 pb-3" style="border-bottom: 1px solid var(--border-color);">
                    <span class="skill-tag" style="font-size: 1rem;"><?php echo htmlspecialchars($session['skill_name']); ?></span>
                    <p class="mt-2 mb-1">
                        <strong>Reviewing: <?php echo htmlspecialchars($revieweeName); ?></strong>
                    </p>
                    <small class="text-muted">
                        Session on <?php echo date('M d, Y', strtotime($session['session_date'])); ?> 
                        at <?php echo date('h:i A', strtotime($session['session_time'])); ?>
                    </small>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <!-- Star Rating -->
                    <div class="mb-4 text-center">
                        <label class="form-label d-block">Rating</label>
                        <div class="star-rating-input d-flex justify-content-center gap-2" style="direction: rtl;">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>" class="d-none"
                                       <?php echo (isset($rating) && $rating == $i) ? 'checked' : ''; ?>>
                                <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> star<?php echo $i > 1 ? 's' : ''; ?>">
                                    <i class="bi bi-star-fill"></i>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Comment -->
                    <div class="mb-3">
                        <label for="comment" class="form-label">Comment (optional)</label>
                        <textarea class="form-control" id="comment" name="comment" rows="4" 
                                  placeholder="Share your experience..."><?php echo htmlspecialchars($comment ?? ''); ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-check-lg me-1"></i> Submit Review
                        </button>
                        <a href="my_sessions.php" class="btn btn-outline-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
