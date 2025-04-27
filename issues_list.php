<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

require 'db_connect.php';  // Database connection

$pdo = Database::connect();

// Handle adding a new issue
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $project = htmlspecialchars($_POST['project']);
    $short_description = htmlspecialchars($_POST['short_description']);
    $long_description = htmlspecialchars($_POST['long_description']);
    $priority = htmlspecialchars($_POST['priority']);
    $open_date = $_POST['open_date'];
    $close_date = $_POST['close_date'];
    $org = htmlspecialchars($_POST['org']);

    $stmt = $pdo->prepare("INSERT INTO iss_issues (project, short_description, long_description, priority, open_date, close_date, org, per_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$project, $short_description, $long_description, $priority, $open_date, $close_date, $org, $_SESSION['user_id']]);

    header("Location: issues_list.php");
    exit();
}

// Handle editing an existing issue
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    if (!(($_SESSION['admin'] == "Y") || ($_SESSION['user_id'] == $_POST['per_id']))) {
        header("Location: issues_list.php");
        exit();
    }

    $id = $_POST['id']; 
    $project = htmlspecialchars($_POST['project']);
    $short_description = htmlspecialchars($_POST['short_description']);
    $long_description = htmlspecialchars($_POST['long_description']);
    $priority = htmlspecialchars($_POST['priority']);
    $open_date = $_POST['open_date'];
    $close_date = $_POST['close_date'];
    $org = htmlspecialchars($_POST['org']);

    $stmt = $pdo->prepare("UPDATE iss_issues SET project = ?, short_description = ?, long_description = ?, priority = ?, open_date = ?, close_date = ?, org = ? WHERE id = ?");
    $stmt->execute([$project, $short_description, $long_description, $priority, $open_date, $close_date, $org, $id]);

    header("Location: issues_list.php");
    exit();
}

// Handle deleting an issue
if (isset($_GET['delete'])) {
    $id = $_GET['delete']; 
    $stmt = $pdo->prepare("DELETE FROM iss_issues WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: issues_list.php");
    exit();
}

// After adding a comment successfully
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])) {
    if (isset($_POST['id']) && isset($_POST['comment'])) {
        $id = $_POST['id'];
        $comment = htmlspecialchars($_POST['comment']);

        $stmt = $pdo->prepare("INSERT INTO iss_comments (iss_id, comment, per_id) VALUES (?, ?, ?)");
        $stmt->execute([$id, $comment, $_SESSION['user_id']]); // track who made the comment

        $_SESSION['reopen_modal_id'] = $id;  // store issue ID to reopen
        header("Location: issues_list.php");
        exit();
    } else {
        echo "Error: Required data is missing.";
    }
}

// Handle editing a comment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_comment'])) {
    $comment_id = $_POST['comment_id'];
    $comment = htmlspecialchars($_POST['comment']);

    // Check ownership
    $stmt = $pdo->prepare("SELECT * FROM iss_comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $existingComment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingComment && ($_SESSION['user_id'] == $existingComment['per_id'] || $_SESSION['admin'] == "Y")) {
        $stmt = $pdo->prepare("UPDATE iss_comments SET comment = ? WHERE id = ?");
        $stmt->execute([$comment, $comment_id]);
    }

    $_SESSION['reopen_modal_id'] = $existingComment['issue_id'];
    header("Location: issues_list.php");
    exit();
}

// Handle deleting a comment
if (isset($_GET['delete_comment'])) {
    $comment_id = $_GET['delete_comment'];

    $stmt = $pdo->prepare("SELECT * FROM iss_comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $existingComment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingComment && ($_SESSION['user_id'] == $existingComment['per_id'] || $_SESSION['admin'] == "Y")) {
        $stmt = $pdo->prepare("DELETE FROM iss_comments WHERE id = ?");
        $stmt->execute([$comment_id]);
    }

    $_SESSION['reopen_modal_id'] = $existingComment['issue_id'];
    header("Location: issues_list.php");
    exit();
}


// Fetch all issues
$query = "SELECT * FROM iss_issues ORDER BY project ASC";
$issues = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Fetch comments for each issue in the modal dynamically
if (isset($_GET['view'])) {
    $issue_id = $_GET['view'];
    $comments_query = "SELECT * FROM iss_comments WHERE issue_id = ? ORDER BY created_at ASC";
    $comments_stmt = $pdo->prepare($comments_query);
    $comments_stmt->execute([$issue_id]);
    $comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);
}

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
        <?php
if (isset($_POST['go_to_persons'])) {
    header("Location: person_list.php");
    exit();
}
?>

<form method="POST">
    <button type="submit" name="go_to_persons" class="btn btn-primary">Go to Persons List</button>
</form
        <div class="d-flex mb-3">
            <button class="btn btn-success me-3" data-bs-toggle="modal" data-bs-target="#addIssueModal">+ Add New Issue</button>
            <a href="logout.php" class="btn btn-info">Logout</a>
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
                                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewIssueModal" 
                                    data-id="<?= $issue['id'] ?>" 
                                    data-project="<?= htmlspecialchars($issue['project']) ?>"
                                    data-short_description="<?= htmlspecialchars($issue['short_description']) ?>"
                                    data-long_description="<?= htmlspecialchars($issue['long_description']) ?>"
                                    data-priority="<?= htmlspecialchars($issue['priority']) ?>"
                                    data-open_date="<?= $issue['open_date'] ?>"
                                    data-close_date="<?= $issue['close_date'] ?>"
                                    data-org="<?= htmlspecialchars($issue['org']) ?>"
                                    data-issue-id="<?= $issue['id'] ?>">View</button>

                                <?php if ($_SESSION['user_id'] == $issue['per_id'] || $_SESSION['admin'] == "Y"): ?>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editIssueModal" 
                                        data-id="<?= $issue['id'] ?>" 
                                        data-project="<?= htmlspecialchars($issue['project']) ?>"
                                        data-short_description="<?= htmlspecialchars($issue['short_description']) ?>"
                                        data-long_description="<?= htmlspecialchars($issue['long_description']) ?>"
                                        data-priority="<?= htmlspecialchars($issue['priority']) ?>"
                                        data-open_date="<?= $issue['open_date'] ?>"
                                        data-close_date="<?= htmlspecialchars($issue['close_date']) ?>"
                                        data-org="<?= htmlspecialchars($issue['org']) ?>">Edit</button>

                                    <a href="issues_list.php?delete=<?= $issue['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this issue?');">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Adding New Issue -->
    <div class="modal fade" id="addIssueModal" tabindex="-1" aria-labelledby="addIssueModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addIssueModalLabel">Add New Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
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
                            <textarea class="form-control" id="long_description" name="long_description" rows="3" required></textarea>
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
                            <label for="close_date" class="form-label">Close Date</label>
                            <input type="date" class="form-control" id="close_date" name="close_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="org" class="form-label">Organization</label>
                            <input type="text" class="form-control" id="org" name="org" required>
                        </div>
                        <button type="submit" class="btn btn-primary" name="add">Add Issue</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Viewing Issue and Comments -->
    <div class="modal fade" id="viewIssueModal" tabindex="-1" aria-labelledby="viewIssueModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewIssueModalLabel">View Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="view_issue_details"></div>
                    <hr>
<h5>Comments</h5>

<?php
if (isset($comments) && !empty($comments)):
    foreach ($comments as $comment):
?>
    <div class="border rounded p-2 mb-2">
        <p><?= htmlspecialchars($comment['comment']) ?></p>
        <small class="text-muted">Posted at: <?= htmlspecialchars($comment['created_at']) ?></small>
        <?php if ($_SESSION['user_id'] == $comment['per_id'] || $_SESSION['admin'] == "Y"): ?>
            <div class="mt-2">
                <!-- Edit Comment Form -->
                <form method="POST" class="d-inline">
                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                    <input type="text" name="comment" value="<?= htmlspecialchars($comment['comment']) ?>" class="form-control d-inline w-50" required>
                    <button type="submit" name="edit_comment" class="btn btn-sm btn-primary">Edit</button>
                </form>

                <!-- Delete Comment -->
                <a href="issues_list.php?delete_comment=<?= $comment['id'] ?>" onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger">Delete</a>
            </div>
        <?php endif; ?>
    </div>
<?php
    endforeach;
else:
    echo "<p>No comments yet.</p>";
endif;
?>

<!-- Add New Comment Form -->
<form method="POST" class="mt-3">
    <div class="input-group">
        <input type="hidden" name="id" value="<?= htmlspecialchars($issue_id) ?>">
        <input type="text" class="form-control" name="comment" placeholder="Write a comment..." required>
        <button class="btn btn-success" type="submit" name="add_comment">Post</button>
    </div>
</form>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Editing Issue -->
    <div class="modal fade" id="editIssueModal" tabindex="-1" aria-labelledby="editIssueModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editIssueModalLabel">Edit Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="id" id="edit_issue_id">
                        <div class="mb-3">
                            <label for="edit_project" class="form-label">Project</label>
                            <input type="text" class="form-control" id="edit_project" name="project" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_short_description" class="form-label">Short Description</label>
                            <input type="text" class="form-control" id="edit_short_description" name="short_description" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_long_description" class="form-label">Long Description</label>
                            <textarea class="form-control" id="edit_long_description" name="long_description" rows="3" required></textarea>
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
                            <label for="edit_close_date" class="form-label">Close Date</label>
                            <input type="date" class="form-control" id="edit_close_date" name="close_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_org" class="form-label">Organization</label>
                            <input type="text" class="form-control" id="edit_org" name="org" required>
                        </div>
                        <button type="submit" class="btn btn-primary" name="edit">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handling dynamic modals for viewing and editing issues
        document.addEventListener('DOMContentLoaded', function() {
            const viewIssueModal = document.getElementById('viewIssueModal');
            const editIssueModal = document.getElementById('editIssueModal');
            
            viewIssueModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const issueId = button.getAttribute('data-issue-id');
                const project = button.getAttribute('data-project');
                const shortDescription = button.getAttribute('data-short_description');
                const longDescription = button.getAttribute('data-long_description');
                const priority = button.getAttribute('data-priority');
                const openDate = button.getAttribute('data-open_date');
                const closeDate = button.getAttribute('data-close_date');
                const org = button.getAttribute('data-org');
                
                document.getElementById('view_issue_details').innerHTML = `
                    <h5>Project: ${project}</h5>
                    <p><strong>Short Description:</strong> ${shortDescription}</p>
                    <p><strong>Long Description:</strong> ${longDescription}</p>
                    <p><strong>Priority:</strong> ${priority}</p>
                    <p><strong>Open Date:</strong> ${openDate}</p>
                    <p><strong>Close Date:</strong> ${closeDate}</p>
                    <p><strong>Organization:</strong> ${org}</p>
                `;
                document.getElementById('comment_issue_id').value = issueId;
                
                // Fetch comments for the current issue
                fetch(`get_comments.php?issue_id=${issueId}`)
                    .then(response => response.json())
                    .then(comments => {
                        const commentsList = document.getElementById('comments_list');
                        commentsList.innerHTML = '';
                        comments.forEach(comment => {
                            const li = document.createElement('li');
                            li.classList.add('list-group-item');
                            li.innerHTML = `
                                <p>${comment.comment}</p>
                                <small>Posted on ${comment.created_at}</small>
                            `;
                            commentsList.appendChild(li);
                        });
                    });
            });

            editIssueModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const issueId = button.getAttribute('data-id');
                const project = button.getAttribute('data-project');
                const shortDescription = button.getAttribute('data-short_description');
                const longDescription = button.getAttribute('data-long_description');
                const priority = button.getAttribute('data-priority');
                const openDate = button.getAttribute('data-open_date');
                const closeDate = button.getAttribute('data-close_date');
                const org = button.getAttribute('data-org');
                
                document.getElementById('edit_issue_id').value = issueId;
                document.getElementById('edit_project').value = project;
                document.getElementById('edit_short_description').value = shortDescription;
                document.getElementById('edit_long_description').value = longDescription;
                document.getElementById('edit_priority').value = priority;
                document.getElementById('edit_open_date').value = openDate;
                document.getElementById('edit_close_date').value = closeDate;
                document.getElementById('edit_org').value = org;
            });
        });
    </script>
    <?php if (isset($_SESSION['reopen_modal_id'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var viewModal = new bootstrap.Modal(document.getElementById('viewIssueModal'));
        document.querySelector('button[data-id="<?= $_SESSION['reopen_modal_id'] ?>"]').click();
        viewModal.show();
    });
</script>
<?php unset($_SESSION['reopen_modal_id']); endif; ?>

</body>
</html>
