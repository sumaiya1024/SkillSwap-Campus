<?php
$pageTitle = 'Admin Dashboard';
$basePath = '../';
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

// Get stats
$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'student'"))['count'];
$totalSkills = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM skills"))['count'];
$totalRequests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM skill_requests"))['count'];
$totalSessions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM sessions"))['count'];
$totalReviews = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM reviews"))['count'];
$pendingRequests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM skill_requests WHERE status = 'pending'"))['count'];
$completedSessions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM sessions WHERE status = 'completed'"))['count'];
$avgRating = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(rating) as avg FROM reviews"))['avg'];

// Recent registrations
$recentUsers = mysqli_query($conn, "SELECT u.user_id, u.email, u.created_at, s.full_name, s.department 
    FROM users u LEFT JOIN students s ON u.user_id = s.student_id 
    WHERE u.role = 'student' ORDER BY u.created_at DESC LIMIT 5");

// Recent requests
$recentRequests = mysqli_query($conn, "SELECT sr.*, sk.skill_name, 
    st_req.full_name as requester_name, st_prov.full_name as provider_name 
    FROM skill_requests sr 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    JOIN students st_req ON sr.requester_id = st_req.student_id 
    JOIN students st_prov ON sr.provider_id = st_prov.student_id 
    ORDER BY sr.created_at DESC LIMIT 5");

include '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</h1>
    <p>Overview of SkillSwap Campus activity</p>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="bi bi-people"></i></div>
            <div class="stat-number"><?php echo $totalUsers; ?></div>
            <div class="stat-label">Students</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="bi bi-lightbulb"></i></div>
            <div class="stat-number"><?php echo $totalSkills; ?></div>
            <div class="stat-label">Skills</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-arrow-left-right"></i></div>
            <div class="stat-number"><?php echo $totalRequests; ?></div>
            <div class="stat-label">Requests (<?php echo $pendingRequests; ?> pending)</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-number"><?php echo $completedSessions; ?></div>
            <div class="stat-label">Completed Sessions</div>
        </div>
    </div>
</div>

<!-- Quick Links -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <a href="manage_users.php" class="btn btn-primary w-100 py-3">
            <i class="bi bi-people me-1"></i> Manage Users
        </a>
    </div>
    <div class="col-md-4">
        <a href="manage_categories.php" class="btn btn-accent w-100 py-3">
            <i class="bi bi-tags me-1"></i> Manage Categories
        </a>
    </div>
    <div class="col-md-4">
        <a href="manage_skills.php" class="btn btn-outline-light w-100 py-3">
            <i class="bi bi-lightbulb me-1"></i> Manage Skills
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Registrations -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-person-plus me-2"></i>Recent Registrations</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = mysqli_fetch_assoc($recentUsers)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['full_name'] ?? $user['email']); ?></strong></td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($user['department'] ?? '—'); ?></small></td>
                                <td><small><?php echo date('M d', strtotime($user['created_at'])); ?></small></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Requests -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-envelope me-2"></i>Recent Requests</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>From → To</th>
                                <th>Skill</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($req = mysqli_fetch_assoc($recentRequests)): ?>
                            <tr>
                                <td>
                                    <small><?php echo htmlspecialchars($req['requester_name']); ?> → <?php echo htmlspecialchars($req['provider_name']); ?></small>
                                </td>
                                <td><span class="skill-tag"><?php echo htmlspecialchars($req['skill_name']); ?></span></td>
                                <td>
                                    <span class="badge badge-<?php echo $req['status']; ?>"><?php echo ucfirst($req['status']); ?></span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
