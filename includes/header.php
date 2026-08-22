<?php require_once __DIR__ . '/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SkillSwap Campus – Exchange skills with fellow university students">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | SkillSwap Campus' : 'SkillSwap Campus'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= $basePath ?? '' ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= $basePath ?? '' ?>index.php">
            <i class="bi bi-arrow-left-right me-2"></i><span>SkillSwap Campus</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-center">
            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $basePath ?? '' ?>admin/dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $basePath ?? '' ?>admin/students.php"><i class="bi bi-people me-1"></i>Students</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $basePath ?? '' ?>admin/skills.php"><i class="bi bi-lightbulb me-1"></i>Skills</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $basePath ?? '' ?>admin/requests.php"><i class="bi bi-envelope me-1"></i>Requests</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $basePath ?? '' ?>admin/sessions.php"><i class="bi bi-calendar me-1"></i>Sessions</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $basePath ?? '' ?>dashboard.php"><i class="bi bi-grid-1x2 me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $basePath ?? '' ?>skills.php"><i class="bi bi-lightbulb me-1"></i>My Skills</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $basePath ?? '' ?>browse_students.php"><i class="bi bi-search me-1"></i>Browse</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $basePath ?? '' ?>requests.php"><i class="bi bi-envelope me-1"></i>Requests</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $basePath ?? '' ?>sessions.php"><i class="bi bi-calendar-event me-1"></i>Sessions</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $basePath ?? '' ?>reviews.php"><i class="bi bi-star me-1"></i>Reviews</a></li>
                <?php endif; ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars(currentUserName()) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if (!isAdmin()): ?>
                        <li><a class="dropdown-item" href="<?= $basePath ?? '' ?>profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="<?= $basePath ?? '' ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
            <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="<?= $basePath ?? '' ?>login.php">Login</a></li>
                <li class="nav-item"><a class="btn btn-accent btn-sm ms-2" href="<?= $basePath ?? '' ?>register.php">Register</a></li>
            <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main>
<div class="container py-4">
<?php showFlash(); ?>
