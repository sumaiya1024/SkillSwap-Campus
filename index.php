<?php
$pageTitle = 'Home';
include 'includes/header.php';
?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0" style="position: relative; z-index: 1;">
                <h1 class="hero-title">
                    Share Skills,<br>
                    <span class="highlight">Grow Together</span>
                </h1>
                <p class="hero-subtitle">
                    Connect with fellow university students to teach and learn new skills. 
                    From coding to guitar, math to design — swap what you know for what you want to learn.
                </p>
                <div class="mt-4">
                    <?php if (!isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-primary btn-lg me-2">
                            <i class="bi bi-rocket-takeoff me-1"></i> Get Started
                        </a>
                        <a href="login.php" class="btn btn-outline-light btn-lg">
                            Login
                        </a>
                    <?php else: ?>
                        <a href="dashboard.php" class="btn btn-primary btn-lg me-2">
                            <i class="bi bi-grid-1x2 me-1"></i> Go to Dashboard
                        </a>
                        <a href="browse.php" class="btn btn-accent btn-lg">
                            <i class="bi bi-search me-1"></i> Browse Skills
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 text-center" style="position: relative; z-index: 1;">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="stat-card text-center">
                            <div class="stat-icon purple mx-auto"><i class="bi bi-people"></i></div>
                            <div class="stat-number text-gradient">100+</div>
                            <div class="stat-label">Students</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card text-center">
                            <div class="stat-icon teal mx-auto"><i class="bi bi-lightbulb"></i></div>
                            <div class="stat-number text-gradient">50+</div>
                            <div class="stat-label">Skills</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card text-center">
                            <div class="stat-icon green mx-auto"><i class="bi bi-arrow-left-right"></i></div>
                            <div class="stat-number text-gradient">200+</div>
                            <div class="stat-label">Exchanges</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card text-center">
                            <div class="stat-icon orange mx-auto"><i class="bi bi-star"></i></div>
                            <div class="stat-number text-gradient">4.8</div>
                            <div class="stat-label">Avg Rating</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="container py-5" style="margin-top: -2rem;">
    <h2 class="section-title">How It Works</h2>
    <p class="section-subtitle">Three simple steps to start exchanging skills</p>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(108, 92, 231, 0.15); color: var(--primary-light);">
                    <i class="bi bi-person-plus"></i>
                </div>
                <h5>1. Create Your Profile</h5>
                <p>Sign up, list the skills you can teach, and tell others what you'd like to learn.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(0, 206, 201, 0.15); color: var(--accent);">
                    <i class="bi bi-search"></i>
                </div>
                <h5>2. Find a Match</h5>
                <p>Browse students and skills, then send a request to learn from someone on campus.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(0, 184, 148, 0.15); color: var(--success);">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <h5>3. Swap & Rate</h5>
                <p>Schedule a session, meet up, exchange skills, and leave a review when done.</p>
            </div>
        </div>
    </div>
</div>

<!-- Categories Preview -->
<?php
require_once 'config/db.php';
$catResult = mysqli_query($conn, "SELECT c.category_id, c.category_name, COUNT(s.skill_id) AS skill_count FROM skill_categories c LEFT JOIN skills s ON c.category_id = s.category_id GROUP BY c.category_id ORDER BY skill_count DESC LIMIT 6");
?>
<div class="container pb-5">
    <h2 class="section-title">Popular Categories</h2>
    <p class="section-subtitle">Explore skills across different domains</p>
    <div class="row g-3 justify-content-center">
        <?php
        $icons = ['bi-code-slash', 'bi-music-note-beamed', 'bi-palette', 'bi-translate', 'bi-book', 'bi-trophy'];
        $colors = ['purple', 'teal', 'orange', 'green', 'purple', 'teal'];
        $i = 0;
        while ($cat = mysqli_fetch_assoc($catResult)):
        ?>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="feature-card" style="padding: 1.25rem;">
                <div class="feature-icon <?php echo $colors[$i % 6]; ?> mx-auto" style="width: 44px; height: 44px; font-size: 1.1rem; background: rgba(108, 92, 231, 0.12);">
                    <i class="bi <?php echo $icons[$i % 6]; ?>"></i>
                </div>
                <h6 class="mt-2 mb-0" style="font-size: 0.85rem;"><?php echo htmlspecialchars($cat['category_name']); ?></h6>
                <small class="text-muted"><?php echo $cat['skill_count']; ?> skills</small>
            </div>
        </div>
        <?php $i++; endwhile; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
