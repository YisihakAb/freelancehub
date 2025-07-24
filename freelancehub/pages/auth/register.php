<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'pages/dashboard.php');
    exit;
}

$errors = [];
$form_data = [
    'name' => '',
    'email' => '',
    'role' => $_GET['role'] ?? 'freelancer'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors['general'] = "Invalid form submission";
    } else {
        $form_data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'role' => in_array($_POST['role'] ?? '', ['client', 'freelancer']) ? $_POST['role'] : 'freelancer'
        ];

        // Validation
        if (empty($form_data['name'])) {
            $errors['name'] = 'Name is required';
        }

        if (empty($form_data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (empty($form_data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($form_data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }

        if ($form_data['password'] !== $form_data['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        if (empty($errors)) {
            try {
                // Check if email exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$form_data['email']]);
                
                if ($stmt->fetch()) {
                    $errors['email'] = 'Email already registered';
                } else {
                    // Create user
                    $password_hash = password_hash($form_data['password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, email, password_hash, role) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $form_data['name'],
                        $form_data['email'],
                        $password_hash,
                        $form_data['role']
                    ]);

                    // Set session and redirect
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['username'] = $form_data['name'];
                    $_SESSION['user_role'] = $form_data['role'];
                    
                    header('Location: ' . BASE_URL . 'pages/dashboard.php');
                    exit;
                }
            } catch (PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                $errors['general'] = "Registration failed. Please try again.";
            }
        }
    }
}

$page_title = "Register";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="auth-container">
    <h1>Create Your Account</h1>
    
    <?php if (!empty($errors['general'])): ?>
        <div class="alert error"><?php echo htmlspecialchars($errors['general']); ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" class="form-control" 
                   value="<?php echo htmlspecialchars($form_data['name']); ?>" required>
            <?php if (isset($errors['name'])): ?>
                <span class="error-text"><?php echo htmlspecialchars($errors['name']); ?></span>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" 
                   value="<?php echo htmlspecialchars($form_data['email']); ?>" required>
            <?php if (isset($errors['email'])): ?>
                <span class="error-text"><?php echo htmlspecialchars($errors['email']); ?></span>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" required>
            <?php if (isset($errors['password'])): ?>
                <span class="error-text"><?php echo htmlspecialchars($errors['password']); ?></span>
            <?php endif; ?>
            <small>Minimum 8 characters</small>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            <?php if (isset($errors['confirm_password'])): ?>
                <span class="error-text"><?php echo htmlspecialchars($errors['confirm_password']); ?></span>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label>I want to:</label>
            <div class="role-selector">
                <div class="role-option">
                    <input type="radio" name="role" id="freelancer" value="freelancer" 
                        <?php echo ($form_data['role'] === 'freelancer') ? 'checked' : ''; ?>>
                    <label for="freelancer">Find Work (Freelancer)</label>
                </div>
                <div class="role-option">
                    <input type="radio" name="role" id="client" value="client" 
                        <?php echo ($form_data['role'] === 'client') ? 'checked' : ''; ?>>
                    <label for="client">Hire Freelancers (Client)</label>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Register</button>
        
        <div class="auth-links">
            Already have an account? <a href="<?php echo BASE_URL; ?>auth/login.php">Log in</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>