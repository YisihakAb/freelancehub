<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('client');

// Validate Job ID
$job_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) or die(header("Location: " . BASE_URL . "jobs/list.php"));

// Get Job Data
$job = get_job_by_id($job_id) or die(header("Location: " . BASE_URL . "jobs/list.php"));

// Verify ownership
if ($job['client_id'] !== $_SESSION['user_id']) {
    header("HTTP/1.1 403 Forbidden");
    die("You don't have permission to edit this job");
}

$errors = [];
$form_data = [
    'title' => $job['title'],
    'description' => $job['description'],
    'requirements' => $job['requirements'],
    'budget' => $job['budget']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors['form'] = "Invalid form submission";
    } else {
        $form_data = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'requirements' => trim($_POST['requirements'] ?? ''),
            'budget' => trim($_POST['budget'] ?? '')
        ];
        
        $errors = validate_job_data($form_data);
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE jobs SET
                        title = ?,
                        description = ?,
                        requirements = ?,
                        budget = ?,
                        updated_at = NOW()
                    WHERE id = ? AND client_id = ?
                ");
                $stmt->execute([
                    $form_data['title'],
                    $form_data['description'],
                    $form_data['requirements'],
                    $form_data['budget'],
                    $job_id,
                    $_SESSION['user_id']
                ]);
                
                header("Location: " . BASE_URL . "jobs/view.php?id=" . $job_id);
                exit();
            } catch (PDOException $e) {
                error_log("Job update error: " . $e->getMessage());
                $errors['form'] = "Job update failed. Please try again.";
            }
        }
    }
}

$page_title = "Edit Job | " . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="job-form-container">
    <h1>Edit Job: <?= htmlspecialchars($job['title']) ?></h1>
    
    <?php if (!empty($errors['form'])): ?>
        <div class="alert error"><?= htmlspecialchars($errors['form']) ?></div>
    <?php endif; ?>
    
    <form method="POST" class="job-form">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        
        <!-- [Same form fields as create.php but with existing values] -->
        
        <div class="form-actions">
            <button type="submit" class="btn primary">Update Job</button>
            <a href="<?= BASE_URL ?>jobs/view.php?id=<?= $job_id ?>" class="btn">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>