<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | ' . APP_NAME : APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="<?php echo BASE_URL; ?>"><?php echo APP_NAME; ?></a>
            </div>
            
            <nav class="main-nav">
                <ul>
                    <li><a href="<?php  BASE_URL; ?>">Home</a></li>
                    <li><a href="<?php  BASE_URL; ?>pages/jobs/jobs.php">Jobs</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="<?php BASE_URL; ?>dashboard">Dashboard</a></li>
                        <li><a href="<?php BASE_URL; ?>pages/profile.php">Profile</a></li>
                        <li><a href="<?php BASE_URL; ?>auth/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?php BASE_URL; ?>pages/auth/register.php">Register</a></li>
                        <li><a href="<?php BASE_URL; ?>pages/auth/login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>
    
    <main class="container">