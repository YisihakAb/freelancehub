<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Validate Job ID
$job_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) or die(header("Location: " . BASE_URL . "jobs/list.php"));

// Get Job Data
$stmt = $pdo->prepare("
    SELECT j.*, u.username as client_name 
    FROM jobs j
    JOIN users u ON j.client_id = u.id
    WHERE j.id = ?
");
$stmt->execute([$job_id]);
$job = $stmt->fetch() or die(header("Location: " . BASE_URL . "jobs/list.php"));

// Authorization
$is_owner = ($_SESSION['user_id'] ?? null) === $job['client_id'];
$can_apply = ($_SESSION['user_role'] ?? '') === 'freelancer';

$page_title = htmlspecialchars($job['title']) . " | " . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- [Job View HTML from previous example] -->

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>