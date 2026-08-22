<?php
$pageTitle = 'Dashboard';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$userId = getCurrentUserId();

// Get student info
$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Count my skills
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM student_skills WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$mySkillsCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'];

// Count pending incoming requests
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM skill_requests WHERE provider_id = ? AND status = 'pending'");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$pendingRequests = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'];

// Count upcoming sessions (as requester or provider)
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM sessions s JOIN skill_requests sr ON s.request_id = sr.request_id WHERE (sr.requester_id = ? OR sr.provider_id = ?) AND s.status = 'scheduled'");
mysqli_stmt_bind_param($stmt, "ii", $userId, $userId);
mysqli_stmt_execute($stmt);
$upcomingSessions = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'];

// Average rating received
$stmt = mysqli_prepare($conn, "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE reviewee_id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$ratingData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$avgRating = $ratingData['avg_rating'] ? round($ratingData['avg_rating'], 1) : 0;
$reviewCount = $ratingData['review_count'];

// Recent incoming requests
$stmt = mysqli_prepare($conn, "SELECT sr.*, s.skill_name, st.full_name as requester_name 
    FROM skill_requests sr 
    JOIN skills s ON sr.skill_id = s.skill_id 
    JOIN students st ON sr.requester_id = st.student_id 
    WHERE sr.provider_id = ? AND sr.status = 'pending' 
    ORDER BY sr.created_at DESC LIMIT 5");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$recentRequests = mysqli_stmt_get_result($stmt);

// Upcoming sessions
$stmt = mysqli_prepare($conn, "SELECT ses.*, s.skill_name, 
    st_req.full_name as requester_name, st_prov.full_name as provider_name,
    sr.requester_id, sr.provider_id
    FROM sessions ses 
    JOIN skill_requests sr ON ses.request_id = sr.request_id 
    JOIN skills s ON sr.skill_id = s.skill_id 
    JOIN students st_req ON sr.requester_id = st_req.student_id
    JOIN students st_prov ON sr.provider_id = st_prov.student_id
    WHERE (sr.requester_id = ? OR sr.provider_id = ?) AND ses.status = 'scheduled' 
    ORDER BY ses.session_date, ses.session_time LIMIT 5");
mysqli_stmt_bind_param($stmt, "ii", $userId, $userId);
mysqli_stmt_execute($stmt);
$upcomingSessionsList = mysqli_stmt_get_result($stmt);

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-grid-1x2 me-2"></i>Dashboard</h1>
    <p>Welcome back, <?php echo htmlspecialchars($student['full_name']); ?>!</p>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="bi bi-lightbulb"></i></div>
            <div class="stat-number"><?php echo $mySkillsCount; ?></div>
            <div class="stat-label">My Skills</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="bi bi-envelope"></i></div>
            <div class="stat-number"><?php echo $pendingRequests; ?></div>
            <div class="stat-label">Pending Requests</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-calendar-event"></i></div>
            <div class="stat-number"><?php echo $upcomingSessions; ?></div>
            <div class="stat-label">Upcoming Sessions</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="bi bi-star"></i></div>
            <div class="stat-number"><?php echo $avgRating > 0 ? $avgRating : '—'; ?></div>
            <div class="stat-label"><?php echo $reviewCount; ?> Reviews</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <a href="my_skills.php" class="btn btn-primary w-100 py-3">
            <i class="bi bi-plus-circle me-1"></i> Add Skills
        </a>
    </div>
    <div class="col-md-4">
        <a href="browse.php" class="btn btn-accent w-100 py-3">
            <i class="bi bi-search me-1"></i> Browse Students
        </a>
    </div>
    <div class="col-md-4">
        <a href="incoming_requests.php" class="btn btn-outline-light w-100 py-3">
            <i class="bi bi-inbox me-1"></i> View Incoming Requests
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Incoming Requests -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-envelope me-2"></i>Recent Requests</span>
                <a href="incoming_requests.php" class="btn btn-sm btn-outline-light">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (mysqli_num_rows($recentRequests) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <tbody>
                                <?php while ($req = mysqli_fetch_assoc($recentRequests)): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($req['requester_name']); ?></strong><br>
                                        <small class="text-muted">wants to learn <span class="skill-tag"><?php echo htmlspecialchars($req['skill_name']); ?></span></small>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge badge-pending">Pending</span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state py-4">
                        <i class="bi bi-inbox"></i>
                        <h6>No pending requests</h6>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Upcoming Sessions -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-event me-2"></i>Upcoming Sessions</span>
                <a href="my_sessions.php" class="btn btn-sm btn-outline-light">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (mysqli_num_rows($upcomingSessionsList) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <tbody>
                                <?php while ($ses = mysqli_fetch_assoc($upcomingSessionsList)): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($ses['skill_name']); ?></strong><br>
                                        <small class="text-muted">
                                            with <?php echo htmlspecialchars($ses['requester_id'] == $userId ? $ses['provider_name'] : $ses['requester_name']); ?>
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <small><?php echo date('M d', strtotime($ses['session_date'])); ?></small><br>
                                        <small class="text-muted"><?php echo date('h:i A', strtotime($ses['session_time'])); ?></small>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state py-4">
                        <i class="bi bi-calendar-x"></i>
                        <h6>No upcoming sessions</h6>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
