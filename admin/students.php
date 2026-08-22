<?php
$pageTitle = 'Manage Students';
$basePath = '../';
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle Delete Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_student') {
    $deleteId = (int)($_POST['student_id'] ?? 0);
    if ($deleteId > 0 && $deleteId !== (int)currentUserId()) {
        // Delete from users table (cascades via FOREIGN KEY to students, student_skills, etc.)
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'student'");
        $stmt->execute([$deleteId]);
        flash('success', 'Student account and associated data removed successfully.');
    } else {
        flash('error', 'Cannot delete this account.');
    }
    header('Location: students.php');
    exit;
}

// Search
$search = trim($_GET['search'] ?? '');
$query = "SELECT u.user_id, u.email, u.created_at, s.full_name, s.university_id, s.department, s.phone,
    (SELECT COUNT(*) FROM student_skills ss WHERE ss.student_id = u.user_id) AS skill_count,
    (SELECT COALESCE(AVG(r.rating), 0) FROM reviews r WHERE r.reviewee_id = u.user_id) AS avg_rating,
    (SELECT COUNT(r.review_id) FROM reviews r WHERE r.reviewee_id = u.user_id) AS review_count
    FROM users u 
    LEFT JOIN students s ON u.user_id = s.student_id 
    WHERE u.role = 'student'";

$params = [];
if (!empty($search)) {
    $query .= " AND (s.full_name LIKE ? OR u.email LIKE ? OR s.university_id LIKE ? OR s.department LIKE ?)";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like];
}

$query .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
    <div>
        <h1><i class="bi bi-people me-2 text-gradient"></i>Manage Students</h1>
        <p class="text-secondary">View and moderate registered campus student accounts.</p>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="dashboard.php" class="btn btn-outline-light"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
    </div>
</div>

<!-- Search Bar -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-9">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 text-muted" style="border-color: var(--border-color);"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search student by name, email, department or student ID...">
                </div>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Search</button>
                <?php if (!empty($search)): ?>
                    <a href="students.php" class="btn btn-outline-light" title="Reset Search"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Students Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-check me-2 text-primary"></i>All Registered Students</span>
        <span class="badge bg-secondary"><?= count($students) ?> students</span>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($students)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student Details</th>
                            <th>University ID</th>
                            <th>Department</th>
                            <th>Skills</th>
                            <th>Rating</th>
                            <th>Joined</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $stu): ?>
                            <tr>
                                <td><?= $stu['user_id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($stu['full_name'] ?? '—') ?></strong>
                                    <div class="small text-muted"><?= htmlspecialchars($stu['email']) ?></div>
                                    <?php if (!empty($stu['phone'])): ?>
                                        <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($stu['phone']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-secondary"><?= htmlspecialchars($stu['university_id'] ?: '—') ?></small></td>
                                <td><small class="text-secondary"><?= htmlspecialchars($stu['department'] ?: '—') ?></small></td>
                                <td><span class="badge badge-intermediate"><?= $stu['skill_count'] ?> skills</span></td>
                                <td>
                                    <?php if ($stu['review_count'] > 0): ?>
                                        <div class="stars small">
                                            <i class="bi bi-star-fill"></i> <?= number_format($stu['avg_rating'], 1) ?>
                                            <span class="text-muted ms-1">(<?= $stu['review_count'] ?>)</span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= date('M d, Y', strtotime($stu['created_at'])) ?></small></td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this student account and all related sessions/skills?');">
                                        <input type="hidden" name="action" value="delete_student">
                                        <input type="hidden" name="student_id" value="<?= $stu['user_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Student">
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
                <i class="bi bi-people"></i>
                <h5>No students found</h5>
                <p class="text-muted">No student records match your query.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
