<?php
$pageTitle = 'Incoming Requests';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$userId = getCurrentUserId();

// Handle accept/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $requestId = intval($_POST['request_id'] ?? 0);
    $action = $_POST['action'];

    if ($requestId > 0 && in_array($action, ['accept', 'reject'])) {
        // Verify this request belongs to the current user as provider
        $stmt = mysqli_prepare($conn, "SELECT request_id FROM skill_requests WHERE request_id = ? AND provider_id = ? AND status = 'pending'");
        mysqli_stmt_bind_param($stmt, "ii", $requestId, $userId);
        mysqli_stmt_execute($stmt);
        
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
            $newStatus = ($action === 'accept') ? 'accepted' : 'rejected';
            $stmt = mysqli_prepare($conn, "UPDATE skill_requests SET status = ? WHERE request_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $newStatus, $requestId);
            mysqli_stmt_execute($stmt);

            if ($action === 'accept') {
                setFlash('success', 'Request accepted! You can now schedule a session.');
            } else {
                setFlash('success', 'Request rejected.');
            }
        }
        header("Location: incoming_requests.php");
        exit();
    }
}

// Get incoming requests
$stmt = mysqli_prepare($conn, "SELECT sr.*, sk.skill_name, st.full_name as requester_name, st.department as requester_dept
    FROM skill_requests sr 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    JOIN students st ON sr.requester_id = st.student_id 
    WHERE sr.provider_id = ? 
    ORDER BY FIELD(sr.status, 'pending', 'accepted', 'rejected'), sr.created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$requests = mysqli_stmt_get_result($stmt);

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-inbox me-2"></i>Incoming Requests</h1>
    <p>Skill requests from students who want to learn from you</p>
</div>

<div class="mb-3">
    <a href="my_requests.php" class="btn btn-outline-light btn-sm">
        <i class="bi bi-send me-1"></i> View My Outgoing Requests
    </a>
</div>

<?php if (mysqli_num_rows($requests) > 0): ?>
<div class="row g-3">
    <?php while ($req = mysqli_fetch_assoc($requests)): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="mb-0">
                            <a href="student_detail.php?id=<?php echo $req['requester_id']; ?>">
                                <?php echo htmlspecialchars($req['requester_name']); ?>
                            </a>
                        </h6>
                        <small class="text-muted"><?php echo htmlspecialchars($req['requester_dept'] ?? ''); ?></small>
                    </div>
                    <span class="badge badge-<?php echo $req['status']; ?>">
                        <?php echo ucfirst($req['status']); ?>
                    </span>
                </div>

                <div class="mb-2">
                    <span class="text-muted">Wants to learn:</span>
                    <span class="skill-tag"><?php echo htmlspecialchars($req['skill_name']); ?></span>
                </div>

                <?php if ($req['message']): ?>
                <div class="mb-2 p-2" style="background: var(--bg-input); border-radius: var(--radius-sm);">
                    <small class="text-muted"><i class="bi bi-chat-dots me-1"></i><?php echo htmlspecialchars($req['message']); ?></small>
                </div>
                <?php endif; ?>

                <small class="text-muted d-block mb-2">
                    <i class="bi bi-clock me-1"></i><?php echo date('M d, Y h:i A', strtotime($req['created_at'])); ?>
                </small>

                <?php if ($req['status'] === 'pending'): ?>
                <div class="d-flex gap-2">
                    <form method="POST" class="flex-fill">
                        <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                        <input type="hidden" name="action" value="accept">
                        <button type="submit" class="btn btn-success btn-sm w-100">
                            <i class="bi bi-check-lg me-1"></i> Accept
                        </button>
                    </form>
                    <form method="POST" class="flex-fill" onsubmit="return confirmAction('Reject this request?')">
                        <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-danger btn-sm w-100">
                            <i class="bi bi-x-lg me-1"></i> Reject
                        </button>
                    </form>
                </div>
                <?php elseif ($req['status'] === 'accepted'): ?>
                    <?php
                    // Check if session already exists for this request
                    $stmtSes = mysqli_prepare($conn, "SELECT session_id FROM sessions WHERE request_id = ?");
                    mysqli_stmt_bind_param($stmtSes, "i", $req['request_id']);
                    mysqli_stmt_execute($stmtSes);
                    $existingSession = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtSes));
                    ?>
                    <?php if (!$existingSession): ?>
                        <a href="my_sessions.php?schedule=<?php echo $req['request_id']; ?>" class="btn btn-accent btn-sm w-100">
                            <i class="bi bi-calendar-plus me-1"></i> Schedule Session
                        </a>
                    <?php else: ?>
                        <a href="my_sessions.php" class="btn btn-outline-light btn-sm w-100">
                            <i class="bi bi-calendar-event me-1"></i> View Session
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php else: ?>
    <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <h5>No incoming requests</h5>
        <p>When students request to learn your skills, they'll appear here.</p>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
