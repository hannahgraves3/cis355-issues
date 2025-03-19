<?php
session_start();
require 'db_connect.php'; // Database connection

$pdo = Database::connect();

// Ensure `iss_issues` table exists
$createTableQuery = "CREATE TABLE IF NOT EXISTS iss_issues (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    short_description VARCHAR(255) NOT NULL,
    long_description TEXT NOT NULL,
    open_date DATE NOT NULL,
    close_date DATE NOT NULL,
    priority VARCHAR(255) NOT NULL,
    org VARCHAR(255) NOT NULL,
    project VARCHAR(255) NOT NULL,
    per_id INT(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
$pdo->exec($createTableQuery);

// Handle form submission to add new issue
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    // Edit issue
    $id = $_POST['id'];
    $project = $_POST['project'];
    $short_description = $_POST['short_description'];
    $long_description = $_POST['long_description'];
    $priority = $_POST['priority'];
    $open_date = $_POST['open_date'];
    $close_date = $_POST['close_date'];
    $org = $_POST['org'];

    // Update the issue in the database
    $updateQuery = "UPDATE iss_issues SET project = ?, short_description = ?, long_description = ?, open_date = ?, close_date = ?, priority = ?, org = ? WHERE id = ?";
    $stmt = $pdo->prepare($updateQuery);
    $stmt->execute([$project, $short_description, $long_description, $open_date, $close_date, $priority, $org, $id]);

    // Redirect back to the same page to refresh the issue list
    header("Location: issues_list.php");
    exit();
}

// Handle deleting an issue
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Delete the issue from the database
    $deleteQuery = "DELETE FROM iss_issues WHERE id = ?";
    $stmt = $pdo->prepare($deleteQuery);
    $stmt->execute([$id]);

    // Redirect back to the same page to refresh the issue list
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
</head>
<body class="bg-light">

    <div class="container mt-4">
        <h2 class="mb-3">Issues List</h2>
        
        <!-- Button to trigger modal -->
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addIssueModal">+ Add New Issue</button>

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
                                <!-- Edit Button -->
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editIssueModal" data-id="<?= $issue['id'] ?>" data-project="<?= htmlspecialchars($issue['project']) ?>" data-short_description="<?= htmlspecialchars($issue['short_description']) ?>" data-long_description="<?= htmlspecialchars($issue['long_description']) ?>" data-priority="<?= htmlspecialchars($issue['priority']) ?>" data-open_date="<?= $issue['open_date'] ?>" data-close_date="<?= $issue['close_date'] ?>" data-org="<?= htmlspecialchars($issue['org']) ?>">Edit</button>

                                <!-- Delete Button -->
                                <a href="issues_list.php?delete=<?= $issue['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for adding a new issue -->
    <div class="modal fade" id="addIssueModal" tabindex="-1" aria-labelledby="addIssueModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addIssueModalLabel">Add New Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="issues_list.php" method="POST">
                        <div class="mb-3">
                            <label for="project" class="form-label">Project</label>
                            <input type="text" class="form-control" id="project" name="project" required>
                        </div>
                        <div class="mb-3">
                            <label for="short_description" class="form-label">Short Description</label>
                            <input type="text" class="form-control" id="short_description" name="short_description" required>
                        </div>
                        <div class="mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority" required>
                                <option value="High">High</option>
                                <option value="Medium">Medium</option>
                                <option value="Low">Low</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="open_date" class="form-label">Open Date</label>
                            <input type="date" class="form-control" id="open_date" name="open_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="long_description" class="form-label">Long Description</label>
                            <textarea class="form-control" id="long_description" name="long_description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="close_date" class="form-label">Close Date</label>
                            <input type="date" class="form-control" id="close_date" name="close_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="org" class="form-label">Organization</label>
                            <input type="text" class="form-control" id="org" name="org" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Issue</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for editing an existing issue -->
    <div class="modal fade" id="editIssueModal" tabindex="-1" aria-labelledby="editIssueModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editIssueModalLabel">Edit Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="issues_list.php" method="POST">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="mb-3">
                            <label for="edit_project" class="form-label">Project</label>
                            <input type="text" class="form-control" id="edit_project" name="project" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_short_description" class="form-label">Short Description</label>
                            <input type="text" class="form-control" id="edit_short_description" name="short_description" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_priority" class="form-label">Priority</label>
                            <select class="form-select" id="edit_priority" name="priority" required>
                                <option value="High">High</option>
                                <option value="Medium">Medium</option>
                                <option value="Low">Low</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_open_date" class="form-label">Open Date</label>
                            <input type="date" class="form-control" id="edit_open_date" name="open_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_long_description" class="form-label">Long Description</label>
                            <textarea class="form-control" id="edit_long_description" name="long_description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_close_date" class="form-label">Close Date</label>
                            <input type="date" class="form-control" id="edit_close_date" name="close_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_org" class="form-label">Organization</label>
                            <input type="text" class="form-control" id="edit_org" name="org" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Set the data for the edit modal
        const editModal = document.getElementById('editIssueModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Button that triggered the modal
            const id = button.getAttribute('data-id');
            const project = button.getAttribute('data-project');
            const shortDescription = button.getAttribute('data-short_description');
            const longDescription = button.getAttribute('data-long_description');
            const priority = button.getAttribute('data-priority');
            const openDate = button.getAttribute('data-open_date');
            const closeDate = button.getAttribute('data-close_date');
            const org = button.getAttribute('data-org');

            // Populate the modal form with the current issue data
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_project').value = project;
            document.getElementById('edit_short_description').value = shortDescription;
            document.getElementById('edit_long_description').value = longDescription;
            document.getElementById('edit_priority').value = priority;
            document.getElementById('edit_open_date').value = openDate;
            document.getElementById('edit_close_date').value = closeDate;
            document.getElementById('edit_org').value = org;
        });
    </script>

</body>
</html>
