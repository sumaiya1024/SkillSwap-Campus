<?php
$pageTitle = 'Manage Skill Requests';
$basePath = '../';
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle Delete Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_request') {
    $requestId = (int)($_POST['request_id'] ?? 0);
    if ($requestId > 0) {
        $stmt = $pdo->prepare("DELETE FROM skill_requests WHERE request_id = ?");
        $stmt->execute([$requestId]);
        flash('success', 'Skill request deleted successfully.');
    }
    header('Location: requests.php');
    exit;
}

// Handle Update Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $requestId = (int)($_POST['request_id'] ?? 0);
    $status    = $_POST['status'] ?? '';
    if ($requestId > 0 && in_array($status, ['pending', 'accepted', 'rejected'])) {
        $stmt = $pdo->prepare("UPDATE skill_requests SET status = ? WHERE request_id = ?");
        $stmt->execute([$status, $requestId]);
        flash('success', 'Request status updated to ' . ucfirst($status) . '.');
    }
    header('Location: requests.php');
    exit;
}

// Filter
$statusFilter = trim($_GET['status'] ?? '');
$query = "SELECT sr.*, sk.skill_name, 
    st_req.student_id AS requester_id, st_req.full_name AS requester_name, st_req.department AS requester_dept,
    st_prov.student_id AS provider_id, st_prov.full_name AS provider_name, st_prov.department AS provider_dept,
    (SELECT session_id FROM sessions WHERE request_id = sr.request_id LIMIT 1) AS session_id
    FROM skill_requests sr 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    JOIN students st_req ON sr.requester_id = st_req.student_id 
    JOIN students st_prov ON sr.provider_id = st_prov.student_id";

$params = [];
if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'accepted', 'rejected'])) {
    $query .= " WHERE sr.status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY sr.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
    <div>
        <h1><i class="bi bi-envelope me-2 text-gradient"></i>Manage Skill Requests</h1>
        <p class="text-secondary">Monitor, filter, and moderate all learning and exchange requests across campus.</p>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="dashboard.php" class="btn btn-outline-light"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
    </div>
</div>

<!-- Status Filter Tabs / Buttons -->
<div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="requests.php" class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-outline-light' ?>">
        All Requests (<?= count($requests) ?>)
    </a>
    <a href="requests.php?status=pending" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light' ?>">
        Pending
    </a>
    <a href="requests.php?status=accepted" class="btn btn-sm <?= $statusFilter === 'accepted' ? 'btn-success' : 'btn-outline-light' ?>">
        Accepted
    </a>
    <a href="requests.php?status=rejected" class="btn btn-sm <?= $statusFilter === 'rejected' ? 'btn-danger' : 'btn-outline-light' ?>">
        Rejected
    </a>
</div>

<!-- Requests Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-task me-2 text-primary"></i>All Campus Skill Exchange Requests</span>
        <span class="badge bg-secondary"><?= count($requests) ?> records</span>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($requests)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Learner (Requester)</th>
                            <th>Teacher (Provider)</th>
                            <th>Skill</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td><?= $req['request_id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($req['requester_name']) ?></strong>
                                    <div class="small text-muted"><?= htmlspecialchars($req['requester_dept'] ?: 'Student') ?></div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($req['provider_name']) ?></strong>
                                    <div class="small text-muted"><?= htmlspecialchars($req['provider_dept'] ?: 'Student') ?></div>
                                </td>
                                <td><span class="skill-tag fw-bold"><?= htmlspecialchars($req['skill_name']) ?></span></td>
                                <td>
                                    <small class="text-secondary"><?= htmlspecialchars(mb_strimwidth($req['message'] ?? '—', 0, 45, '...')) ?></small>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="request_id" value="<?= $req['request_id'] ?>">
                                        <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                            <option value="pending" <?= $req['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="accepted" <?= $req['status'] === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                                            <option value="rejected" <?= $req['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                        </select>
                                    </form>
                                </td>
                                <td><small class="text-muted"><?= date('M d, Y', strtotime($req['created_at'])) ?></small></td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this request record?');">
                                        <input type="hidden" name="action" value="delete_request">
                                        <input type="hidden" name="request_id" value="<?= $req['request_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Request">
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
                <i class="bi bi-inbox"></i>
                <h5>No skill requests found</h5>
                <p class="text-muted">There are no exchange requests matching this filter.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
