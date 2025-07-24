<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../classes/Job.php';

$job = new Job($pdo);
$filters = [];

if (isset($_GET['category'])) {
    $filters['category'] = $_GET['category'];
}

if (isset($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

$jobs = $job->getJobs($filters);
$categories = ['Web Development', 'Graphic Design', 'Content Writing', 'Digital Marketing', 'Mobile Development'];

include '../../includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                                        <?php echo (isset($_GET['category']) && $_GET['category'] === $cat) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="d-flex justify-content-between mb-4">
                <h2>Available Jobs</h2>
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search jobs..." 
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
            
            <?php if (empty($jobs)): ?>
                <div class="alert alert-info">No jobs found matching your criteria.</div>
            <?php else: ?>
                <?php foreach ($jobs as $job): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h5 class="card-title"><?php echo htmlspecialchars($job['title']); ?></h5>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($job['category']); ?></span>
                            </div>
                            <h6 class="card-subtitle mb-2 text-muted">Posted by: <?php echo htmlspecialchars($job['client_name']); ?></h6>
                            <p class="card-text"><?php echo substr(htmlspecialchars($job['description']), 0, 200); ?>...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if ($job['budget'] > 0): ?>
                                        <span class="fw-bold">Budget: $<?php echo number_format($job['budget'], 2); ?></span>
                                    <?php else: ?>
                                        <span class="fw-bold">Budget: Negotiable</span>
                                    <?php endif; ?>
                                </div>
                                <a href="job-view.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>