<?php
$pageTitle = 'Dashboard';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

if (isAdmin()) {
    header('Location: admin/dashboard.php');
    exit;
}

$userId = currentUserId();

// Fetch student profile
$stmt = $pdo->prepare("SELECT s.*, u.email FROM students s JOIN users u ON s.student_id = u.user_id WHERE s.student_id = ?");
$stmt->execute([$userId]);
$student = $stmt->fetch();

// Statistics
// 1. My Skills Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM student_skills WHERE student_id = ?");
$stmt->execute([$userId]);
$mySkillsCount = $stmt->fetchColumn();

// 2. Pending Incoming Requests
$stmt = $pdo->prepare("SELECT COUNT(*) FROM skill_requests WHERE provider_id = ? AND status = 'pending'");
$stmt->execute([$userId]);
$pendingRequests = $stmt->fetchColumn();

// 3. Upcoming Scheduled Sessions
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sessions ses 
    JOIN skill_requests sr ON ses.request_id = sr.request_id 
    WHERE (sr.requester_id = ? OR sr.provider_id = ?) AND ses.status = 'scheduled'");
$stmt->execute([$userId, $userId]);
$upcomingSessions = $stmt->fetchColumn();

// 4. Rating & Reviews
$stmt = $pdo->prepare("SELECT COALESCE(AVG(rating), 0) AS avg_rating, COUNT(*) AS review_count FROM reviews WHERE reviewee_id = ?");
$stmt->execute([$userId]);
$ratingData = $stmt->fetch();
$avgRating = (float)$ratingData['avg_rating'];
$reviewCount = (int)$ratingData['review_count'];

// Recent Incoming Requests (Limit 5)
$stmt = $pdo->prepare("SELECT sr.*, sk.skill_name, st.full_name AS requester_name 
    FROM skill_requests sr 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    JOIN students st ON sr.requester_id = st.student_id 
    WHERE sr.provider_id = ? 
    ORDER BY sr.created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$recentRequests = $stmt->fetchAll();

// Upcoming Sessions (Limit 5)
$stmt = $pdo->prepare("SELECT ses.*, sk.skill_name, 
    st_req.full_name AS requester_name, st_prov.full_name AS provider_name,
    sr.requester_id, sr.provider_id
    FROM sessions ses 
    JOIN skill_requests sr ON ses.request_id = sr.request_id 
    JOIN skills sk ON sr.skill_id = sk.skill_id 
    JOIN students st_req ON sr.requester_id = st_req.student_id
    JOIN students st_prov ON sr.provider_id = st_prov.student_id
    WHERE (sr.requester_id = ? OR sr.provider_id = ?) AND ses.status = 'scheduled' 
    ORDER BY ses.session_date ASC, ses.session_time ASC LIMIT 5");
$stmt->execute([$userId, $userId]);
$upcomingSessionsList = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
    <div>
        <h1><i class="bi bi-grid-1x2 me-2 text-gradient"></i>Student Dashboard</h1>
        <p class="text-secondary">Welcome back, <strong><?= htmlspecialchars($student['full_name'] ?? currentUserName()) ?></strong>! Here is an overview of your skill sharing activity.</p>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="browse_students.php" class="btn btn-accent"><i class="bi bi-search me-1"></i>Find Skills</a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="bi bi-lightbulb"></i></div>
            <div class="stat-number text-gradient"><?= $mySkillsCount ?></div>
            <div class="stat-label">My Skills Listed</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="bi bi-inbox"></i></div>
            <div class="stat-number text-gradient"><?= $pendingRequests ?></div>
            <div class="stat-label">Pending Requests</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-number text-gradient"><?= $upcomingSessions ?></div>
            <div class="stat-label">Upcoming Sessions</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="bi bi-star"></i></div>
            <div class="stat-number text-gradient"><?= $avgRating > 0 ? number_format($avgRating, 1) : '—' ?></div>
            <div class="stat-label"><?= $reviewCount ?> Reviews Received</div>
        </div>
    </div>
</div>

<!-- Quick Navigation Bar -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <a href="skills.php" class="btn btn-outline-light w-100 py-2 d-flex align-items-center justify-content-center">
            <i class="bi bi-plus-circle me-2 text-primary"></i>Manage My Skills
        </a>
    </div>
    <div class="col-md-3">
        <a href="browse_students.php" class="btn btn-outline-light w-100 py-2 d-flex align-items-center justify-content-center">
            <i class="bi bi-people me-2 text-accent"></i>Browse Students
        </a>
    </div>
    <div class="col-md-3">
        <a href="requests.php" class="btn btn-outline-light w-100 py-2 d-flex align-items-center justify-content-center">
            <i class="bi bi-envelope me-2 text-warning"></i>Exchange Requests
        </a>
    </div>
    <div class="col-md-3">
        <a href="sessions.php" class="btn btn-outline-light w-100 py-2 d-flex align-items-center justify-content-center">
            <i class="bi bi-calendar-event me-2 text-success"></i>Booked Sessions
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Requests -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-envelope me-2 text-warning"></i>Recent Requests Received</span>
                <a href="requests.php" class="btn btn-sm btn-outline-light">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($recentRequests)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Learner</th>
                                    <th>Skill</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRequests as $req): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($req['requester_name']) ?></strong>
                                        <div class="small text-muted"><?= date('M d, Y', strtotime($req['created_at'])) ?></div>
                                    </td>
                                    <td><span class="skill-tag"><?= htmlspecialchars($req['skill_name']) ?></span></td>
                                    <td>
                                        <span class="badge badge-<?= $req['status'] ?>">
                                            <?= ucfirst($req['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state py-4">
                        <i class="bi bi-inbox"></i>
                        <h6>No requests received yet</h6>
                        <p class="mb-0">Add more teaching skills so peers can find and learn from you!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Upcoming Sessions -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-event me-2 text-success"></i>Upcoming Scheduled Sessions</span>
                <a href="sessions.php" class="btn btn-sm btn-outline-light">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($upcomingSessionsList)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Skill & Partner</th>
                                    <th>Date & Time</th>
                                    <th>Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingSessionsList as $ses): 
                                    $isRequester = ($ses['requester_id'] == $userId);
                                    $partnerName = $isRequester ? $ses['provider_name'] : $ses['requester_name'];
                                    $roleLabel = $isRequester ? '(Teacher: ' . $partnerName . ')' : '(Learner: ' . $partnerName . ')';
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($ses['skill_name']) ?></strong>
                                        <div class="small text-muted"><?= htmlspecialchars($roleLabel) ?></div>
                                    </td>
                                    <td>
                                        <div><?= date('M d, Y', strtotime($ses['session_date'])) ?></div>
                                        <div class="small text-muted"><?= date('h:i A', strtotime($ses['session_time'])) ?> (<?= $ses['duration_minutes'] ?> min)</div>
                                    </td>
                                    <td><small class="text-secondary"><?= htmlspecialchars($ses['location'] ?: 'Not specified') ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state py-4">
                        <i class="bi bi-calendar-x"></i>
                        <h6>No upcoming sessions scheduled</h6>
                        <p class="mb-0">Accept a request or ask a peer to book a session.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
