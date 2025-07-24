<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_auth();

// Get user-specific data
if ($_SESSION['user_role'] === 'client') {
    // Client dashboard - show posted jobs and applications
    $stmt = $pdo->prepare("
        SELECT j.*, COUNT(a.id) as applications
        FROM jobs j
        LEFT JOIN applications a ON j.id = a.job_id
        WHERE j.client_id = ?
        GROUP BY j.id
        ORDER BY j.post_date DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_jobs = $stmt->fetchAll();
    
    // Count total jobs
    $total_jobs = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE client_id = ?");
    $total_jobs->execute([$_SESSION['user_id']]);
    $total_jobs = $total_jobs->fetchColumn();
} else {
    // Freelancer dashboard - show applications and available jobs
    $stmt = $pdo->prepare("
        SELECT a.*, j.title as job_title, j.budget, j.status as job_status
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        WHERE a.freelancer_id = ?
        ORDER BY a.applied_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_applications = $stmt->fetchAll();
    
    // Count total applications
    $total_applications = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE freelancer_id = ?");
    $total_applications->execute([$_SESSION['user_id']]);
    $total_applications = $total_applications->fetchColumn();
}

$page_title = "Dashboard | " . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-container">
    <h1>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
    
    <div class="dashboard-stats">
        <?php if ($_SESSION['user_role'] === 'client'): ?>
            <div class="stat-card">
                <h3>Posted Jobs</h3>
                <p><?= $total_jobs ?></p>
                <a href="<?= BASE_URL ?>jobs/list.php" class="btn small">View All</a>
            </div>
            
            <div class="stat-card">
                <h3>Active Jobs</h3>
                <p>
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE client_id = ? AND status = 'open'");
                    $stmt->execute([$_SESSION['user_id']]);
                    echo $stmt->fetchColumn();
                    ?>
                </p>
            </div>
        <?php else: ?>
            <div class="stat-card">
                <h3>Applications</h3>
                <p><?= $total_applications ?></p>
                <a href="<?= BASE_URL ?>jobs/list.php" class="btn small">Find Jobs</a>
            </div>
            
            <div class="stat-card">
                <h3>Active Projects</h3>
                <p>
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) 
                        FROM applications a
                        JOIN jobs j ON a.job_id = j.id
                        WHERE a.freelancer_id = ? AND j.status = 'in_progress' AND a.status = 'accepted'
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    echo $stmt->fetchColumn();
                    ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="dashboard-section">
        <?php if ($_SESSION['user_role'] === 'client'): ?>
            <h2>Your Recent Jobs</h2>
            
            <?php if (empty($recent_jobs)): ?>
                <div class="alert info">
                    You haven't posted any jobs yet. 
                    <a href="<?= BASE_URL ?>jobs/create.php">Post your first job</a>
                </div>
            <?php else: ?>
                <div class="recent-jobs">
                    <?php foreach ($recent_jobs as $job): ?>
                        <div class="job-item">
                            <h3>
                                <a href="<?= BASE_URL ?>jobs/view.php?id=<?= $job['id'] ?>">
                                    <?= htmlspecialchars($job['title']) ?>
                                </a>
                            </h3>
                            <div class="job-meta">
                                <span>Posted: <?= date('M j, Y', strtotime($job['post_date'])) ?></span>
                                <span>Budget: $<?= number_format($job['budget'], 2) ?></span>
                                <span>Applications: <?= $job['applications'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <h2>Your Recent Applications</h2>
            
            <?php if (empty($recent_applications)): ?>
                <div class="alert info">
                    You haven't applied to any jobs yet. 
                    <a href="<?= BASE_URL ?>jobs/list.php">Browse available jobs</a>
                </div>
            <?php else: ?>
                <div class="recent-applications">
                    <?php foreach ($recent_applications as $app): ?>
                        <div class="application-item">
                            <h3>
                                <a href="<?= BASE_URL ?>jobs/view.php?id=<?= $app['job_id'] ?>">
                                    <?= htmlspecialchars($app['job_title']) ?>
                                </a>
                            </h3>
                            <div class="application-meta">
                                <span>Applied: <?= date('M j, Y', strtotime($app['applied_at'])) ?></span>
                                <span>Status: 
                                    <span class="status-<?= $app['status'] ?>">
                                        <?= ucfirst($app['status']) ?>
                                    </span>
                                </span>
                                <span>Job Status: 
                                    <span class="status-<?= $app['job_status'] ?>">
                                        <?= ucfirst(str_replace('_', ' ', $app['job_status'])) ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>