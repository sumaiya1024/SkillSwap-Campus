<?php
$pageTitle = 'Learning Sessions';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$userId = currentUserId();
$errors = [];

// Handle Create / Schedule Session POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'schedule_session') {
    $requestId       = (int)($_POST['request_id'] ?? 0);
    $session_date    = trim($_POST['session_date'] ?? '');
    $session_time    = trim($_POST['session_time'] ?? '');
    $duration_minutes= (int)($_POST['duration_minutes'] ?? 60);
    $location        = trim($_POST['location'] ?? '');

    if ($requestId <= 0) {
        $errors[] = 'Please select a valid accepted request.';
    } elseif (empty($session_date)) {
        $errors[] = 'Session date is required.';
    } elseif (empty($session_time)) {
        $errors[] = 'Session time is required.';
    } elseif ($duration_minutes < 15 || $duration_minutes > 300) {
        $errors[] = 'Duration must be between 15 and 300 minutes.';
    } else {
        // Validate request belongs to user and is accepted
        $stmt = $pdo->prepare("SELECT request_id FROM skill_requests WHERE request_id = ? AND (requester_id = ? OR provider_id = ?) AND status = 'accepted'");
        $stmt->execute([$requestId, $userId, $userId]);
        if (!$stmt->fetch()) {
            $errors[] = 'Invalid request or request is not yet accepted.';
        } else {
            // Check if session already exists for this request
            $stmt = $pdo->prepare("SELECT session_id FROM sessions WHERE request_id = ?");
            $stmt->execute([$requestId]);
            if ($stmt->fetch()) {
                $errors[] = 'A learning session is already scheduled for this request.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO sessions (request_id, session_date, session_time, duration_minutes, location, status) VALUES (?, ?, ?, ?, ?, 'scheduled')");
                $stmt->execute([$requestId, $session_date, $session_time, $duration_minutes, $location ?: null]);
                flash('success', 'Learning session successfully scheduled!');
                header('Location: sessions.php');
                exit;
            }
        }
    }
}

// Handle Mark Completed / Cancel Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['complete_session', 'cancel_session'])) {
    $sessionId = (int)($_POST['session_id'] ?? 0);
    $action    = $_POST['action'];

    // Verify session belongs to user
    $stmt = $pdo->prepare("SELECT ses.session_id FROM sessions ses 
        JOIN skill_requests sr ON ses.request_id = sr.request_id 
        WHERE ses.session_id = ? AND (sr.requester_id = ? OR sr.provider_id = ?)");
    $stmt->execute([$sessionId, $userId, $userId]);
    
    if ($stmt->fetch()) {
        $newStatus = ($action === 'complete_session') ? 'completed' : 'cancelled';
        $stmt = $pdo->prepare("UPDATE sessions SET status = ? WHERE session_id = ?");
        $stmt->execute([$newStatus, $sessionId]);
        
        if ($action === 'complete_session') {
            flash('success', 'Session marked as completed! You can now write a review.');
        } else {
            flash('success', 'Session cancelled.');
        }
    } else {
        flash('error', 'Unable to update this session.');
    }
    header('Location: sessions.php');
    exit;
}

// Preselected request for scheduling if passed in URL
$scheduleReqId = (int)($_GET['schedule'] ?? 0);

// Fetch Accepted Requests without a session yet (eligible for scheduling)
$stmt = $pdo->prepare("SELECT sr.request_id, sk.skill_name, 
    st_req.full_name AS requester_name, st_prov.full_name AS provider_name,
    sr.requester_id, sr.provider_id 
    FROM skill_requests sr 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    JOIN students st_req ON sr.requester_id = st_req.student_id 
    JOIN students st_prov ON sr.provider_id = st_prov.student_id 
    LEFT JOIN sessions ses ON sr.request_id = ses.request_id 
    WHERE (sr.requester_id = ? OR sr.provider_id = ?) 
      AND sr.status = 'accepted' 
      AND ses.session_id IS NULL");
$stmt->execute([$userId, $userId]);
$schedulableRequests = $stmt->fetchAll();

// Fetch All Sessions for this user
$stmt = $pdo->prepare("SELECT ses.*, sk.skill_name, 
    st_req.student_id AS requester_id, st_req.full_name AS requester_name,
    st_prov.student_id AS provider_id, st_prov.full_name AS provider_name,
    (SELECT COUNT(*) FROM reviews r WHERE r.session_id = ses.session_id AND r.reviewer_id = ?) AS user_reviewed
    FROM sessions ses 
    JOIN skill_requests sr ON ses.request_id = sr.request_id 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    JOIN students st_req ON sr.requester_id = st_req.student_id 
    JOIN students st_prov ON sr.provider_id = st_prov.student_id 
    WHERE sr.requester_id = ? OR sr.provider_id = ? 
    ORDER BY FIELD(ses.status, 'scheduled', 'completed', 'cancelled'), ses.session_date DESC, ses.session_time DESC");
$stmt->execute([$userId, $userId, $userId]);
$sessions = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
    <div>
        <h1><i class="bi bi-calendar-event me-2 text-gradient"></i>Learning Sessions</h1>
        <p class="text-secondary">Schedule and coordinate your 1-on-1 skill exchange sessions.</p>
    </div>
    <?php if (!empty($schedulableRequests)): ?>
        <div class="mt-3 mt-md-0">
            <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#scheduleFormCollapse">
                <i class="bi bi-calendar-plus me-1"></i>Schedule New Session
            </button>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $err): ?>
            <div><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Schedule New Session Form (Collapsible or open if ?schedule= is set) -->
<div class="collapse <?= ($scheduleReqId > 0 || !empty($errors)) ? 'show' : '' ?> mb-4" id="scheduleFormCollapse">
    <div class="card border-primary">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="bi bi-calendar-plus me-2 text-accent"></i>Book a Learning Session</span>
            <button class="btn-close" type="button" data-bs-toggle="collapse" data-bs-target="#scheduleFormCollapse"></button>
        </div>
        <div class="card-body p-4">
            <?php if (!empty($schedulableRequests)): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="schedule_session">

                    <div class="mb-3">
                        <label for="request_id" class="form-label">Select Accepted Skill Request <span class="text-danger">*</span></label>
                        <select class="form-select" id="request_id" name="request_id" required>
                            <option value="">-- Choose request --</option>
                            <?php foreach ($schedulableRequests as $r): 
                                $isReq = ($r['requester_id'] == $userId);
                                $partner = $isReq ? $r['provider_name'] . ' (Teacher)' : $r['requester_name'] . ' (Learner)';
                                $selected = ($scheduleReqId === (int)$r['request_id']) ? 'selected' : '';
                            ?>
                                <option value="<?= $r['request_id'] ?>" <?= $selected ?>>
                                    Skill: <?= htmlspecialchars($r['skill_name']) ?> | Partner: <?= htmlspecialchars($partner) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="session_date" class="form-label">Session Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="session_date" name="session_date" 
                                   min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['session_date'] ?? date('Y-m-d', strtotime('+1 day'))) ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="session_time" class="form-label">Session Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="session_time" name="session_time" 
                                   value="<?= htmlspecialchars($_POST['session_time'] ?? '14:00') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="duration_minutes" class="form-label">Duration</label>
                            <select class="form-select" id="duration_minutes" name="duration_minutes">
                                <option value="30">30 Minutes</option>
                                <option value="45">45 Minutes</option>
                                <option value="60" selected>60 Minutes (1 Hour)</option>
                                <option value="90">90 Minutes (1.5 Hours)</option>
                                <option value="120">120 Minutes (2 Hours)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">Location / Meeting Details</label>
                        <input type="text" class="form-control" id="location" name="location" 
                               placeholder="e.g. Central Library 2nd Floor Study Room 204 or Google Meet link" 
                               value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Confirm Session</button>
                        <button type="button" class="btn btn-outline-light" data-bs-toggle="collapse" data-bs-target="#scheduleFormCollapse">Cancel</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-muted text-center py-2">
                    <p class="mb-0">You do not have any accepted skill requests ready for booking right now. Once a request is accepted in <a href="requests.php">Requests</a>, you can schedule it here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Sessions List Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar3 me-2 text-primary"></i>My Sessions List</span>
        <span class="badge bg-secondary"><?= count($sessions) ?> total</span>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($sessions)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Skill</th>
                            <th>Role & Partner</th>
                            <th>Date & Time</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $ses): 
                            $isRequester = ($ses['requester_id'] == $userId);
                            $myRole = $isRequester ? 'Learner' : 'Teacher';
                            $partnerName = $isRequester ? $ses['provider_name'] : $ses['requester_name'];
                            $partnerRole = $isRequester ? 'Teacher' : 'Learner';
                        ?>
                            <tr>
                                <td>
                                    <strong class="text-light"><?= htmlspecialchars($ses['skill_name']) ?></strong>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($partnerName) ?></strong>
                                    <div class="small text-muted">You are: <span class="badge badge-<?= $isRequester ? 'intermediate' : 'advanced' ?>"><?= $myRole ?></span> (Partner is <?= $partnerRole ?>)</div>
                                </td>
                                <td>
                                    <div><i class="bi bi-calendar-event me-1 text-muted"></i><?= date('M d, Y', strtotime($ses['session_date'])) ?></div>
                                    <div class="small text-muted"><i class="bi bi-clock me-1"></i><?= date('h:i A', strtotime($ses['session_time'])) ?> (<?= $ses['duration_minutes'] ?> min)</div>
                                </td>
                                <td>
                                    <small class="text-secondary">
                                        <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($ses['location'] ?: 'To be decided') ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $ses['status'] ?>">
                                        <?= ucfirst($ses['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if ($ses['status'] === 'scheduled'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="complete_session">
                                            <input type="hidden" name="session_id" value="<?= $ses['session_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-success" title="Mark as Completed">
                                                <i class="bi bi-check-circle me-1"></i>Complete
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Cancel this scheduled session?');">
                                            <input type="hidden" name="action" value="cancel_session">
                                            <input type="hidden" name="session_id" value="<?= $ses['session_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel Session">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    <?php elseif ($ses['status'] === 'completed'): ?>
                                        <?php if ($ses['user_reviewed'] == 0): ?>
                                            <a href="reviews.php?session_id=<?= $ses['session_id'] ?>" class="btn btn-sm btn-accent">
                                                <i class="bi bi-star me-1"></i>Leave Review
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="bi bi-check2 me-1"></i>Reviewed</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Cancelled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state py-5">
                <i class="bi bi-calendar-x"></i>
                <h5>No learning sessions yet</h5>
                <p class="text-muted">Once a skill exchange request is accepted, schedule a session here!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
