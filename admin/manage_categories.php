<?php
$pageTitle = 'Manage Categories';
$basePath = '../';
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['category_name'] ?? '');
    if (!empty($name)) {
        // Check for duplicate
        $stmt = mysqli_prepare($conn, "SELECT category_id FROM skill_categories WHERE category_name = ?");
        mysqli_stmt_bind_param($stmt, "s", $name);
        mysqli_stmt_execute($stmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
            setFlash('error', 'Category already exists.');
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO skill_categories (category_name) VALUES (?)");
            mysqli_stmt_bind_param($stmt, "s", $name);
            mysqli_stmt_execute($stmt);
            setFlash('success', 'Category added successfully!');
        }
    }
    header("Location: manage_categories.php");
    exit();
}

// Handle edit category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $catId = intval($_POST['category_id'] ?? 0);
    $name = trim($_POST['category_name'] ?? '');
    if ($catId > 0 && !empty($name)) {
        $stmt = mysqli_prepare($conn, "UPDATE skill_categories SET category_name = ? WHERE category_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $name, $catId);
        mysqli_stmt_execute($stmt);
        setFlash('success', 'Category updated.');
    }
    header("Location: manage_categories.php");
    exit();
}

// Handle delete category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $catId = intval($_POST['category_id'] ?? 0);
    if ($catId > 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM skill_categories WHERE category_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $catId);
        mysqli_stmt_execute($stmt);
        setFlash('success', 'Category deleted.');
    }
    header("Location: manage_categories.php");
    exit();
}

// Get all categories with skill count
$categories = mysqli_query($conn, "SELECT c.*, COUNT(s.skill_id) as skill_count 
    FROM skill_categories c 
    LEFT JOIN skills s ON c.category_id = s.category_id 
    GROUP BY c.category_id 
    ORDER BY c.category_name");

include '../includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-tags me-2"></i>Manage Categories</h1>
        <p>Add, edit, or remove skill categories</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-light btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
</div>

<div class="row g-4">
    <!-- Add Category -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-plus-circle me-2"></i>Add Category</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="add_category" value="1">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" 
                               placeholder="e.g., Photography" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg me-1"></i> Add Category
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Categories List -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-list-ul me-2"></i>All Categories</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Category Name</th>
                                <th>Skills</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                            <tr>
                                <td><?php echo $cat['category_id']; ?></td>
                                <td>
                                    <form method="POST" class="d-flex gap-2 align-items-center">
                                        <input type="hidden" name="edit_category" value="1">
                                        <input type="hidden" name="category_id" value="<?php echo $cat['category_id']; ?>">
                                        <input type="text" class="form-control form-control-sm" name="category_name" 
                                               value="<?php echo htmlspecialchars($cat['category_name']); ?>" 
                                               style="max-width: 200px;">
                                        <button type="submit" class="btn btn-sm btn-outline-light" title="Save">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                </td>
                                <td><span class="badge badge-intermediate"><?php echo $cat['skill_count']; ?></span></td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline" onsubmit="return confirmAction('Delete this category and all its skills?')">
                                        <input type="hidden" name="delete_category" value="1">
                                        <input type="hidden" name="category_id" value="<?php echo $cat['category_id']; ?>">
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
