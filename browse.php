<?php
$pageTitle = 'Browse Skills';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$userId = getCurrentUserId();

// Filter parameters
$categoryFilter = intval($_GET['category'] ?? 0);
$searchQuery = trim($_GET['search'] ?? '');

// Build query
$query = "SELECT DISTINCT st.student_id, st.full_name, st.department, st.profile_picture, st.bio,
    GROUP_CONCAT(DISTINCT sk.skill_name ORDER BY sk.skill_name SEPARATOR ', ') as skills,
    (SELECT AVG(r.rating) FROM reviews r WHERE r.reviewee_id = st.student_id) as avg_rating,
    (SELECT COUNT(r.review_id) FROM reviews r WHERE r.reviewee_id = st.student_id) as review_count
    FROM students st
    JOIN student_skills ss ON st.student_id = ss.student_id
    JOIN skills sk ON ss.skill_id = sk.skill_id
    WHERE st.student_id != ?";

$params = [$userId];
$types = "i";

if ($categoryFilter > 0) {
    $query .= " AND sk.category_id = ?";
    $params[] = $categoryFilter;
    $types .= "i";
}

if (!empty($searchQuery)) {
    $query .= " AND (st.full_name LIKE ? OR sk.skill_name LIKE ? OR st.department LIKE ?)";
    $searchLike = "%$searchQuery%";
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $types .= "sss";
}

$query .= " GROUP BY st.student_id ORDER BY avg_rating DESC, st.full_name";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$students = mysqli_stmt_get_result($stmt);

// Get categories for filter
$categories = mysqli_query($conn, "SELECT * FROM skill_categories ORDER BY category_name");

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-search me-2"></i>Browse Students & Skills</h1>
    <p>Find students who can teach you something new</p>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label for="search" class="form-label small">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($searchQuery); ?>" 
                       placeholder="Search by name, skill, or department...">
            </div>
            <div class="col-md-4">
                <label for="category" class="form-label small">Category</label>
                <select class="form-select" id="category" name="category">
                    <option value="0">All Categories</option>
                    <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo $categoryFilter == $cat['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-search me-1"></i> Search
                </button>
                <a href="browse.php" class="btn btn-outline-light">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Results -->
<?php if (mysqli_num_rows($students) > 0): ?>
    <div class="row g-3">
        <?php while ($stu = mysqli_fetch_assoc($students)): ?>
        <div class="col-md-6 col-lg-4">
            <div class="student-card">
                <div class="student-info">
                    <?php if ($stu['profile_picture'] && file_exists($stu['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($stu['profile_picture']); ?>" class="avatar avatar-sm" alt="">
                    <?php else: ?>
                        <div class="avatar-placeholder sm">
                            <?php echo strtoupper(substr($stu['full_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div class="student-name"><?php echo htmlspecialchars($stu['full_name']); ?></div>
                        <div class="student-dept"><?php echo htmlspecialchars($stu['department'] ?? 'No department'); ?></div>
                    </div>
                </div>

                <?php if ($stu['avg_rating']): ?>
                <div class="stars mb-2" style="font-size: 0.85rem;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="bi <?php echo $i <= round($stu['avg_rating']) ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                    <?php endfor; ?>
                    <span class="text-muted ms-1">(<?php echo $stu['review_count']; ?>)</span>
                </div>
                <?php endif; ?>

                <div class="skills-list">
                    <?php 
                    $skillNames = explode(', ', $stu['skills']);
                    foreach (array_slice($skillNames, 0, 4) as $s): ?>
                        <span class="skill-tag"><?php echo htmlspecialchars($s); ?></span>
                    <?php endforeach; 
                    if (count($skillNames) > 4): ?>
                        <span class="skill-tag" style="opacity: 0.6;">+<?php echo count($skillNames) - 4; ?> more</span>
                    <?php endif; ?>
                </div>

                <a href="student_detail.php?id=<?php echo $stu['student_id']; ?>" class="btn btn-primary btn-sm w-100 mt-auto">
                    <i class="bi bi-eye me-1"></i> View Profile
                </a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="empty-state">
        <i class="bi bi-people"></i>
        <h5>No students found</h5>
        <p>Try adjusting your search or filter criteria.</p>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
