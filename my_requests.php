<?php
$pageTitle = 'My Requests';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$userId = getCurrentUserId();

// Get outgoing requests
$stmt = mysqli_prepare($conn, "SELECT sr.*, sk.skill_name, st.full_name as provider_name, st.department as provider_dept
    FROM skill_requests sr 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    JOIN students st ON sr.provider_id = st.student_id 
    WHERE sr.requester_id = ? 
    ORDER BY sr.created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$requests = mysqli_stmt_get_result($stmt);

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-send me-2"></i>My Requests</h1>
    <p>Skill requests you have sent to other students</p>
</div>

<div class="mb-3">
    <a href="incoming_requests.php" class="btn btn-outline-light btn-sm">
        <i class="bi bi-inbox me-1"></i> View Incoming Requests
    </a>
</div>

<?php if (mysqli_num_rows($requests) > 0): ?>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Provider</th>
                        <th>Skill</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($req = mysqli_fetch_assoc($requests)): ?>
                    <tr>
                        <td>
                            <a href="student_detail.php?id=<?php echo $req['provider_id']; ?>">
                                <strong><?php echo htmlspecialchars($req['provider_name']); ?></strong>
                            </a>
                            <br><small class="text-muted"><?php echo htmlspecialchars($req['provider_dept'] ?? ''); ?></small>
                        </td>
                        <td><span class="skill-tag"><?php echo htmlspecialchars($req['skill_name']); ?></span></td>
                        <td>
                            <small class="text-muted">
                                <?php echo $req['message'] ? htmlspecialchars(substr($req['message'], 0, 60)) . (strlen($req['message']) > 60 ? '...' : '') : '—'; ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $req['status']; ?>">
                                <?php echo ucfirst($req['status']); ?>
                            </span>
                        </td>
                        <td><small><?php echo date('M d, Y', strtotime($req['created_at'])); ?></small></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
    <div class="empty-state">
        <i class="bi bi-send"></i>
        <h5>No requests sent yet</h5>
        <p>Browse students and request to learn a skill!</p>
        <a href="browse.php" class="btn btn-primary mt-2">
            <i class="bi bi-search me-1"></i> Browse Students
        </a>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
