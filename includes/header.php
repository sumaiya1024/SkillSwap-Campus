<?php 
require_once __DIR__ . '/auth.php'; 
$currentScript = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SkillSwap Campus – University Peer-to-Peer Skill Exchange & Learning Platform">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | SkillSwap Campus' : 'SkillSwap Campus'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= $basePath ?? '' ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $basePath ?? '' ?>index.php">
            <span class="brand-badge"><i class="bi bi-mortarboard-fill"></i></span>
            <span class="fw-bold tracking-tight">SkillSwap <span class="text-gradient">Campus</span></span>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 my-2 my-lg-0">
            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>" href="<?= $basePath ?? '' ?>admin/dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i>Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentScript === 'students.php' ? 'active' : '' ?>" href="<?= $basePath ?? '' ?>admin/students.php">
                            <i class="bi bi-people me-1"></i>Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentScript === 'skills.php' ? 'active' : '' ?>" href="<?= $basePath ?? '' ?>admin/skills.php">
                            <i class="bi bi-lightbulb me-1"></i>Skills
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentScript === 'requests.php' ? 'active' : '' ?>" href="<?= $basePath ?? '' ?>admin/requests.php">
                            <i class="bi bi-envelope me-1"></i>Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentScript === 'sessions.php' ? 'active' : '' ?>" href="<?= $basePath ?? '' ?>admin/sessions.php">
                            <i class="bi bi-calendar-event me-1"></i>Sessions
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>" href="<?= $basePath ?? '' ?>dashboard.php">
                            <i class="bi bi-grid-1x2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentScript === 'skills.php' ? 'active' : '' ?>" href="<?= $basePath ?? '' ?>skills.php">
                            <i class="bi bi-lightbulb me-1"></i>My Skills
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentScript === 'browse_students.php' ? 'active' : '' ?>" href="<?= $basePath ?? '' ?>browse_students.php">
                            <i class="bi bi-search me-1"></i>Browse
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentScript === 'requests.php' ? 'active' : '' ?>" href="<?= $basePath ?? '' ?>requests.php">
                            <i class="bi bi-envelope me-1"></i>Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentScript === 'sessions.php' ? 'active' : '' ?>" href="<?= $basePath ?? '' ?>sessions.php">
                            <i class="bi bi-calendar-event me-1"></i>Sessions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentScript === 'reviews.php' ? 'active' : '' ?>" href="<?= $basePath ?? '' ?>reviews.php">
                            <i class="bi bi-star me-1"></i>Reviews
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-item dropdown ms-lg-2">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                        <div class="avatar-placeholder sm" style="width: 28px; height: 28px; font-size: 0.75rem;">
                            <?= strtoupper(substr(currentUserName(), 0, 1)) ?>
                        </div>
                        <span><?= htmlspecialchars(currentUserName()) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-3 py-1">
                            <small class="text-muted d-block">Signed in as</small>
                            <span class="badge badge-<?= isAdmin() ? 'advanced' : 'beginner' ?> mt-1">
                                <?= isAdmin() ? 'Administrator' : 'Student Member' ?>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if (!isAdmin()): ?>
                        <li><a class="dropdown-item" href="<?= $basePath ?? '' ?>profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item text-danger" href="<?= $basePath ?? '' ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                    </ul>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentScript === 'login.php' ? 'active' : '' ?>" href="<?= $basePath ?? '' ?>login.php">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Login
                    </a>
                </li>
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-accent btn-sm" href="<?= $basePath ?? '' ?>register.php">
                        <i class="bi bi-rocket-takeoff me-1"></i>Get Started
                    </a>
                </li>
            <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main>
<div class="container py-4">
<?php showFlash(); ?>
