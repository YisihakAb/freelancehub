<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_auth();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors['form'] = "Invalid form submission";
    } else {
        // Password change validation
        if (empty($_POST['current_password'])) {
            $errors['current_password'] = 'Current password is required';
        }
        
        if (empty($_POST['new_password'])) {
            $errors['new_password'] = 'New password is required';
        } elseif (strlen($_POST['new_password']) < 8) {
            $errors['new_password'] = 'Password must be at least 8 characters';
        }
        
        if (empty($errors)) {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if (!$user || !password_verify($_POST['current_password'], $user['password_hash'])) {
                    $errors['current_password'] = 'Incorrect current password';
                } else {
                    // Update to new hashed password
                    $new_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$new_hash, $_SESSION['user_id']]);
                    
                    $success = "Password updated successfully";
                }
            } catch (PDOException $e) {
                error_log("Password change error: " . $e->getMessage());
                $errors['form'] = "Password update failed. Please try again.";
            }
        }
    }
}

// [Rest of the file remains the same]
?>
        
        // Handle notification preferences
        if (empty($errors)) {
            // [Add notification preference updates here]
        }
    }
}

$page_title = "Account Settings | " . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="settings-container">
    <h1>Account Settings</h1>
    
    <?php if ($success)): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if (!empty($errors['form'])): ?>
        <div class="alert error"><?= htmlspecialchars($errors['form']) ?></div>
    <?php endif; ?>
    
    <div class="settings-tabs">
        <div class="tab active" data-tab="password">Password</div>
        <div class="tab" data-tab="notifications">Notifications</div>
        <div class="tab" data-tab="danger">Danger Zone</div>
    </div>
    
    <div class="tab-content active" id="password-tab">
        <form method="POST" class="settings-form">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="form-group <?= isset($errors['current_password']) ? 'error' : '' ?>">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password">
                <?php if (isset($errors['current_password'])): ?>
                    <span class="error-text"><?= htmlspecialchars($errors['current_password']) ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group <?= isset($errors['new_password']) ? 'error' : '' ?>">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password">
                <?php if (isset($errors['new_password'])): ?>
                    <span class="error-text"><?= htmlspecialchars($errors['new_password']) ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn primary">Update Password</button>
            </div>
        </form>
    </div>
    
    <div class="tab-content" id="notifications-tab">
        <form method="POST" class="settings-form">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="email_notifications" checked>
                    Email Notifications
                </label>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="app_notifications" checked>
                    In-App Notifications
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn primary">Save Preferences</button>
            </div>
        </form>
    </div>
    
    <div class="tab-content" id="danger-tab">
        <div class="danger-zone">
            <h3>Delete Account</h3>
            <p>This will permanently remove your account and all associated data.</p>
            <button class="btn danger" id="delete-account-btn">
                <i class="fas fa-trash"></i> Delete Account
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById(this.dataset.tab + '-tab').classList.add('active');
        });
    });
    
    // Delete account confirmation
    document.getElementById('delete-account-btn').addEventListener('click', function() {
        if (confirm('Are you sure you want to permanently delete your account?')) {
            window.location.href = '<?= BASE_URL ?>user/delete-account.php';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>