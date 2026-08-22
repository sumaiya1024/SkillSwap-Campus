<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SkillSwap Campus - Exchange skills with fellow university students">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | SkillSwap Campus' : 'SkillSwap Campus'; ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?php echo $basePath ?? ''; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo $basePath ?? ''; ?>index.php">
            <i class="bi bi-arrow-left-right me-2"></i>
            <span>SkillSwap Campus</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath ?? ''; ?>admin/dashboard.php">
                                <i class="bi bi-speedometer2 me-1"></i> Admin Panel
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath ?? ''; ?>dashboard.php">
                                <i class="bi bi-grid-1x2 me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath ?? ''; ?>browse.php">
                                <i class="bi bi-search me-1"></i> Browse
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath ?? ''; ?>my_skills.php">
                                <i class="bi bi-lightbulb me-1"></i> My Skills
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath ?? ''; ?>my_requests.php">
                                <i class="bi bi-envelope me-1"></i> Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath ?? ''; ?>my_sessions.php">
                                <i class="bi bi-calendar-event me-1"></i> Sessions
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars(getCurrentUserName()); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (!isAdmin()): ?>
                            <li><a class="dropdown-item" href="<?php echo $basePath ?? ''; ?>profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?php echo $basePath ?? ''; ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $basePath ?? ''; ?>login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-accent btn-sm ms-2" href="<?php echo $basePath ?? ''; ?>register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<main>
    <div class="container py-4">
        <?php displayFlash(); ?>
