<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('freelancer');

// Validate Job ID
$job_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) or die(header("Location: " . BASE_URL . "jobs/list.php"));

// Get Job Data
$job = get_job_by_id($job_id) or die(header("Location: " . BASE_URL . "jobs/list.php"));

// Check if already applied
$stmt = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND freelancer_id = ?");
$stmt->execute([$job_id, $_SESSION['user_id']]);
$already_applied = $stmt->fetch();

$errors = [];
$form_data = [
    'proposal' => '',
    'bid_amount' => $job['budget'] ? $job['budget'] * 0.8 : 0 // Default to 80% of job budget
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors['form'] = "Invalid form submission";
    } elseif ($already_applied) {
        $errors['form'] = "You've already applied to this job";
    } else {
        $form_data = [
            'proposal' => trim($_POST['proposal'] ?? ''),
            'bid_amount' => floatval($_POST['bid_amount'] ?? 0)
        ];
        
        // Validate inputs
        if (empty($form_data['proposal'])) {
            $errors['proposal'] = 'Proposal is required';
        } elseif (strlen($form_data['proposal']) < 50) {
            $errors['proposal'] = 'Proposal too short (min 50 chars)';
        }
        
        if ($form_data['bid_amount'] <= 0) {
            $errors['bid_amount'] = 'Invalid bid amount';
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO applications (job_id, freelancer_id, proposal, bid_amount)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $job_id,
                    $_SESSION['user_id'],
                    $form_data['proposal'],
                    $form_data['bid_amount']
                ]);
                
                header("Location: " . BASE_URL . "jobs/view.php?id=" . $job_id);
                exit();
            } catch (PDOException $e) {
                error_log("Application error: " . $e->getMessage());
                $errors['form'] = "Application failed. Please try again.";
            }
        }
    }
}

$page_title = "Apply to " . htmlspecialchars($job['title']) . " | " . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="job-application">
    <h1>Apply to: <?= htmlspecialchars($job['title']) ?></h1>
    
    <?php if ($already_applied): ?>
        <div class="alert info">
            You've already applied to this job on <?= date('M j, Y', strtotime($already_applied['applied_at'])) ?>
        </div>
        <a href="<?= BASE_URL ?>jobs/view.php?id=<?= $job_id ?>" class="btn">
            Back to Job
        </a>
    <?php else: ?>
        <?php if (!empty($errors['form'])): ?>
            <div class="alert error"><?= htmlspecialchars($errors['form']) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="application-form">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="form-group <?= isset($errors['proposal']) ? 'error' : '' ?>">
                <label for="proposal">Your Proposal</label>
                <textarea id="proposal" name="proposal" rows="8" required><?= 
                    htmlspecialchars($form_data['proposal']) 
                ?></textarea>
                <?php if (isset($errors['proposal'])): ?>
                    <span class="error-text"><?= htmlspecialchars($errors['proposal']) ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group <?= isset($errors['bid_amount']) ? 'error' : '' ?>">
                <label for="bid_amount">Bid Amount ($)</label>
                <input type="number" id="bid_amount" name="bid_amount" 
                       step="0.01" min="0.01" value="<?= $form_data['bid_amount'] ?>" required>
                <?php if (isset($errors['bid_amount'])): ?>
                    <span class="error-text"><?= htmlspecialchars($errors['bid_amount']) ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn primary">Submit Application</button>
                <a href="<?= BASE_URL ?>jobs/view.php?id=<?= $job_id ?>" class="btn">
                    Cancel
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>