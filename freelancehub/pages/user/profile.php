<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_auth();

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$errors = [];
$form_data = [
    'username' => $user['username'],
    'email' => $user['email'],
    'bio' => $user['bio'] ?? ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors['form'] = "Invalid form submission";
    } else {
        $form_data = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'bio' => trim($_POST['bio'] ?? '')
        ];
        
        // Validate inputs
        if (empty($form_data['username'])) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($form_data['username']) > 50) {
            $errors['username'] = 'Username too long (max 50 chars)';
        }
        
        if (empty($form_data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users SET
                        username = ?,
                        email = ?,
                        bio = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $form_data['username'],
                    $form_data['email'],
                    $form_data['bio'],
                    $_SESSION['user_id']
                ]);
                
                // Update session
                $_SESSION['username'] = $form_data['username'];
                
                $success = "Profile updated successfully";
            } catch (PDOException $e) {
                error_log("Profile update error: " . $e->getMessage());
                $errors['form'] = "Profile update failed. Please try again.";
            }
        }
    }
}

$page_title = "Profile | " . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="profile-container">
    <h1>Your Profile</h1>
    
    <?php if (isset($success)): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if (!empty($errors['form'])): ?>
        <div class="alert error"><?= htmlspecialchars($errors['form']) ?></div>
    <?php endif; ?>
    
    <div class="profile-content">
        <div class="profile-sidebar">
            <div class="avatar-container">
                <img src="<?= BASE_URL ?>uploads/avatars/<?= htmlspecialchars($user['avatar']) ?>" 
                     alt="<?= htmlspecialchars($user['username']) ?>">
                <form method="POST" enctype="multipart/form-data" class="avatar-form">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="file" name="avatar" id="avatar" accept="image/*">
                    <label for="avatar" class="btn small">Change Avatar</label>
                </form>
            </div>
            
            <div class="profile-stats">
                <h3>Account Stats</h3>
                <p>
                    <strong>Member since:</strong> 
                    <?= date('M j, Y', strtotime($user['created_at'])) ?>
                </p>
                
                <?php if ($_SESSION['user_role'] === 'client'): ?>
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE client_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $job_count = $stmt->fetchColumn();
                    ?>
                    <p><strong>Jobs Posted:</strong> <?= $job_count ?></p>
                <?php else: ?>
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE freelancer_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $app_count = $stmt->fetchColumn();
                    ?>
                    <p><strong>Applications:</strong> <?= $app_count ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="profile-form">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                
                <div class="form-group <?= isset($errors['username']) ? 'error' : '' ?>">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" 
                           value="<?= htmlspecialchars($form_data['username']) ?>" required>
                    <?php if (isset($errors['username'])): ?>
                        <span class="error-text"><?= htmlspecialchars($errors['username']) ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group <?= isset($errors['email']) ? 'error' : '' ?>">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars($form_data['email']) ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <span class="error-text"><?= htmlspecialchars($errors['email']) ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" rows="4"><?= 
                        htmlspecialchars($form_data['bio']) 
                    ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn primary">Update Profile</button>
                </div>
            </form>
            
            <div class="account-actions">
                <h3>Account Actions</h3>
                <a href="<?= BASE_URL ?>user/settings.php" class="btn">
                    <i class="fas fa-cog"></i> Account Settings
                </a>
                <a href="<?= BASE_URL ?>auth/logout.php" class="btn danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>