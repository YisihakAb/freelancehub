<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_auth();

$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Get jobs based on user role
if ($_SESSION['user_role'] === 'client') {
    $stmt = $pdo->prepare("
        SELECT j.*, COUNT(a.id) as applications
        FROM jobs j
        LEFT JOIN applications a ON j.id = a.job_id
        WHERE j.client_id = ?
        GROUP BY j.id
        ORDER BY j.post_date DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$_SESSION['user_id'], $limit, $offset]);
    
    // Count total jobs for pagination
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE client_id = ?");
    $count_stmt->execute([$_SESSION['user_id']]);
} else {
    $stmt = $pdo->prepare("
        SELECT j.*, u.username as client_name,
               EXISTS(
                   SELECT 1 FROM applications a 
                   WHERE a.job_id = j.id AND a.freelancer_id = ?
               ) as has_applied
        FROM jobs j
        JOIN users u ON j.client_id = u.id
        WHERE j.status = 'open'
        ORDER BY j.post_date DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$_SESSION['user_id'], $limit, $offset]);
    
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE status = 'open'");
    $count_stmt->execute();
}

$jobs = $stmt->fetchAll();
$total_jobs = $count_stmt->fetchColumn();
$total_pages = ceil($total_jobs / $limit);

$page_title = "Job Listings | " . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="jobs-list-container">
    <div class="jobs-header">
        <h1>
            <?php if ($_SESSION['user_role'] === 'client'): ?>
                Your Posted Jobs
            <?php else: ?>
                Available Jobs
            <?php endif; ?>
        </h1>
        
        <?php if ($_SESSION['user_role'] === 'client'): ?>
            <a href="<?= BASE_URL ?>jobs/create.php" class="btn primary">
                <i class="fas fa-plus"></i> Post New Job
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (empty($jobs)): ?>
        <div class="alert info">
            <?php if ($_SESSION['user_role'] === 'client'): ?>
                You haven't posted any jobs yet.
            <?php else: ?>
                No available jobs at the moment.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="jobs-grid">
            <?php foreach ($jobs as $job): ?>
                <div class="job-card">
                    <div class="job-header">
                        <h2>
                            <a href="<?= BASE_URL ?>jobs/view.php?id=<?= $job['id'] ?>">
                                <?= htmlspecialchars($job['title']) ?>
                            </a>
                        </h2>
                        <span class="job-budget">$<?= number_format($job['budget'], 2) ?></span>
                    </div>
                    
                    <div class="job-meta">
                        <span>
                            <i class="fas fa-clock"></i> 
                            <?= date('M j, Y', strtotime($job['post_date'])) ?>
                        </span>
                        
                        <?php if ($_SESSION['user_role'] === 'client'): ?>
                            <span>
                                <i class="fas fa-users"></i> 
                                <?= $job['applications'] ?> applications
                            </span>
                        <?php elseif ($job['has_applied']): ?>
                            <span class="applied-badge">
                                <i class="fas fa-check"></i> Applied
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="job-description">
                        <?= nl2br(htmlspecialchars(substr($job['description'], 0, 200))) ?>
                        <?= strlen($job['description']) > 200 ? '...' : '' ?>
                    </div>
                    
                    <div class="job-actions">
                        <a href="<?= BASE_URL ?>jobs/view.php?id=<?= $job['id'] ?>" class="btn">
                            View Details
                        </a>
                        
                        <?php if ($_SESSION['user_role'] === 'freelancer' && !$job['has_applied']): ?>
                            <a href="<?= BASE_URL ?>jobs/apply.php?id=<?= $job['id'] ?>" class="btn primary">
                                Apply Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="btn">
                        <i class="fas fa-arrow-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <span>Page <?= $page ?> of <?= $total_pages ?></span>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="btn">
                        Next <i class="fas fa-arrow-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>