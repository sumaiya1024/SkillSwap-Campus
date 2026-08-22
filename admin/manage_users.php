<?php
$pageTitle = 'Manage Users';
$basePath = '../';
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $deleteId = intval($_POST['user_id'] ?? 0);
    if ($deleteId > 0 && $deleteId != getCurrentUserId()) {
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ? AND role = 'student'");
        mysqli_stmt_bind_param($stmt, "i", $deleteId);
        mysqli_stmt_execute($stmt);
        setFlash('success', 'User deleted successfully.');
        header("Location: manage_users.php");
        exit();
    }
}

// Get all students
$users = mysqli_query($conn, "SELECT u.user_id, u.email, u.created_at, s.full_name, s.university_id, s.department,
    (SELECT COUNT(*) FROM student_skills ss WHERE ss.student_id = u.user_id) as skill_count,
    (SELECT AVG(r.rating) FROM reviews r WHERE r.reviewee_id = u.user_id) as avg_rating
    FROM users u 
    LEFT JOIN students s ON u.user_id = s.student_id 
    WHERE u.role = 'student' 
    ORDER BY u.created_at DESC");

include '../includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-people me-2"></i>Manage Users</h1>
        <p>View and manage student accounts</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-light btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>University ID</th>
                        <th>Department</th>
                        <th>Skills</th>
                        <th>Rating</th>
                        <th>Joined</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td><?php echo $user['user_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($user['full_name'] ?? '—'); ?></strong></td>
                        <td><small><?php echo htmlspecialchars($user['email']); ?></small></td>
                        <td><small><?php echo htmlspecialchars($user['university_id'] ?? '—'); ?></small></td>
                        <td><small><?php echo htmlspecialchars($user['department'] ?? '—'); ?></small></td>
                        <td><span class="badge badge-intermediate"><?php echo $user['skill_count']; ?></span></td>
                        <td>
                            <?php if ($user['avg_rating']): ?>
                                <span class="stars" style="font-size: 0.8rem;">
                                    <i class="bi bi-star-fill"></i> <?php echo round($user['avg_rating'], 1); ?>
                                </span>
                            <?php else: ?>
                                <small class="text-muted">—</small>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo date('M d, Y', strtotime($user['created_at'])); ?></small></td>
                        <td class="text-end">
                            <form method="POST" class="d-inline" onsubmit="return confirmAction('Delete this user? This cannot be undone.')">
                                <input type="hidden" name="delete_user" value="1">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
