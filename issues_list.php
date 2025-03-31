<?php
session_start();
require 'db_connect.php'; // Database connection

$pdo = Database::connect();

// Handle adding a new issue
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    // Retrieve form data
    $project = $_POST['project'];
    $short_description = $_POST['short_description'];
    $long_description = $_POST['long_description'];
    $priority = $_POST['priority'];
    $open_date = $_POST['open_date'];
    $close_date = $_POST['close_date'];
    $org = $_POST['org'];

    // Database insertion logic
    $stmt = $pdo->prepare("INSERT INTO iss_issues (project, short_description, long_description, priority, open_date, close_date, org) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$project, $short_description, $long_description, $priority, $open_date, $close_date, $org]);

    // Redirect back to the issues list
    header("Location: issues_list.php");
    exit();
}

// Handle editing an existing issue
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = $_POST['id']; // The issue ID to update
    $project = $_POST['project'];
    $short_description = $_POST['short_description'];
    $long_description = $_POST['long_description'];
    $priority = $_POST['priority'];
    $open_date = $_POST['open_date'];
    $close_date = $_POST['close_date'];
    $org = $_POST['org'];

    // Database update logic
    $stmt = $pdo->prepare("UPDATE iss_issues SET project = ?, short_description = ?, long_description = ?, priority = ?, open_date = ?, close_date = ?, org = ? WHERE id = ?");
    $stmt->execute([$project, $short_description, $long_description, $priority, $open_date, $close_date, $org, $id]);

    // Redirect back to the issues list
    header("Location: issues_list.php");
    exit();
}

// Handle deleting an issue
if (isset($_GET['delete'])) {
    $id = $_GET['delete']; // Get the ID of the issue to delete
    $stmt = $pdo->prepare("DELETE FROM iss_issues WHERE id = ?");
    $stmt->execute([$id]);

    // Redirect back to the issues list
    header("Location: issues_list.php");
    exit();
}

// Fetch all issues
$query = "SELECT * FROM iss_issues ORDER BY project ASC";
$issues = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issues List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://use.typekit.net/nsb3zrk.css">
    <style>
        table th {
            font-size: 20px;
            font-weight: bold;
        }

        table td {
            font-size: 16px;
        }
    </style>
</head>
<body class="bg-light">

    <div class="container mt-4">
        <h2 class="mb-3">Issues List</h2>

        <!-- Buttons for adding a new issue and viewing all comments -->
        <div class="d-flex mb-3">
    <button class="btn btn-success me-3" data-bs-toggle="modal" data-bs-target="#addIssueModal">+ Add New Issue</button>
    <a href="comments.php" class="btn btn-info" target="_blank">View All Comments</a>
</div>


        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Project</th>
                        <th>Short Description</th>
                        <th>Priority</th>
                        <th>Open Date</th>
                        <th>Close Date</th>
                        <th>Org</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($issues as $issue): ?>
                        <tr>
                            <td><?= htmlspecialchars($issue['id']) ?></td>
                            <td><?= htmlspecialchars($issue['project']) ?></td>
                            <td><?= htmlspecialchars($issue['short_description']) ?></td>
                            <td>
                                <span class="badge 
                                    <?= $issue['priority'] === 'High' ? 'bg-danger' : ($issue['priority'] === 'Medium' ? 'bg-warning text-dark' : 'bg-success') ?>">
                                    <?= htmlspecialchars($issue['priority']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($issue['open_date']) ?></td>
                            <td><?= htmlspecialchars($issue['close_date']) ?></td>
                            <td><?= htmlspecialchars($issue['org']) ?></td>
                            <td>
                                <!-- View Button -->
                                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewIssueModal" 
                                    data-id="<?= $issue['id'] ?>" 
                                    data-project="<?= htmlspecialchars($issue['project']) ?>"
                                    data-short_description="<?= htmlspecialchars($issue['short_description']) ?>"
                                    data-long_description="<?= htmlspecialchars($issue['long_description']) ?>"
                                    data-priority="<?= htmlspecialchars($issue['priority']) ?>"
                                    data-open_date="<?= $issue['open_date'] ?>"
                                    data-close_date="<?= $issue['close_date'] ?>"
                                    data-org="<?= htmlspecialchars($issue['org']) ?>">View</button>

                                <!-- Edit Button -->
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editIssueModal" 
                                    data-id="<?= $issue['id'] ?>" 
                                    data-project="<?= htmlspecialchars($issue['project']) ?>"
                                    data-short_description="<?= htmlspecialchars($issue['short_description']) ?>"
                                    data-long_description="<?= htmlspecialchars($issue['long_description']) ?>"
                                    data-priority="<?= htmlspecialchars($issue['priority']) ?>"
                                    data-open_date="<?= $issue['open_date'] ?>"
                                    data-close_date="<?= $issue['close_date'] ?>"
                                    data-org="<?= htmlspecialchars($issue['org']) ?>">Edit</button>

                                <!-- Delete Button -->
                                <a href="issues_list.php?delete=<?= $issue['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this issue?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for adding an issue -->
    <div class="modal fade" id="addIssueModal" tabindex="-1" aria-labelledby="addIssueModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addIssueModalLabel">Add New Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="issues_list.php">
                        <div class="mb-3">
                            <label for="project" class="form-label">Project</label>
                            <input type="text" class="form-control" id="project" name="project" required>
                        </div>
                        <div class="mb-3">
                            <label for="short_description" class="form-label">Short Description</label>
                            <input type="text" class="form-control" id="short_description" name="short_description" required>
                        </div>
                        <div class="mb-3">
                            <label for="long_description" class="form-label">Long Description</label>
                            <textarea class="form-control" id="long_description" name="long_description" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-control" id="priority" name="priority" required>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="open_date" class="form-label">Open Date</label>
                            <input type="date" class="form-control" id="open_date" name="open_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="close_date" class="form-label">Close Date</label>
                            <input type="date" class="form-control" id="close_date" name="close_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="org" class="form-label">Organization</label>
                            <input type="text" class="form-control" id="org" name="org" required>
                        </div>
                        <button type="submit" class="btn btn-success" name="add">Add Issue</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for editing an issue -->
    <div class="modal fade" id="editIssueModal" tabindex="-1" aria-labelledby="editIssueModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editIssueModalLabel">Edit Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="issues_list.php">
                        <div class="mb-3">
                            <label for="project" class="form-label">Project</label>
                            <input type="text" class="form-control" id="edit_project" name="project" required>
                        </div>
                        <div class="mb-3">
                            <label for="short_description" class="form-label">Short Description</label>
                            <input type="text" class="form-control" id="edit_short_description" name="short_description" required>
                        </div>
                        <div class="mb-3">
                            <label for="long_description" class="form-label">Long Description</label>
                            <textarea class="form-control" id="edit_long_description" name="long_description" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-control" id="edit_priority" name="priority" required>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="open_date" class="form-label">Open Date</label>
                            <input type="date" class="form-control" id="edit_open_date" name="open_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="close_date" class="form-label">Close Date</label>
                            <input type="date" class="form-control" id="edit_close_date" name="close_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="org" class="form-label">Organization</label>
                            <input type="text" class="form-control" id="edit_org" name="org" required>
                        </div>
                        <input type="hidden" name="id" id="edit_id">
                        <button type="submit" class="btn btn-primary" name="edit">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for viewing an issue -->
    <div class="modal fade" id="viewIssueModal" tabindex="-1" aria-labelledby="viewIssueModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewIssueModalLabel">View Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="row">
                        <dt class="col-sm-3">Project</dt>
                        <dd class="col-sm-9" id="view_project"></dd>

                        <dt class="col-sm-3">Short Description</dt>
                        <dd class="col-sm-9" id="view_short_description"></dd>

                        <dt class="col-sm-3">Long Description</dt>
                        <dd class="col-sm-9" id="view_long_description"></dd>

                        <dt class="col-sm-3">Priority</dt>
                        <dd class="col-sm-9" id="view_priority"></dd>

                        <dt class="col-sm-3">Open Date</dt>
                        <dd class="col-sm-9" id="view_open_date"></dd>

                        <dt class="col-sm-3">Close Date</dt>
                        <dd class="col-sm-9" id="view_close_date"></dd>

                        <dt class="col-sm-3">Organization</dt>
                        <dd class="col-sm-9" id="view_org"></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Populate the view modal with issue details
        document.querySelectorAll('[data-bs-target="#viewIssueModal"]').forEach(button => {
            button.addEventListener('click', function () {
                document.getElementById('view_project').textContent = this.dataset.project;
                document.getElementById('view_short_description').textContent = this.dataset.short_description;
                document.getElementById('view_long_description').textContent = this.dataset.long_description;
                document.getElementById('view_priority').textContent = this.dataset.priority;
                document.getElementById('view_open_date').textContent = this.dataset.open_date;
                document.getElementById('view_close_date').textContent = this.dataset.close_date;
                document.getElementById('view_org').textContent = this.dataset.org;
            });
        });
    </script>

</body>
</html>
