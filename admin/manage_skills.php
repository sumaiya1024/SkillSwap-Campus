<?php
$pageTitle = 'Manage Skills';
$basePath = '../';
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle add skill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_skill'])) {
    $skillName = trim($_POST['skill_name'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);

    if (!empty($skillName) && $categoryId > 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO skills (skill_name, category_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "si", $skillName, $categoryId);
        mysqli_stmt_execute($stmt);
        setFlash('success', 'Skill added successfully!');
    }
    header("Location: manage_skills.php");
    exit();
}

// Handle delete skill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_skill'])) {
    $skillId = intval($_POST['skill_id'] ?? 0);
    if ($skillId > 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM skills WHERE skill_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $skillId);
        mysqli_stmt_execute($stmt);
        setFlash('success', 'Skill deleted.');
    }
    header("Location: manage_skills.php");
    exit();
}

// Get all skills with category and usage count
$skills = mysqli_query($conn, "SELECT sk.*, sc.category_name, 
    (SELECT COUNT(*) FROM student_skills ss WHERE ss.skill_id = sk.skill_id) as student_count
    FROM skills sk 
    JOIN skill_categories sc ON sk.category_id = sc.category_id 
    ORDER BY sc.category_name, sk.skill_name");

// Get categories for dropdown
$categories = mysqli_query($conn, "SELECT * FROM skill_categories ORDER BY category_name");

include '../includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-lightbulb me-2"></i>Manage Skills</h1>
        <p>Add or remove skills across categories</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-light btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
</div>

<div class="row g-4">
    <!-- Add Skill -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-plus-circle me-2"></i>Add Skill</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="add_skill" value="1">
                    <div class="mb-3">
                        <label for="skill_name" class="form-label">Skill Name</label>
                        <input type="text" class="form-control" id="skill_name" name="skill_name" 
                               placeholder="e.g., React.js" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select category...</option>
                            <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                                <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg me-1"></i> Add Skill
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Skills List -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-list-ul me-2"></i>All Skills</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Skill</th>
                                <th>Category</th>
                                <th>Students</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($skill = mysqli_fetch_assoc($skills)): ?>
                            <tr>
                                <td><?php echo $skill['skill_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($skill['skill_name']); ?></strong></td>
                                <td><span class="skill-tag"><?php echo htmlspecialchars($skill['category_name']); ?></span></td>
                                <td><span class="badge badge-intermediate"><?php echo $skill['student_count']; ?></span></td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline" onsubmit="return confirmAction('Delete this skill?')">
                                        <input type="hidden" name="delete_skill" value="1">
                                        <input type="hidden" name="skill_id" value="<?php echo $skill['skill_id']; ?>">
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
    </div>
</div>

<?php include '../includes/footer.php'; ?>
