<?php
require_once __DIR__ . '/config.php';

function get_job_by_id(int $id): ?array {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT j.*, u.username as client_name, u.avatar as client_avatar
            FROM jobs j
            JOIN users u ON j.client_id = u.id
            WHERE j.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log("Job fetch error: " . $e->getMessage());
        return null;
    }
}

function get_user_jobs(int $user_id, string $role): array {
    global $pdo;
    try {
        if ($role === 'client') {
            $stmt = $pdo->prepare("SELECT * FROM jobs WHERE client_id = ? ORDER BY post_date DESC");
        } else {
            $stmt = $pdo->prepare("
                SELECT j.* FROM jobs j
                JOIN applications a ON j.id = a.job_id
                WHERE a.freelancer_id = ?
                ORDER BY j.post_date DESC
            ");
        }
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("User jobs fetch error: " . $e->getMessage());
        return [];
    }
}

function validate_job_data(array $data): array {
    $errors = [];
    
    if (empty(trim($data['title']))) {
        $errors['title'] = 'Title is required';
    } elseif (strlen($data['title']) > 100) {
        $errors['title'] = 'Title too long (max 100 chars)';
    }
    
    if (empty(trim($data['description']))) {
        $errors['description'] = 'Description is required';
    }
    
    if (!is_numeric($data['budget']) || $data['budget'] <= 0) {
        $errors['budget'] = 'Invalid budget amount';
    }
    
    return $errors;
}