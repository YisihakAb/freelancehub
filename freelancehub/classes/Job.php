<?php
class Job {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function create($title, $description, $category, $budget, $deadline, $clientId, $skills) {
        try {
            $this->pdo->beginTransaction();
            
            // Insert job
            $stmt = $this->pdo->prepare("INSERT INTO jobs (title, description, category, budget, deadline, client_id) 
                                        VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $category, $budget, $deadline, $clientId]);
            $jobId = $this->pdo->lastInsertId();
            
            // Insert skills
            $skillStmt = $this->pdo->prepare("INSERT INTO job_skills (job_id, skill) VALUES (?, ?)");
            foreach ($skills as $skill) {
                $skillStmt->execute([$jobId, $skill]);
            }
            
            $this->pdo->commit();
            return $jobId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    public function getJobs($filters = []) {
        $query = "SELECT j.*, u.name as client_name FROM jobs j JOIN users u ON j.client_id = u.id WHERE 1=1";
        $params = [];
        
        if (!empty($filters['category'])) {
            $query .= " AND j.category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (j.title LIKE ? OR j.description LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        
        $query .= " ORDER BY j.created_at DESC";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Other methods: getJobById, updateJob, deleteJob, etc.
}
?>