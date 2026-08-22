<?php
$pageTitle = 'Manage Skills & Categories';
$basePath = '../';
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

$errors = [];

// Handle Add Skill Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $category_name = trim($_POST['category_name'] ?? '');
    if (empty($category_name)) {
        $errors[] = 'Category name cannot be empty.';
    } else {
        $stmt = $pdo->prepare("SELECT category_id FROM skill_categories WHERE category_name = ?");
        $stmt->execute([$category_name]);
        if ($stmt->fetch()) {
            $errors[] = 'A category with this name already exists.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO skill_categories (category_name) VALUES (?)");
            $stmt->execute([$category_name]);
            flash('success', 'Skill category created successfully!');
            header('Location: skills.php');
            exit;
        }
    }
}

// Handle Add New Skill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_skill') {
    $skill_name  = trim($_POST['skill_name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);

    if (empty($skill_name)) {
        $errors[] = 'Skill name is required.';
    } elseif ($category_id <= 0) {
        $errors[] = 'Please select a valid category.';
    } else {
        $stmt = $pdo->prepare("SELECT skill_id FROM skills WHERE skill_name = ? AND category_id = ?");
        $stmt->execute([$skill_name, $category_id]);
        if ($stmt->fetch()) {
            $errors[] = 'This skill already exists in the selected category.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO skills (skill_name, category_id) VALUES (?, ?)");
            $stmt->execute([$skill_name, $category_id]);
            flash('success', 'New skill added to the catalog!');
            header('Location: skills.php');
            exit;
        }
    }
}

// Handle Delete Skill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_skill') {
    $skillId = (int)($_POST['skill_id'] ?? 0);
    if ($skillId > 0) {
        $stmt = $pdo->prepare("DELETE FROM skills WHERE skill_id = ?");
        $stmt->execute([$skillId]);
        flash('success', 'Skill removed from the catalog.');
        header('Location: skills.php');
        exit;
    }
}

// Handle Delete Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_category') {
    $catId = (int)($_POST['category_id'] ?? 0);
    if ($catId > 0) {
        $stmt = $pdo->prepare("DELETE FROM skill_categories WHERE category_id = ?");
        $stmt->execute([$catId]);
        flash('success', 'Category and all associated catalog skills removed.');
        header('Location: skills.php');
        exit;
    }
}

// Fetch all Categories with skill counts
$categories = $pdo->query("SELECT sc.*, COUNT(sk.skill_id) AS skill_count 
    FROM skill_categories sc 
    LEFT JOIN skills sk ON sc.category_id = sk.category_id 
    GROUP BY sc.category_id, sc.category_name 
    ORDER BY sc.category_name ASC")->fetchAll();

// Fetch all Skills with student teacher counts
$skills = $pdo->query("SELECT sk.*, sc.category_name, 
    COUNT(ss.student_skill_id) AS teacher_count 
    FROM skills sk 
    JOIN skill_categories sc ON sk.category_id = sc.category_id 
    LEFT JOIN student_skills ss ON sk.skill_id = ss.skill_id 
    GROUP BY sk.skill_id, sk.skill_name, sc.category_name 
    ORDER BY sc.category_name ASC, sk.skill_name ASC")->fetchAll();

include '../includes/header.php';
?>

<div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
    <div>
        <h1><i class="bi bi-lightbulb me-2 text-gradient"></i>Manage Skills & Categories</h1>
        <p class="text-secondary">Add and organize official skill categories and catalog listings.</p>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="dashboard.php" class="btn btn-outline-light"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $err): ?>
            <div><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <!-- Add Category Form -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-folder-plus me-2 text-accent"></i>Add New Skill Category</div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="add_category">
                    <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="category_name" placeholder="e.g. Photography, Robotics, Finance" required>
                    </div>
                    <button type="submit" class="btn btn-accent w-100"><i class="bi bi-plus-lg me-1"></i>Create Category</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Skill Form -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-plus-circle me-2 text-primary"></i>Add New Skill to Catalog</div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="add_skill">
                    <div class="mb-3">
                        <label class="form-label">Skill Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="skill_name" placeholder="e.g. React.js, Portrait Photography" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Category <span class="text-danger">*</span></label>
                        <select class="form-select" name="category_id" required>
                            <option value="">-- Choose Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i>Add Skill</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Categories List -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-tags me-2 text-warning"></i>Skill Categories</span>
                <span class="badge bg-secondary"><?= count($categories) ?> categories</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Skills</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($cat['category_name']) ?></strong></td>
                                    <td><span class="badge bg-secondary"><?= $cat['skill_count'] ?></span></td>
                                    <td class="text-end">
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this category and all its skills?');">
                                            <input type="hidden" name="action" value="delete_category">
                                            <input type="hidden" name="category_id" value="<?= $cat['category_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Category">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Skills Catalog List -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-collection me-2 text-primary"></i>All Catalog Skills</span>
                <span class="badge bg-secondary"><?= count($skills) ?> skills</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Skill Name</th>
                                <th>Category</th>
                                <th>Students Teaching</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($skills as $sk): ?>
                                <tr>
                                    <td><?= $sk['skill_id'] ?></td>
                                    <td><strong class="text-light"><?= htmlspecialchars($sk['skill_name']) ?></strong></td>
                                    <td><span class="badge badge-intermediate"><?= htmlspecialchars($sk['category_name']) ?></span></td>
                                    <td><span class="small text-muted"><?= $sk['teacher_count'] ?> <?= $sk['teacher_count'] == 1 ? 'student' : 'students' ?></span></td>
                                    <td class="text-end">
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this skill from catalog?');">
                                            <input type="hidden" name="action" value="delete_skill">
                                            <input type="hidden" name="skill_id" value="<?= $sk['skill_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Skill">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
