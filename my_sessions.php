<?php
$pageTitle = 'My Sessions';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$userId = getCurrentUserId();

// Handle schedule session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_session'])) {
    $requestId = intval($_POST['request_id'] ?? 0);
    $sessionDate = $_POST['session_date'] ?? '';
    $sessionTime = $_POST['session_time'] ?? '';
    $duration = intval($_POST['duration_minutes'] ?? 60);
    $location = trim($_POST['location'] ?? '');
    $errors = [];

    if (empty($sessionDate)) $errors[] = 'Date is required.';
    if (empty($sessionTime)) $errors[] = 'Time is required.';
    if ($duration < 15 || $duration > 180) $errors[] = 'Duration must be between 15 and 180 minutes.';

    // Verify the request is accepted and belongs to this user
    if ($requestId > 0 && empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT request_id FROM skill_requests WHERE request_id = ? AND (requester_id = ? OR provider_id = ?) AND status = 'accepted'");
        mysqli_stmt_bind_param($stmt, "iii", $requestId, $userId, $userId);
        mysqli_stmt_execute($stmt);
        
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
            $errors[] = 'Invalid request.';
        }

        // Check if session already exists
        $stmt = mysqli_prepare($conn, "SELECT session_id FROM sessions WHERE request_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $requestId);
        mysqli_stmt_execute($stmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
            $errors[] = 'A session already exists for this request.';
        }
    }

    if (empty($errors)) {
        $locVal = !empty($location) ? $location : null;
        $stmt = mysqli_prepare($conn, "INSERT INTO sessions (request_id, session_date, session_time, duration_minutes, location) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issis", $requestId, $sessionDate, $sessionTime, $duration, $locVal);
        mysqli_stmt_execute($stmt);
        setFlash('success', 'Session scheduled successfully!');
        header("Location: my_sessions.php");
        exit();
    }
}

// Handle mark as completed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_session'])) {
    $sessionId = intval($_POST['session_id'] ?? 0);
    
    $stmt = mysqli_prepare($conn, "SELECT ses.session_id FROM sessions ses 
        JOIN skill_requests sr ON ses.request_id = sr.request_id 
        WHERE ses.session_id = ? AND (sr.requester_id = ? OR sr.provider_id = ?) AND ses.status = 'scheduled'");
    mysqli_stmt_bind_param($stmt, "iii", $sessionId, $userId, $userId);
    mysqli_stmt_execute($stmt);
    
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
        $stmt = mysqli_prepare($conn, "UPDATE sessions SET status = 'completed' WHERE session_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $sessionId);
        mysqli_stmt_execute($stmt);
        setFlash('success', 'Session marked as completed! You can now leave a review.');
    }
    header("Location: my_sessions.php");
    exit();
}

// Handle cancel session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_session'])) {
    $sessionId = intval($_POST['session_id'] ?? 0);
    
    $stmt = mysqli_prepare($conn, "SELECT ses.session_id FROM sessions ses 
        JOIN skill_requests sr ON ses.request_id = sr.request_id 
        WHERE ses.session_id = ? AND (sr.requester_id = ? OR sr.provider_id = ?) AND ses.status = 'scheduled'");
    mysqli_stmt_bind_param($stmt, "iii", $sessionId, $userId, $userId);
    mysqli_stmt_execute($stmt);
    
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
        $stmt = mysqli_prepare($conn, "UPDATE sessions SET status = 'cancelled' WHERE session_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $sessionId);
        mysqli_stmt_execute($stmt);
        setFlash('success', 'Session cancelled.');
    }
    header("Location: my_sessions.php");
    exit();
}

// Check if we need to show schedule form
$scheduleRequestId = intval($_GET['schedule'] ?? 0);
$scheduleRequest = null;
if ($scheduleRequestId > 0) {
    $stmt = mysqli_prepare($conn, "SELECT sr.*, sk.skill_name, 
        st_req.full_name as requester_name, st_prov.full_name as provider_name 
        FROM skill_requests sr 
        JOIN skills sk ON sr.skill_id = sk.skill_id
        JOIN students st_req ON sr.requester_id = st_req.student_id
        JOIN students st_prov ON sr.provider_id = st_prov.student_id
        WHERE sr.request_id = ? AND (sr.requester_id = ? OR sr.provider_id = ?) AND sr.status = 'accepted'");
    mysqli_stmt_bind_param($stmt, "iii", $scheduleRequestId, $userId, $userId);
    mysqli_stmt_execute($stmt);
    $scheduleRequest = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

// Get all sessions
$stmt = mysqli_prepare($conn, "SELECT ses.*, sk.skill_name, 
    st_req.full_name as requester_name, st_prov.full_name as provider_name,
    sr.requester_id, sr.provider_id,
    (SELECT COUNT(*) FROM reviews r WHERE r.session_id = ses.session_id AND r.reviewer_id = ?) as has_reviewed
    FROM sessions ses 
    JOIN skill_requests sr ON ses.request_id = sr.request_id 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    JOIN students st_req ON sr.requester_id = st_req.student_id
    JOIN students st_prov ON sr.provider_id = st_prov.student_id
    WHERE sr.requester_id = ? OR sr.provider_id = ?
    ORDER BY FIELD(ses.status, 'scheduled', 'completed', 'cancelled'), ses.session_date DESC, ses.session_time DESC");
mysqli_stmt_bind_param($stmt, "iii", $userId, $userId, $userId);
mysqli_stmt_execute($stmt);
$sessions = mysqli_stmt_get_result($stmt);

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-calendar-event me-2"></i>My Sessions</h1>
    <p>View and manage your skill exchange sessions</p>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
            <div><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Schedule Session Modal -->
<?php if ($scheduleRequest): ?>
<div class="card mb-4 glow-border">
    <div class="card-header">
        <i class="bi bi-calendar-plus me-2"></i>Schedule Session — <?php echo htmlspecialchars($scheduleRequest['skill_name']); ?>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">
            Session between <strong><?php echo htmlspecialchars($scheduleRequest['requester_name']); ?></strong> (learner) 
            and <strong><?php echo htmlspecialchars($scheduleRequest['provider_name']); ?></strong> (teacher)
        </p>
        <form method="POST">
            <input type="hidden" name="schedule_session" value="1">
            <input type="hidden" name="request_id" value="<?php echo $scheduleRequestId; ?>">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="session_date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="session_date" name="session_date" required 
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="session_time" class="form-label">Time</label>
                    <input type="time" class="form-control" id="session_time" name="session_time" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="duration_minutes" class="form-label">Duration (min)</label>
                    <select class="form-select" id="duration_minutes" name="duration_minutes">
                        <option value="30">30 min</option>
                        <option value="60" selected>60 min</option>
                        <option value="90">90 min</option>
                        <option value="120">120 min</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" placeholder="e.g., Library Room 204">
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Schedule
                </button>
                <a href="my_sessions.php" class="btn btn-outline-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Sessions List -->
<?php if (mysqli_num_rows($sessions) > 0): ?>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Skill</th>
                        <th>With</th>
                        <th>Date & Time</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($ses = mysqli_fetch_assoc($sessions)): ?>
                    <tr>
                        <td><span class="skill-tag"><?php echo htmlspecialchars($ses['skill_name']); ?></span></td>
                        <td>
                            <?php 
                            $otherName = ($ses['requester_id'] == $userId) ? $ses['provider_name'] : $ses['requester_name'];
                            $otherRole = ($ses['requester_id'] == $userId) ? 'Teacher' : 'Learner';
                            ?>
                            <strong><?php echo htmlspecialchars($otherName); ?></strong>
                            <br><small class="text-muted"><?php echo $otherRole; ?></small>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($ses['session_date'])); ?>
                            <br><small class="text-muted"><?php echo date('h:i A', strtotime($ses['session_time'])); ?> (<?php echo $ses['duration_minutes']; ?> min)</small>
                        </td>
                        <td><small><?php echo htmlspecialchars($ses['location'] ?? '—'); ?></small></td>
                        <td>
                            <span class="badge badge-<?php echo $ses['status']; ?>">
                                <?php echo ucfirst($ses['status']); ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <?php if ($ses['status'] === 'scheduled'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="session_id" value="<?php echo $ses['session_id']; ?>">
                                    <input type="hidden" name="complete_session" value="1">
                                    <button type="submit" class="btn btn-sm btn-success" title="Mark Complete">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="return confirmAction('Cancel this session?')">
                                    <input type="hidden" name="session_id" value="<?php echo $ses['session_id']; ?>">
                                    <input type="hidden" name="cancel_session" value="1">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Cancel">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            <?php elseif ($ses['status'] === 'completed' && $ses['has_reviewed'] == 0): ?>
                                <a href="add_review.php?session_id=<?php echo $ses['session_id']; ?>" class="btn btn-sm btn-accent">
                                    <i class="bi bi-star me-1"></i> Review
                                </a>
                            <?php elseif ($ses['status'] === 'completed' && $ses['has_reviewed'] > 0): ?>
                                <span class="text-muted"><i class="bi bi-check-circle"></i> Reviewed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
    <div class="empty-state">
        <i class="bi bi-calendar-x"></i>
        <h5>No sessions yet</h5>
        <p>Sessions will appear here once a skill request is accepted and scheduled.</p>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
