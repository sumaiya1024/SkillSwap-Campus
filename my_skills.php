<?php
$pageTitle = 'My Skills';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$userId = getCurrentUserId();

// Handle add skill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $skill_id = intval($_POST['skill_id'] ?? 0);
        $proficiency = $_POST['proficiency_level'] ?? 'Beginner';

        if ($skill_id > 0) {
            // Check if already added
            $stmt = mysqli_prepare($conn, "SELECT student_skill_id FROM student_skills WHERE student_id = ? AND skill_id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $userId, $skill_id);
            mysqli_stmt_execute($stmt);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
                setFlash('error', 'You have already added this skill.');
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO student_skills (student_id, skill_id, proficiency_level) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "iis", $userId, $skill_id, $proficiency);
                mysqli_stmt_execute($stmt);
                setFlash('success', 'Skill added successfully!');
            }
        }
        header("Location: my_skills.php");
        exit();
    }

    if ($_POST['action'] === 'remove') {
        $student_skill_id = intval($_POST['student_skill_id'] ?? 0);
        if ($student_skill_id > 0) {
            $stmt = mysqli_prepare($conn, "DELETE FROM student_skills WHERE student_skill_id = ? AND student_id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $student_skill_id, $userId);
            mysqli_stmt_execute($stmt);
            setFlash('success', 'Skill removed.');
        }
        header("Location: my_skills.php");
        exit();
    }
}

// Get my current skills
$stmt = mysqli_prepare($conn, "SELECT ss.student_skill_id, sk.skill_name, sc.category_name, ss.proficiency_level 
    FROM student_skills ss 
    JOIN skills sk ON ss.skill_id = sk.skill_id 
    JOIN skill_categories sc ON sk.category_id = sc.category_id 
    WHERE ss.student_id = ? ORDER BY sc.category_name, sk.skill_name");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$mySkills = mysqli_stmt_get_result($stmt);

// Get all skills grouped by category (for adding)
$allSkills = mysqli_query($conn, "SELECT sk.skill_id, sk.skill_name, sc.category_name 
    FROM skills sk 
    JOIN skill_categories sc ON sk.category_id = sc.category_id 
    ORDER BY sc.category_name, sk.skill_name");

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-lightbulb me-2"></i>My Skills</h1>
    <p>Manage the skills you can teach to other students</p>
</div>

<div class="row g-4">
    <!-- Add Skill Form -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-plus-circle me-2"></i>Add a Skill</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="skill_id" class="form-label">Select Skill</label>
                        <select class="form-select" id="skill_id" name="skill_id" required>
                            <option value="">Choose a skill...</option>
                            <?php 
                            $currentCat = '';
                            while ($sk = mysqli_fetch_assoc($allSkills)):
                                if ($sk['category_name'] !== $currentCat):
                                    if ($currentCat !== '') echo '</optgroup>';
                                    $currentCat = $sk['category_name'];
                                    echo '<optgroup label="' . htmlspecialchars($currentCat) . '">';
                                endif;
                            ?>
                                <option value="<?php echo $sk['skill_id']; ?>"><?php echo htmlspecialchars($sk['skill_name']); ?></option>
                            <?php endwhile; 
                            if ($currentCat !== '') echo '</optgroup>';
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="proficiency_level" class="form-label">Proficiency Level</label>
                        <select class="form-select" id="proficiency_level" name="proficiency_level">
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg me-1"></i> Add Skill
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- My Skills List -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-list-ul me-2"></i>Skills I Teach</div>
            <div class="card-body p-0">
                <?php if (mysqli_num_rows($mySkills) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Skill</th>
                                    <th>Category</th>
                                    <th>Level</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($skill = mysqli_fetch_assoc($mySkills)): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($skill['skill_name']); ?></strong></td>
                                    <td><span class="text-muted"><?php echo htmlspecialchars($skill['category_name']); ?></span></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($skill['proficiency_level']); ?>">
                                            <?php echo $skill['proficiency_level']; ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" class="d-inline" onsubmit="return confirmAction('Remove this skill?')">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="student_skill_id" value="<?php echo $skill['student_skill_id']; ?>">
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
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-lightbulb"></i>
                        <h5>No skills added yet</h5>
                        <p>Use the form to add skills you can teach!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
