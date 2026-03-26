<?php
// Start output buffering
ob_start();

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/header.php';

// Check if user is admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();

// Get date filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'summary';

// Get job statistics
$stats = [];

// Total jobs
$query = "SELECT COUNT(*) as total FROM jobs";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_jobs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Jobs by status
$query = "SELECT status, COUNT(*) as count FROM jobs GROUP BY status";
$stmt = $db->prepare($query);
$stmt->execute();
$jobs_by_status = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Jobs by category
$query = "SELECT c.name, COUNT(j.id) as count 
          FROM categories c 
          LEFT JOIN jobs j ON c.id = j.category_id 
          GROUP BY c.id 
          ORDER BY count DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$jobs_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Jobs by location
$query = "SELECT location, COUNT(*) as count 
          FROM jobs 
          WHERE location IS NOT NULL 
          GROUP BY location 
          ORDER BY count DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$jobs_by_location = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Jobs by job type
$query = "SELECT job_type, COUNT(*) as count FROM jobs GROUP BY job_type";
$stmt = $db->prepare($query);
$stmt->execute();
$jobs_by_type = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Jobs posted in date range
$query = "SELECT DATE(created_at) as date, COUNT(*) as count 
          FROM jobs 
          WHERE DATE(created_at) BETWEEN ? AND ? 
          GROUP BY DATE(created_at) 
          ORDER BY date";
$stmt = $db->prepare($query);
$stmt->execute([$start_date, $end_date]);
$daily_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total applications
$query = "SELECT COUNT(*) as total FROM applications";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Applications by status
$query = "SELECT status, COUNT(*) as count FROM applications GROUP BY status";
$stmt = $db->prepare($query);
$stmt->execute();
$applications_by_status = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Applications per job (top 10)
$query = "SELECT j.title, COUNT(a.id) as application_count 
          FROM jobs j 
          LEFT JOIN applications a ON j.id = a.job_id 
          GROUP BY j.id 
          ORDER BY application_count DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$top_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top employers (by job posts)
$query = "SELECT u.full_name, COUNT(j.id) as job_count 
          FROM users u 
          LEFT JOIN jobs j ON u.id = j.employer_id 
          WHERE u.role = 'employer' 
          GROUP BY u.id 
          ORDER BY job_count DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$top_employers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly job posts (last 12 months)
$query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
          FROM jobs 
          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
          GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
          ORDER BY month";
$stmt = $db->prepare($query);
$stmt->execute();
$monthly_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate averages
$stats['avg_applications_per_job'] = $stats['total_jobs'] > 0 ? round($stats['total_applications'] / $stats['total_jobs'], 1) : 0;
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-chart-line me-2"></i>Job Reports & Analytics</h2>
            <p class="text-muted">Comprehensive job market insights and statistics</p>
        </div>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print me-2"></i>Print Report
        </button>
    </div>

    <!-- Date Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Key Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-briefcase fa-3x mb-2" style="color: #1e847f;"></i>
                    <h3><?php echo $stats['total_jobs']; ?></h3>
                    <p class="mb-0">Total Jobs</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-file-alt fa-3x mb-2" style="color: #ecc19c;"></i>
                    <h3><?php echo $stats['total_applications']; ?></h3>
                    <p class="mb-0">Total Applications</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-chart-line fa-3x mb-2" style="color: #1e847f;"></i>
                    <h3><?php echo $stats['avg_applications_per_job']; ?></h3>
                    <p class="mb-0">Avg Applications/Job</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-building fa-3x mb-2" style="color: #ecc19c;"></i>
                    <h3><?php echo count($top_employers); ?></h3>
                    <p class="mb-0">Active Employers</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Jobs by Status Chart -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Jobs by Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="250"></canvas>
                    <div class="mt-3">
                        <?php foreach ($jobs_by_status as $status): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>
                                    <i class="fas fa-circle me-2" style="color: 
                                        <?php echo $status['status'] == 'open' ? '#28a745' : ($status['status'] == 'pending' ? '#ffc107' : '#dc3545'); ?>">
                                    </i>
                                    <?php echo ucfirst($status['status']); ?>
                                </span>
                                <span class="fw-bold"><?php echo $status['count']; ?> jobs (<?php echo round($status['count'] / $stats['total_jobs'] * 100); ?>%)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jobs by Type Chart -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Jobs by Type</h5>
                </div>
                <div class="card-body">
                    <canvas id="typeChart" height="250"></canvas>
                    <div class="mt-3">
                        <?php foreach ($jobs_by_type as $type): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo ucfirst(str_replace('-', ' ', $type['job_type'])); ?></span>
                                <span class="fw-bold"><?php echo $type['count']; ?> jobs</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Categories -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Top Categories by Jobs</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Jobs</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jobs_by_category as $cat): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cat['name'] ?: 'Uncategorized'); ?></td>
                                        <td><?php echo $cat['count']; ?></td>
                                        <td><?php echo round($cat['count'] / $stats['total_jobs'] * 100); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Locations -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Top Locations</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Location</th>
                                    <th>Jobs</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jobs_by_location as $loc): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($loc['location']); ?></td>
                                        <td><?php echo $loc['count']; ?></td>
                                        <td><?php echo round($loc['count'] / $stats['total_jobs'] * 100); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Jobs by Applications -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-star me-2"></i>Top Jobs by Applications</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Job Title</th>
                                    <th>Applications</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_jobs as $job): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($job['title']); ?></td>
                                        <td><?php echo $job['application_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Employers -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i>Top Employers</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Employer</th>
                                    <th>Jobs Posted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_employers as $emp): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($emp['full_name']); ?></td>
                                        <td><?php echo $emp['job_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Application Status -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Application Status Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($applications_by_status as $app): ?>
                            <div class="col-md-2 text-center mb-3">
                                <div class="border rounded p-3">
                                    <i class="fas 
                                        <?php echo $app['status'] == 'pending' ? 'fa-clock' : ($app['status'] == 'accepted' ? 'fa-check-circle' : 'fa-times-circle'); ?> 
                                        fa-2x mb-2" style="color: 
                                        <?php echo $app['status'] == 'pending' ? '#ffc107' : ($app['status'] == 'accepted' ? '#28a745' : '#dc3545'); ?>">
                                    </i>
                                    <h4><?php echo $app['count']; ?></h4>
                                    <p class="mb-0"><?php echo ucfirst($app['status']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Jobs Trend -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Daily Jobs Posted (<?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?>)</h5>
        </div>
        <div class="card-body">
            <canvas id="dailyJobsChart" height="300"></canvas>
        </div>
    </div>

    <!-- Monthly Trend -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Monthly Jobs Trend (Last 12 Months)</h5>
        </div>
        <div class="card-body">
            <canvas id="monthlyJobsChart" height="300"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusData = <?php
                        $status_labels = array_column($jobs_by_status, 'status');
                        $status_counts = array_column($jobs_by_status, 'count');
                        echo json_encode(['labels' => array_map('ucfirst', $status_labels), 'data' => $status_counts]);
                        ?>;
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusData.labels,
            datasets: [{
                data: statusData.data,
                backgroundColor: ['#28a745', '#ffc107', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Job Type Chart
    const typeCtx = document.getElementById('typeChart').getContext('2d');
    const typeData = <?php
                        $type_labels = array_map(function ($t) {
                            return ucfirst(str_replace('-', ' ', $t['job_type']));
                        }, $jobs_by_type);
                        $type_counts = array_column($jobs_by_type, 'count');
                        echo json_encode(['labels' => $type_labels, 'data' => $type_counts]);
                        ?>;
    new Chart(typeCtx, {
        type: 'bar',
        data: {
            labels: typeData.labels,
            datasets: [{
                label: 'Number of Jobs',
                data: typeData.data,
                backgroundColor: '#1e847f'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Daily Jobs Chart
    const dailyCtx = document.getElementById('dailyJobsChart').getContext('2d');
    const dailyData = <?php
                        $daily_dates = array_column($daily_jobs, 'date');
                        $daily_counts = array_column($daily_jobs, 'count');
                        echo json_encode(['labels' => $daily_dates, 'data' => $daily_counts]);
                        ?>;
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: dailyData.labels,
            datasets: [{
                label: 'Jobs Posted',
                data: dailyData.data,
                borderColor: '#1e847f',
                backgroundColor: 'rgba(30, 132, 127, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Monthly Jobs Chart
    const monthlyCtx = document.getElementById('monthlyJobsChart').getContext('2d');
    const monthlyData = <?php
                        $monthly_labels = array_column($monthly_jobs, 'month');
                        $monthly_counts = array_column($monthly_jobs, 'count');
                        echo json_encode(['labels' => $monthly_labels, 'data' => $monthly_counts]);
                        ?>;
    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: monthlyData.labels,
            datasets: [{
                label: 'Jobs Posted',
                data: monthlyData.data,
                backgroundColor: '#ecc19c'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
</script>

<style>
    @media print {

        .btn,
        .btn-primary,
        form,
        .no-print {
            display: none !important;
        }

        .card {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        body {
            background: white;
        }

        .container {
            max-width: 100%;
            margin: 0;
            padding: 0;
        }
    }
</style>

<?php
require_once '../includes/footer.php';
?>