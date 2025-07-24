<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('client');

$errors = [];
$form_data = [
    'title' => '',
    'description' => '',
    'requirements' => '',
    'budget' => ''
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
                    INSERT INTO jobs (
                        client_id, title, description, requirements, budget
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $form_data['title'],
                    $form_data['description'],
                    $form_data['requirements'],
                    $form_data['budget']
                ]);
                
                $job_id = $pdo->lastInsertId();
                header("Location: " . BASE_URL . "jobs/view.php?id=" . $job_id);
                exit();
            } catch (PDOException $e) {
                error_log("Job creation error: " . $e->getMessage());
                $errors['form'] = "Job posting failed. Please try again.";
            }
        }
    }
}

$page_title = "Post New Job | " . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="job-form-container">
    <h1>Post a New Job</h1>
    
    <?php if (!empty($errors['form'])): ?>
        <div class="alert error"><?= htmlspecialchars($errors['form']) ?></div>
    <?php endif; ?>
    
    <form method="POST" class="job-form">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        
        <div class="form-group <?= isset($errors['title']) ? 'error' : '' ?>">
            <label for="title">Job Title</label>
            <input type="text" id="title" name="title" 
                   value="<?= htmlspecialchars($form_data['title']) ?>" required>
            <?php if (isset($errors['title'])): ?>
                <span class="error-text"><?= htmlspecialchars($errors['title']) ?></span>
            <?php endif; ?>
        </div>
        
        <div class="form-group <?= isset($errors['description']) ? 'error' : '' ?>">
            <label for="description">Job Description</label>
            <textarea id="description" name="description" rows="6" required><?= 
                htmlspecialchars($form_data['description']) 
            ?></textarea>
            <?php if (isset($errors['description'])): ?>
                <span class="error-text"><?= htmlspecialchars($errors['description']) ?></span>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="requirements">Required Skills (Optional)</label>
            <textarea id="requirements" name="requirements" rows="4"><?= 
                htmlspecialchars($form_data['requirements']) 
            ?></textarea>
            <p class="hint">List one skill per line</p>
        </div>
        
        <div class="form-group <?= isset($errors['budget']) ? 'error' : '' ?>">
            <label for="budget">Budget ($)</label>
            <input type="number" id="budget" name="budget" 
                   step="0.01" min="0.01" value="<?= htmlspecialchars($form_data['budget']) ?>" required>
            <?php if (isset($errors['budget'])): ?>
                <span class="error-text"><?= htmlspecialchars($errors['budget']) ?></span>
            <?php endif; ?>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn primary">Post Job</button>
            <a href="<?= BASE_URL ?>jobs/list.php" class="btn">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>