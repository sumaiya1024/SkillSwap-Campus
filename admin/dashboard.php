<?php
$pageTitle = 'Admin Dashboard';
$basePath = '../';
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

// Fetch System Overview Metrics
$metrics = $pdo->query("SELECT
    (SELECT COUNT(*) FROM students) AS total_students,
    (SELECT COUNT(*) FROM skills) AS total_skills,
    (SELECT COUNT(*) FROM skill_categories) AS total_categories,
    (SELECT COUNT(*) FROM skill_requests) AS total_requests,
    (SELECT COUNT(*) FROM skill_requests WHERE status = 'pending') AS pending_requests,
    (SELECT COUNT(*) FROM sessions) AS total_sessions,
    (SELECT COUNT(*) FROM sessions WHERE status = 'completed') AS completed_sessions,
    (SELECT COUNT(*) FROM reviews) AS total_reviews,
    (SELECT COALESCE(AVG(rating), 0) FROM reviews) AS avg_rating
")->fetch();

// Recent Registrations
$recentStudents = $pdo->query("SELECT s.*, u.email, u.created_at 
    FROM students s 
    JOIN users u ON s.student_id = u.user_id 
    ORDER BY u.created_at DESC LIMIT 5")->fetchAll();

// Recent Skill Requests
$recentRequests = $pdo->query("SELECT sr.*, sk.skill_name, 
    st_req.full_name AS requester_name, st_prov.full_name AS provider_name 
    FROM skill_requests sr 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    JOIN students st_req ON sr.requester_id = st_req.student_id 
    JOIN students st_prov ON sr.provider_id = st_prov.student_id 
    ORDER BY sr.created_at DESC LIMIT 5")->fetchAll();

// Recent Sessions
$recentSessions = $pdo->query("SELECT ses.*, sk.skill_name, 
    st_req.full_name AS requester_name, st_prov.full_name AS provider_name 
    FROM sessions ses 
    JOIN skill_requests sr ON ses.request_id = sr.request_id 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    JOIN students st_req ON sr.requester_id = st_req.student_id 
    JOIN students st_prov ON sr.provider_id = st_prov.student_id 
    ORDER BY ses.session_date DESC, ses.session_time DESC LIMIT 5")->fetchAll();

include '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-speedometer2 me-2 text-gradient"></i>Admin Control Center</h1>
    <p class="text-secondary">Overview and administration of all students, skills, requests, and campus sessions.</p>
</div>

<!-- Primary Stats Grid -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="bi bi-people"></i></div>
            <div class="stat-number text-gradient"><?= $metrics['total_students'] ?></div>
            <div class="stat-label">Registered Students</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="bi bi-lightbulb"></i></div>
            <div class="stat-number text-gradient"><?= $metrics['total_skills'] ?></div>
            <div class="stat-label">Catalog Skills (<?= $metrics['total_categories'] ?> Cats)</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-envelope"></i></div>
            <div class="stat-number text-gradient"><?= $metrics['total_requests'] ?></div>
            <div class="stat-label"><?= $metrics['pending_requests'] ?> Pending Requests</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-number text-gradient"><?= $metrics['total_sessions'] ?></div>
            <div class="stat-label"><?= $metrics['completed_sessions'] ?> Completed Sessions</div>
        </div>
    </div>
</div>

<!-- Quick Navigation Links -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <a href="students.php" class="btn btn-outline-light w-100 py-3 text-center">
            <i class="bi bi-people d-block fs-4 mb-1 text-primary"></i>
            <strong>Manage Students</strong>
        </a>
    </div>
    <div class="col-md-3">
        <a href="skills.php" class="btn btn-outline-light w-100 py-3 text-center">
            <i class="bi bi-lightbulb d-block fs-4 mb-1 text-accent"></i>
            <strong>Manage Skills & Categories</strong>
        </a>
    </div>
    <div class="col-md-3">
        <a href="requests.php" class="btn btn-outline-light w-100 py-3 text-center">
            <i class="bi bi-envelope d-block fs-4 mb-1 text-warning"></i>
            <strong>Manage Requests</strong>
        </a>
    </div>
    <div class="col-md-3">
        <a href="sessions.php" class="btn btn-outline-light w-100 py-3 text-center">
            <i class="bi bi-calendar-event d-block fs-4 mb-1 text-success"></i>
            <strong>Manage Sessions</strong>
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Recent Students Table -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person-plus me-2 text-primary"></i>Recent Student Registrations</span>
                <a href="students.php" class="btn btn-sm btn-outline-light">All Students</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($recentStudents)): ?>
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
                                <?php foreach ($recentStudents as $s): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($s['full_name']) ?></strong>
                                            <div class="small text-muted"><?= htmlspecialchars($s['email']) ?></div>
                                        </td>
                                        <td><span class="small text-secondary"><?= htmlspecialchars($s['department'] ?: '—') ?></span></td>
                                        <td><small class="text-muted"><?= date('M d, Y', strtotime($s['created_at'])) ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">No students found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Skill Requests Table -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-envelope me-2 text-warning"></i>Recent Skill Requests</span>
                <a href="requests.php" class="btn btn-sm btn-outline-light">All Requests</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($recentRequests)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Exchange</th>
                                    <th>Skill</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRequests as $r): ?>
                                    <tr>
                                        <td>
                                            <small><?= htmlspecialchars($r['requester_name']) ?> &rarr; <?= htmlspecialchars($r['provider_name']) ?></small>
                                        </td>
                                        <td><span class="skill-tag small"><?= htmlspecialchars($r['skill_name']) ?></span></td>
                                        <td>
                                            <span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">No requests found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Sessions Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar3 me-2 text-success"></i>Recent Learning Sessions</span>
        <a href="sessions.php" class="btn btn-sm btn-outline-light">All Sessions</a>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($recentSessions)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Skill</th>
                            <th>Participants</th>
                            <th>Date & Time</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentSessions as $ses): ?>
                            <tr>
                                <td><span class="skill-tag fw-bold"><?= htmlspecialchars($ses['skill_name']) ?></span></td>
                                <td>
                                    <small>Learner: <strong><?= htmlspecialchars($ses['requester_name']) ?></strong> | Teacher: <strong><?= htmlspecialchars($ses['provider_name']) ?></strong></small>
                                </td>
                                <td>
                                    <div><?= date('M d, Y', strtotime($ses['session_date'])) ?></div>
                                    <small class="text-muted"><?= date('h:i A', strtotime($ses['session_time'])) ?></small>
                                </td>
                                <td><small class="text-secondary"><?= htmlspecialchars($ses['location'] ?: '—') ?></small></td>
                                <td>
                                    <span class="badge badge-<?= $ses['status'] ?>"><?= ucfirst($ses['status']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-4 text-center text-muted">No sessions found.</div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
