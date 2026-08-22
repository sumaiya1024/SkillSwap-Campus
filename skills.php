<?php
$pageTitle = 'Skills Management';
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$userId = currentUserId();
$errors = [];

// Handle Add Skill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_skill') {
    $skill_id = (int)($_POST['skill_id'] ?? 0);
    $proficiency = $_POST['proficiency_level'] ?? 'Beginner';
    $validProficiencies = ['Beginner', 'Intermediate', 'Advanced'];

    if (!in_array($proficiency, $validProficiencies)) {
        $proficiency = 'Beginner';
    }

    if ($skill_id <= 0) {
        $errors[] = 'Please select a valid skill.';
    } else {
        // Check if skill exists
        $stmt = $pdo->prepare("SELECT skill_id FROM skills WHERE skill_id = ?");
        $stmt->execute([$skill_id]);
        if (!$stmt->fetch()) {
            $errors[] = 'Selected skill does not exist.';
        } else {
            // Check if already added
            $stmt = $pdo->prepare("SELECT student_skill_id FROM student_skills WHERE student_id = ? AND skill_id = ?");
            $stmt->execute([$userId, $skill_id]);
            if ($stmt->fetch()) {
                $errors[] = 'You have already added this skill to your teaching list.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO student_skills (student_id, skill_id, proficiency_level) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $skill_id, $proficiency]);
                flash('success', 'Skill added to your teaching profile!');
                header('Location: skills.php');
                exit;
            }
        }
    }
}

// Handle Remove Skill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_skill') {
    $student_skill_id = (int)($_POST['student_skill_id'] ?? 0);
    if ($student_skill_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM student_skills WHERE student_skill_id = ? AND student_id = ?");
        $stmt->execute([$student_skill_id, $userId]);
        flash('success', 'Skill removed from your teaching profile.');
        header('Location: skills.php');
        exit;
    }
}

// Fetch My Skills
$stmt = $pdo->prepare("SELECT ss.student_skill_id, ss.proficiency_level, sk.skill_id, sk.skill_name, sc.category_name 
    FROM student_skills ss 
    JOIN skills sk ON ss.skill_id = sk.skill_id 
    JOIN skill_categories sc ON sk.category_id = sc.category_id 
    WHERE ss.student_id = ? 
    ORDER BY sc.category_name ASC, sk.skill_name ASC");
$stmt->execute([$userId]);
$mySkills = $stmt->fetchAll();

// Fetch Catalog Skills for the dropdown
$allSkillsStmt = $pdo->query("SELECT sk.skill_id, sk.skill_name, sc.category_name 
    FROM skills sk 
    JOIN skill_categories sc ON sk.category_id = sc.category_id 
    ORDER BY sc.category_name ASC, sk.skill_name ASC");
$allSkills = $allSkillsStmt->fetchAll();

// Group skills by category
$skillsByCategory = [];
foreach ($allSkills as $s) {
    $skillsByCategory[$s['category_name']][] = $s;
}

// Fetch all available skills catalog with student teacher counts
$catalogStmt = $pdo->query("SELECT sk.skill_id, sk.skill_name, sc.category_name,
    COUNT(ss.student_skill_id) AS teacher_count 
    FROM skills sk 
    JOIN skill_categories sc ON sk.category_id = sc.category_id 
    LEFT JOIN student_skills ss ON sk.skill_id = ss.skill_id 
    GROUP BY sk.skill_id, sk.skill_name, sc.category_name 
    ORDER BY sc.category_name ASC, teacher_count DESC, sk.skill_name ASC");
$catalogSkills = $catalogStmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-lightbulb me-2 text-gradient"></i>Skills Management</h1>
    <p class="text-secondary">Manage what skills you teach and discover skills available on campus.</p>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $err): ?>
            <div><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="row g-4 mb-5">
    <!-- Left Column: Add Skill Form -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle me-2 text-primary"></i>Add a Skill You Can Teach
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="add_skill">

                    <div class="mb-3">
                        <label for="skill_id" class="form-label">Select Skill <span class="text-danger">*</span></label>
                        <select class="form-select" id="skill_id" name="skill_id" required>
                            <option value="">-- Choose from available skills --</option>
                            <?php foreach ($skillsByCategory as $catName => $catSkills): ?>
                                <optgroup label="<?= htmlspecialchars($catName) ?>">
                                    <?php foreach ($catSkills as $sk): ?>
                                        <option value="<?= $sk['skill_id'] ?>"><?= htmlspecialchars($sk['skill_name']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="proficiency_level" class="form-label">Your Proficiency Level</label>
                        <select class="form-select" id="proficiency_level" name="proficiency_level">
                            <option value="Beginner">Beginner (Can teach basics)</option>
                            <option value="Intermediate" selected>Intermediate (Good practical knowledge)</option>
                            <option value="Advanced">Advanced (Extensive expertise)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg me-1"></i>Add to My Skills
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: My Skills List -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person-check me-2 text-success"></i>Skills You Are Currently Teaching</span>
                <span class="badge bg-secondary"><?= count($mySkills) ?> skills</span>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($mySkills)): ?>
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
                                <?php foreach ($mySkills as $sk): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($sk['skill_name']) ?></strong></td>
                                        <td><span class="text-secondary small"><?= htmlspecialchars($sk['category_name']) ?></span></td>
                                        <td>
                                            <span class="badge badge-<?= strtolower($sk['proficiency_level']) ?>">
                                                <?= $sk['proficiency_level'] ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to stop offering this skill?');">
                                                <input type="hidden" name="action" value="remove_skill">
                                                <input type="hidden" name="student_skill_id" value="<?= $sk['student_skill_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove Skill">
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
                    <div class="empty-state py-4">
                        <i class="bi bi-lightbulb"></i>
                        <h6>No skills listed yet</h6>
                        <p class="mb-0">Select a skill from the form on the left to start teaching others!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Campus Skill Directory Section -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-collection me-2 text-accent"></i>Campus Skill Catalog & Directory
    </div>
    <div class="card-body">
        <p class="text-secondary small mb-3">Click on any skill to find fellow students teaching that topic:</p>
        <div class="row g-3">
            <?php 
            $currentCat = '';
            foreach ($catalogSkills as $item): 
                if ($currentCat !== $item['category_name']):
                    $currentCat = $item['category_name'];
            ?>
                <div class="col-12 mt-4 mb-1">
                    <h6 class="text-primary fw-bold text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                        <i class="bi bi-tag-fill me-1"></i><?= htmlspecialchars($currentCat) ?>
                    </h6>
                </div>
            <?php endif; ?>
                <div class="col-md-4 col-lg-3">
                    <a href="browse_students.php?search=<?= urlencode($item['skill_name']) ?>" class="text-decoration-none">
                        <div class="p-2 px-3 border rounded d-flex justify-content-between align-items-center" style="background: var(--bg-card); border-color: var(--border-color) !important; transition: var(--transition);">
                            <span class="text-light fw-medium"><?= htmlspecialchars($item['skill_name']) ?></span>
                            <span class="badge bg-secondary"><?= $item['teacher_count'] ?> <?= $item['teacher_count'] == 1 ? 'peer' : 'peers' ?></span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
