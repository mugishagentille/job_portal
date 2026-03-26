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

$message = '';
$error = '';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    // Check if category has jobs
    $check = $db->prepare("SELECT COUNT(*) FROM jobs WHERE category_id = ?");
    $check->execute([$_GET['delete']]);
    $job_count = $check->fetchColumn();

    if ($job_count > 0) {
        $error = "Cannot delete this category. There are $job_count jobs associated with it.";
    } else {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt->execute([$_GET['delete']])) {
            $message = "Category deleted successfully!";
        } else {
            $error = "Failed to delete category.";
        }
    }
}

// Handle status toggle
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $stmt = $db->prepare("UPDATE categories SET status = IF(status='active', 'inactive', 'active') WHERE id = ?");
    if ($stmt->execute([$_GET['toggle']])) {
        $message = "Category status updated successfully!";
    } else {
        $error = "Failed to update category status.";
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $name = trim($_POST['name']);
    $icon = trim($_POST['icon']);
    $description = trim($_POST['description']);

    // Validation
    $errors = [];
    if (empty($name)) {
        $errors[] = "Category name is required";
    }
    if (strlen($name) < 2) {
        $errors[] = "Category name must be at least 2 characters";
    }

    // Check duplicate name
    if ($id > 0) {
        $check = $db->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
        $check->execute([$name, $id]);
    } else {
        $check = $db->prepare("SELECT id FROM categories WHERE name = ?");
        $check->execute([$name]);
    }

    if ($check->fetch()) {
        $errors[] = "Category name already exists";
    }

    if (empty($errors)) {
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE categories SET name = ?, icon = ?, description = ? WHERE id = ?");
            if ($stmt->execute([$name, $icon, $description, $id])) {
                $message = "Category updated successfully!";
            } else {
                $error = "Failed to update category.";
            }
        } else {
            $stmt = $db->prepare("INSERT INTO categories (name, icon, description) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $icon, $description])) {
                $message = "Category added successfully!";
            } else {
                $error = "Failed to add category.";
            }
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// Get all categories with job counts
$query = "SELECT c.*, COUNT(j.id) as job_count 
          FROM categories c 
          LEFT JOIN jobs j ON c.id = j.category_id 
          GROUP BY c.id 
          ORDER BY c.name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-tags me-2"></i>Manage Categories</h2>
            <p class="text-muted">Create, edit, and manage job categories</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="resetForm()">
            <i class="fas fa-plus-circle me-2"></i>Add Category
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Icon</th>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Jobs</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="fas fa-folder-open" style="font-size: 3rem; color: #ccc;"></i>
                                    <p class="mt-2 text-muted">No categories found</p>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="resetForm()">
                                        <i class="fas fa-plus-circle me-1"></i>Create First Category
                                    </button>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td class="fw-bold">#<?php echo $cat['id']; ?></td>
                                    <td>
                                        <i class="fas <?php echo $cat['icon'] ?: 'fa-tag'; ?> fa-lg" style="color: #1e847f;"></i>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($cat['name']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(substr($cat['description'] ?? '', 0, 50)); ?>
                                        <?php if (strlen($cat['description'] ?? '') > 50): ?>...<?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: #ecc19c; color: #1e847f;">
                                            <?php echo $cat['job_count']; ?> jobs
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $cat['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($cat['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo date('M d, Y', strtotime($cat['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="editCategory(<?php echo htmlspecialchars(json_encode($cat)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?toggle=<?php echo $cat['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-<?php echo $cat['status'] == 'active' ? 'pause' : 'play'; ?>"></i>
                                        </a>
                                        <?php if ($cat['job_count'] == 0): ?>
                                            <a href="?delete=<?php echo $cat['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled title="Cannot delete: Category has jobs">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Category Statistics -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-tags fa-2x" style="color: #1e847f;"></i>
                    <h3 class="mt-2 mb-0"><?php echo count($categories); ?></h3>
                    <small class="text-muted">Total Categories</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-2x" style="color: #28a745;"></i>
                    <h3 class="mt-2 mb-0"><?php echo count(array_filter($categories, function ($c) {
                                                return $c['status'] == 'active';
                                            })); ?></h3>
                    <small class="text-muted">Active Categories</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-briefcase fa-2x" style="color: #ecc19c;"></i>
                    <h3 class="mt-2 mb-0"><?php echo array_sum(array_column($categories, 'job_count')); ?></h3>
                    <small class="text-muted">Total Jobs</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-calendar-week fa-2x" style="color: #1e847f;"></i>
                    <h3 class="mt-2 mb-0"><?php
                                            $this_month = count(array_filter($categories, function ($c) {
                                                return date('Y-m', strtotime($c['created_at'])) == date('Y-m');
                                            }));
                                            echo $this_month;
                                            ?></h3>
                    <small class="text-muted">Added This Month</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: #1e847f; color: #ecc19c;">
                <h5 class="modal-title"><i class="fas fa-tag me-2"></i>Category Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="categoryId">

                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="name" id="categoryName" class="form-control" required>
                        <small class="text-muted">Example: Technology, Healthcare, Finance</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Font Awesome Icon</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-tag" id="iconPreview"></i></span>
                            <input type="text" name="icon" id="categoryIcon" class="form-control" placeholder="fa-laptop, fa-heartbeat, fa-chart-line">
                        </div>
                        <small class="text-muted">Browse icons at <a href="https://fontawesome.com/icons" target="_blank">Font Awesome</a></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="categoryDesc" class="form-control" rows="3" placeholder="Brief description of this category"></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Popular Icons:</strong>
                        <span class="ms-2">
                            <code class="me-2">fa-laptop</code>
                            <code class="me-2">fa-heartbeat</code>
                            <code class="me-2">fa-chart-line</code>
                            <code class="me-2">fa-book</code>
                            <code class="me-2">fa-bullhorn</code>
                            <code>fa-coffee</code>
                        </span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" style="background: #1e847f; color: #ecc19c;">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function resetForm() {
        document.getElementById('categoryId').value = '';
        document.getElementById('categoryName').value = '';
        document.getElementById('categoryIcon').value = '';
        document.getElementById('categoryDesc').value = '';
        updateIconPreview();
    }

    function updateIconPreview() {
        const icon = document.getElementById('categoryIcon').value;
        const preview = document.getElementById('iconPreview');
        if (icon) {
            preview.className = `fas ${icon}`;
        } else {
            preview.className = 'fas fa-tag';
        }
    }

    function editCategory(cat) {
        document.getElementById('categoryId').value = cat.id;
        document.getElementById('categoryName').value = cat.name;
        document.getElementById('categoryIcon').value = cat.icon || '';
        document.getElementById('categoryDesc').value = cat.description || '';
        updateIconPreview();
        new bootstrap.Modal(document.getElementById('categoryModal')).show();
    }

    document.getElementById('categoryIcon')?.addEventListener('input', updateIconPreview);
</script>

<?php
require_once '../includes/footer.php';
?>