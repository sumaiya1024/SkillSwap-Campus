<?php
$pageTitle = 'Skill Exchange Requests';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$userId = currentUserId();

// Handle Accept / Reject Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $requestId = (int)($_POST['request_id'] ?? 0);
    $action    = $_POST['action'];

    if ($requestId > 0 && in_array($action, ['accept', 'reject'])) {
        // Verify this user is the provider for this request and request is pending
        $stmt = $pdo->prepare("SELECT request_id FROM skill_requests WHERE request_id = ? AND provider_id = ? AND status = 'pending'");
        $stmt->execute([$requestId, $userId]);
        if ($stmt->fetch()) {
            $newStatus = ($action === 'accept') ? 'accepted' : 'rejected';
            $stmt = $pdo->prepare("UPDATE skill_requests SET status = ? WHERE request_id = ?");
            $stmt->execute([$newStatus, $requestId]);

            if ($action === 'accept') {
                flash('success', 'Request accepted! You or the learner can now schedule a session under Sessions.');
            } else {
                flash('success', 'Request rejected.');
            }
        } else {
            flash('error', 'Unable to process this request.');
        }
    }
    header('Location: requests.php');
    exit;
}

// Fetch Incoming Requests (Other students asking to learn from this user)
$stmt = $pdo->prepare("SELECT sr.*, sk.skill_name, st.full_name AS requester_name, st.department AS requester_dept,
    (SELECT session_id FROM sessions WHERE request_id = sr.request_id LIMIT 1) AS session_id
    FROM skill_requests sr 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    JOIN students st ON sr.requester_id = st.student_id 
    WHERE sr.provider_id = ? 
    ORDER BY FIELD(sr.status, 'pending', 'accepted', 'rejected'), sr.created_at DESC");
$stmt->execute([$userId]);
$incomingRequests = $stmt->fetchAll();

// Fetch Outgoing Requests (This user requesting to learn from other students)
$stmt = $pdo->prepare("SELECT sr.*, sk.skill_name, st.full_name AS provider_name, st.department AS provider_dept,
    (SELECT session_id FROM sessions WHERE request_id = sr.request_id LIMIT 1) AS session_id
    FROM skill_requests sr 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    JOIN students st ON sr.provider_id = st.student_id 
    WHERE sr.requester_id = ? 
    ORDER BY sr.created_at DESC");
$stmt->execute([$userId]);
$outgoingRequests = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
    <div>
        <h1><i class="bi bi-envelope me-2 text-gradient"></i>Skill Requests</h1>
        <p class="text-secondary">Manage incoming learning requests and check your sent requests.</p>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="browse_students.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Skill Request</a>
    </div>
</div>

<!-- Nav tabs -->
<ul class="nav nav-pills mb-4" id="requestTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="incoming-tab" data-bs-toggle="pill" data-bs-target="#incoming" type="button" role="tab">
            <i class="bi bi-inbox me-2"></i>Incoming Requests
            <span class="badge bg-danger ms-1">
                <?= count(array_filter($incomingRequests, fn($r) => $r['status'] === 'pending')) ?>
            </span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="outgoing-tab" data-bs-toggle="pill" data-bs-target="#outgoing" type="button" role="tab">
            <i class="bi bi-send me-2"></i>Sent Requests
            <span class="badge bg-secondary ms-1"><?= count($outgoingRequests) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content" id="requestTabsContent">
    <!-- Tab 1: Incoming Requests -->
    <div class="tab-pane fade show active" id="incoming" role="tabpanel">
        <?php if (!empty($incomingRequests)): ?>
            <div class="row g-3">
                <?php foreach ($incomingRequests as $req): ?>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5 class="mb-1 fw-bold"><?= htmlspecialchars($req['requester_name']) ?></h5>
                                        <small class="text-secondary"><?= htmlspecialchars($req['requester_dept'] ?: 'Student') ?></small>
                                    </div>
                                    <span class="badge badge-<?= $req['status'] ?>">
                                        <?= ucfirst($req['status']) ?>
                                    </span>
                                </div>

                                <div class="mb-2">
                                    <small class="text-muted d-block">Requested to learn:</small>
                                    <span class="skill-tag fw-bold"><?= htmlspecialchars($req['skill_name']) ?></span>
                                </div>

                                <?php if (!empty($req['message'])): ?>
                                    <div class="p-2 mb-3 rounded" style="background: var(--bg-input); border: 1px solid var(--border-color);">
                                        <small class="text-light fst-italic">"<?= nl2br(htmlspecialchars($req['message'])) ?>"</small>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-auto pt-2 border-top border-secondary-subtle d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('M d, Y h:i A', strtotime($req['created_at'])) ?></small>
                                    
                                    <?php if ($req['status'] === 'pending'): ?>
                                        <div class="d-flex gap-2">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?= $req['request_id'] ?>">
                                                <input type="hidden" name="action" value="accept">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="bi bi-check-lg me-1"></i>Accept
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Reject this request?');">
                                                <input type="hidden" name="request_id" value="<?= $req['request_id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-x-lg me-1"></i>Reject
                                                </button>
                                            </form>
                                        </div>
                                    <?php elseif ($req['status'] === 'accepted'): ?>
                                        <?php if (!empty($req['session_id'])): ?>
                                            <a href="sessions.php" class="btn btn-sm btn-outline-light">
                                                <i class="bi bi-calendar-event me-1"></i>View Session
                                            </a>
                                        <?php else: ?>
                                            <a href="sessions.php?schedule=<?= $req['request_id'] ?>" class="btn btn-sm btn-accent">
                                                <i class="bi bi-calendar-plus me-1"></i>Schedule Session
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state py-5 card">
                <i class="bi bi-inbox"></i>
                <h5>No incoming requests yet</h5>
                <p class="text-muted">When other students request your teaching skills, they'll show up here.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tab 2: Outgoing Requests -->
    <div class="tab-pane fade" id="outgoing" role="tabpanel">
        <?php if (!empty($outgoingRequests)): ?>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Teacher</th>
                                    <th>Skill</th>
                                    <th>My Message</th>
                                    <th>Status</th>
                                    <th>Sent Date</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($outgoingRequests as $req): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($req['provider_name']) ?></strong>
                                            <div class="small text-muted"><?= htmlspecialchars($req['provider_dept'] ?: 'Student') ?></div>
                                        </td>
                                        <td><span class="skill-tag"><?= htmlspecialchars($req['skill_name']) ?></span></td>
                                        <td>
                                            <small class="text-secondary"><?= htmlspecialchars(mb_strimwidth($req['message'] ?? '—', 0, 50, '...')) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $req['status'] ?>">
                                                <?= ucfirst($req['status']) ?>
                                            </span>
                                        </td>
                                        <td><small class="text-muted"><?= date('M d, Y', strtotime($req['created_at'])) ?></small></td>
                                        <td class="text-end">
                                            <?php if ($req['status'] === 'accepted'): ?>
                                                <?php if (!empty($req['session_id'])): ?>
                                                    <a href="sessions.php" class="btn btn-sm btn-outline-light"><i class="bi bi-calendar-event me-1"></i>View Session</a>
                                                <?php else: ?>
                                                    <a href="sessions.php?schedule=<?= $req['request_id'] ?>" class="btn btn-sm btn-accent"><i class="bi bi-calendar-plus me-1"></i>Schedule Session</a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state py-5 card">
                <i class="bi bi-send"></i>
                <h5>You haven't sent any requests yet</h5>
                <p class="text-muted">Browse students on campus and send a request to learn new skills!</p>
                <a href="browse_students.php" class="btn btn-primary btn-sm mt-2"><i class="bi bi-search me-1"></i>Browse Students</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
