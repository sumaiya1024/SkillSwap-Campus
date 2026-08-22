<?php
$pageTitle = 'Campus Skill Exchange';
require_once 'config/db.php';
include 'includes/header.php';

// Stats from database
$stats = $pdo->query("SELECT
    (SELECT COUNT(*) FROM students) AS students,
    (SELECT COUNT(*) FROM skills) AS skills,
    (SELECT COUNT(*) FROM skill_requests) AS exchanges,
    (SELECT COALESCE(AVG(rating), 0) FROM reviews) AS avg_rating
")->fetch();

// Skill Categories
$cats = $pdo->query("SELECT c.category_id, c.category_name, COUNT(s.skill_id) AS cnt 
    FROM skill_categories c 
    LEFT JOIN skills s ON c.category_id = s.category_id 
    GROUP BY c.category_id 
    ORDER BY cnt DESC LIMIT 6")->fetchAll();

$catIcons = [
    'Programming'       => 'bi-code-slash',
    'Music'             => 'bi-music-note-beamed',
    'Design'            => 'bi-palette',
    'Languages'         => 'bi-translate',
    'Academic'          => 'bi-book',
    'Sports & Fitness'  => 'bi-activity'
];
?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0" style="position: relative; z-index: 1;">
                <div class="hero-badge">
                    <i class="bi bi-mortarboard"></i> University Peer Learning Network
                </div>
                <h1 class="hero-title">
                    Share Skills.<br>
                    <span class="highlight">Learn from Peers.</span>
                </h1>
                <p class="hero-subtitle">
                    A collaborative platform designed for university students to swap knowledge 1-on-1. 
                    Teach coding, music, design, languages, or coursework — and learn what you need without high tutoring fees.
                </p>
                <div class="d-flex gap-3 mt-4 flex-wrap">
                    <?php if (!isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-rocket-takeoff me-1"></i> Join the Community
                        </a>
                        <a href="login.php" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </a>
                    <?php else: ?>
                        <a href="dashboard.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-grid-1x2 me-1"></i> Go to My Dashboard
                        </a>
                        <a href="browse_students.php" class="btn btn-accent btn-lg">
                            <i class="bi bi-search me-1"></i> Explore Peer Skills
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Live Campus Statistics -->
            <div class="col-lg-6" style="position: relative; z-index: 1;">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="stat-card">
                            <div class="stat-icon purple"><i class="bi bi-people"></i></div>
                            <div class="stat-number text-gradient"><?= $stats['students'] ?>+</div>
                            <div class="stat-label">Active Campus Students</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card">
                            <div class="stat-icon teal"><i class="bi bi-lightbulb"></i></div>
                            <div class="stat-number text-gradient"><?= $stats['skills'] ?>+</div>
                            <div class="stat-label">Skills in Catalog</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card">
                            <div class="stat-icon green"><i class="bi bi-arrow-left-right"></i></div>
                            <div class="stat-number text-gradient"><?= $stats['exchanges'] ?>+</div>
                            <div class="stat-label">Learning Exchanges</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card">
                            <div class="stat-icon orange"><i class="bi bi-star-fill"></i></div>
                            <div class="stat-number text-gradient"><?= $stats['avg_rating'] > 0 ? number_format($stats['avg_rating'], 1) : '5.0' ?></div>
                            <div class="stat-label">Peer Satisfaction Rating</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- How It Works Section -->
<div class="container py-5" style="margin-top: -1.5rem;">
    <h2 class="section-title">How SkillSwap Works</h2>
    <p class="section-subtitle">Three easy steps to start sharing and learning on campus</p>
    
    <div class="row g-4">
        <div class="col-md-4">
            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary-light);">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <h5 class="fw-bold mb-2">1. Build Your Skill Profile</h5>
                <p class="text-secondary small mb-0">Register with your student email, list topics you are skilled at teaching, and specify your self-assessed level.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(6, 182, 212, 0.15); color: var(--accent);">
                    <i class="bi bi-search"></i>
                </div>
                <h5 class="fw-bold mb-2">2. Connect with a Peer</h5>
                <p class="text-secondary small mb-0">Search students across campus by skill or department and send a learning request with your learning goals.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success);">
                    <i class="bi bi-calendar2-check-fill"></i>
                </div>
                <h5 class="fw-bold mb-2">3. Meet, Learn & Review</h5>
                <p class="text-secondary small mb-0">Schedule a 1-on-1 session in the campus library or online, complete the learning session, and leave feedback.</p>
            </div>
        </div>
    </div>
</div>

<!-- Category Directory Preview -->
<div class="container pb-5">
    <div class="card p-4">
        <div class="row align-items-center mb-4">
            <div class="col-md-8">
                <h3 class="fw-bold mb-1"><i class="bi bi-collection me-2 text-primary"></i>Explore Skills by Category</h3>
                <p class="text-secondary small mb-0">Browse through disciplines and find peers eager to exchange knowledge.</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="browse_students.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-right me-1"></i>View All Skills</a>
            </div>
        </div>

        <div class="row g-3">
            <?php foreach ($cats as $c): 
                $icon = $catIcons[$c['category_name']] ?? 'bi-tag-fill';
            ?>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="browse_students.php?category=<?= $c['category_id'] ?>" class="text-decoration-none">
                    <div class="p-3 text-center rounded border" style="background: var(--bg-surface); border-color: var(--border-color) !important; transition: var(--transition);">
                        <div class="mb-2 text-accent" style="font-size: 1.5rem;">
                            <i class="bi <?= $icon ?>"></i>
                        </div>
                        <h6 class="fw-bold text-light mb-1" style="font-size: 0.85rem;"><?= htmlspecialchars($c['category_name']) ?></h6>
                        <small class="text-muted"><?= $c['cnt'] ?> Topics</small>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Community Benefits -->
<div class="container pb-5">
    <div class="row g-4 text-center">
        <div class="col-md-4">
            <div class="p-3">
                <i class="bi bi-shield-check text-success fs-1 mb-2 d-inline-block"></i>
                <h5 class="fw-bold">Verified Students</h5>
                <p class="text-secondary small">Safe, closed university community for peer collaboration and study partners.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3">
                <i class="bi bi-cash-stack text-warning fs-1 mb-2 d-inline-block"></i>
                <h5 class="fw-bold">100% Free & Peer-Powered</h5>
                <p class="text-secondary small">Exchange skills directly without costly subscriptions or external tutor charges.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3">
                <i class="bi bi-graph-up-arrow text-accent fs-1 mb-2 d-inline-block"></i>
                <h5 class="fw-bold">Boost Your Resume</h5>
                <p class="text-secondary small">Gain practical teaching experience and build peer recommendations for your portfolio.</p>
            </div>
        </div>
    </div>
</div>

<!-- CTA Banner -->
<?php if (!isLoggedIn()): ?>
<div class="container pb-5">
    <div class="p-5 rounded-4 text-center position-relative overflow-hidden" style="background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%); border: 1px solid rgba(99, 102, 241, 0.3);">
        <h2 class="fw-extrabold mb-2 text-white">Ready to start exchanging skills on campus?</h2>
        <p class="text-secondary mx-auto mb-4" style="max-width: 520px;">Join your fellow university classmates and unlock free peer-to-peer knowledge sharing today.</p>
        <a href="register.php" class="btn btn-primary btn-lg shadow-lg">
            <i class="bi bi-person-plus me-1"></i> Create Free Account
        </a>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
