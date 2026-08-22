<?php
$pageTitle = 'Manage Sessions';
$basePath = '../';
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle Delete Session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_session') {
    $sessionId = (int)($_POST['session_id'] ?? 0);
    if ($sessionId > 0) {
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        flash('success', 'Session record deleted successfully.');
    }
    header('Location: sessions.php');
    exit;
}

// Handle Update Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $sessionId = (int)($_POST['session_id'] ?? 0);
    $status    = $_POST['status'] ?? '';
    if ($sessionId > 0 && in_array($status, ['scheduled', 'completed', 'cancelled'])) {
        $stmt = $pdo->prepare("UPDATE sessions SET status = ? WHERE session_id = ?");
        $stmt->execute([$status, $sessionId]);
        flash('success', 'Session status updated to ' . ucfirst($status) . '.');
    }
    header('Location: sessions.php');
    exit;
}

// Filter by Status
$statusFilter = trim($_GET['status'] ?? '');
$query = "SELECT ses.*, sk.skill_name, 
    st_req.student_id AS requester_id, st_req.full_name AS requester_name, st_req.department AS requester_dept,
    st_prov.student_id AS provider_id, st_prov.full_name AS provider_name, st_prov.department AS provider_dept,
    (SELECT COUNT(*) FROM reviews r WHERE r.session_id = ses.session_id) AS review_count
    FROM sessions ses 
    JOIN skill_requests sr ON ses.request_id = sr.request_id 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    JOIN students st_req ON sr.requester_id = st_req.student_id 
    JOIN students st_prov ON sr.provider_id = st_prov.student_id";

$params = [];
if (!empty($statusFilter) && in_array($statusFilter, ['scheduled', 'completed', 'cancelled'])) {
    $query .= " WHERE ses.status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY ses.session_date DESC, ses.session_time DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sessions = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
    <div>
        <h1><i class="bi bi-calendar-event me-2 text-gradient"></i>Manage Learning Sessions</h1>
        <p class="text-secondary">Track, update, and manage all scheduled and completed campus skill sessions.</p>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="dashboard.php" class="btn btn-outline-light"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
    </div>
</div>

<!-- Status Filter Tabs / Buttons -->
<div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="sessions.php" class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-outline-light' ?>">
        All Sessions (<?= count($sessions) ?>)
    </a>
    <a href="sessions.php?status=scheduled" class="btn btn-sm <?= $statusFilter === 'scheduled' ? 'btn-primary' : 'btn-outline-light' ?>">
        Scheduled
    </a>
    <a href="sessions.php?status=completed" class="btn btn-sm <?= $statusFilter === 'completed' ? 'btn-success' : 'btn-outline-light' ?>">
        Completed
    </a>
    <a href="sessions.php?status=cancelled" class="btn btn-sm <?= $statusFilter === 'cancelled' ? 'btn-danger' : 'btn-outline-light' ?>">
        Cancelled
    </a>
</div>

<!-- Sessions Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar-check me-2 text-success"></i>Campus Learning Sessions List</span>
        <span class="badge bg-secondary"><?= count($sessions) ?> sessions</span>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($sessions)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Skill</th>
                            <th>Learner</th>
                            <th>Teacher</th>
                            <th>Date & Time</th>
                            <th>Location</th>
                            <th>Reviews</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $ses): ?>
                            <tr>
                                <td><?= $ses['session_id'] ?></td>
                                <td><strong class="text-light"><?= htmlspecialchars($ses['skill_name']) ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($ses['requester_name']) ?></strong>
                                    <div class="small text-muted"><?= htmlspecialchars($ses['requester_dept'] ?: 'Student') ?></div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($ses['provider_name']) ?></strong>
                                    <div class="small text-muted"><?= htmlspecialchars($ses['provider_dept'] ?: 'Student') ?></div>
                                </td>
                                <td>
                                    <div><?= date('M d, Y', strtotime($ses['session_date'])) ?></div>
                                    <small class="text-muted"><?= date('h:i A', strtotime($ses['session_time'])) ?> (<?= $ses['duration_minutes'] ?> min)</small>
                                </td>
                                <td><small class="text-secondary"><?= htmlspecialchars($ses['location'] ?: '—') ?></small></td>
                                <td>
                                    <span class="badge bg-secondary"><?= $ses['review_count'] ?> review(s)</span>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="session_id" value="<?= $ses['session_id'] ?>">
                                        <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                            <option value="scheduled" <?= $ses['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                            <option value="completed" <?= $ses['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="cancelled" <?= $ses['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this session record and its reviews?');">
                                        <input type="hidden" name="action" value="delete_session">
                                        <input type="hidden" name="session_id" value="<?= $ses['session_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Session">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state py-5">
                <i class="bi bi-calendar-x"></i>
                <h5>No learning sessions found</h5>
                <p class="text-muted">No session records match the current filter.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
