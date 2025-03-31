<?php
session_start();
require 'db_connect.php'; // Database connection

$pdo = Database::connect();

// Fetch all issues (no need for specific issue ID)
$stmt = $pdo->prepare("SELECT * FROM iss_issues");
$stmt->execute();
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle adding a new comment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])) {
    // Retrieve form data for the new comment
    $per_id = $_POST['per_id']; // Assuming this is the person ID (you can get this from the session or another method)
    $short_comment = $_POST['short_comment'];
    $long_comment = $_POST['long_comment'];
    $posted_date = $_POST['posted_date'];
    $issue_id = $_POST['iss_id']; // Ensure you get the issue_id from the form submission

    // Insert the new comment into the database
    $stmt = $pdo->prepare("INSERT INTO iss_comments (per_id, iss_id, short_comment, long_comment, posted_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$per_id, $issue_id, $short_comment, $long_comment, $posted_date]);

    // Reflect the comment change by updating the issue table (if needed)
    // For example, let's update the issue's status or last comment date
    $stmt = $pdo->prepare("UPDATE iss_issues SET status = 'In Progress', last_comment_date = ? WHERE id = ?");
    $stmt->execute([$posted_date, $issue_id]);

    // Redirect back to the comments page
    header("Location: comments.php");
    exit();
}

// Handle deleting a comment
if (isset($_GET['delete_comment_id'])) {
    $comment_id = $_GET['delete_comment_id']; // Get the comment ID to delete
    $stmt = $pdo->prepare("DELETE FROM iss_comments WHERE id = ?");
    $stmt->execute([$comment_id]);

    // Get the related issue ID
    $stmt = $pdo->prepare("SELECT iss_id FROM iss_comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $issue = $stmt->fetch(PDO::FETCH_ASSOC);
    $issue_id = $issue['iss_id'];

    // Reflect the comment change by updating the issue table (if needed)
    // For example, we could change the status or add another action
    $stmt = $pdo->prepare("UPDATE iss_issues SET status = 'Under Discussion' WHERE id = ?");
    $stmt->execute([$issue_id]);

    // Redirect back to the comments page
    header("Location: comments.php");
    exit();
}

// Handle editing an existing comment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_comment'])) {
    $comment_id = $_POST['comment_id']; // The comment ID to update
    $short_comment = $_POST['short_comment'];
    $long_comment = $_POST['long_comment'];

    // Update the comment in the database
    $stmt = $pdo->prepare("UPDATE iss_comments SET short_comment = ?, long_comment = ? WHERE id = ?");
    $stmt->execute([$short_comment, $long_comment, $comment_id]);

    // Get the related issue ID
    $stmt = $pdo->prepare("SELECT iss_id FROM iss_comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $issue = $stmt->fetch(PDO::FETCH_ASSOC);
    $issue_id = $issue['iss_id'];

    // Reflect the comment change by updating the issue table (if needed)
    // For example, update the status of the issue
    $stmt = $pdo->prepare("UPDATE iss_issues SET status = 'Under Discussion', last_comment_date = NOW() WHERE id = ?");
    $stmt->execute([$issue_id]);

    // Redirect back to the comments page
    header("Location: comments.php");
    exit();
}

Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments for All Issues</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://use.typekit.net/nsb3zrk.css">
</head>
<body class="bg-light">

<div class="container mt-4">
    <h2>Comments for All Issues</h2>

    <!-- Form to add a new comment -->
    <div class="mb-4">
        <h4>Add a New Comment</h4>
        <form method="POST" action="comments.php">
            <div class="mb-3">
                <label for="short_comment" class="form-label">Short Comment</label>
                <input type="text" class="form-control" id="short_comment" name="short_comment" required>
            </div>
            <div class="mb-3">
                <label for="long_comment" class="form-label">Long Comment</label>
                <textarea class="form-control" id="long_comment" name="long_comment" rows="4" required></textarea>
            </div>
            <div class="mb-3">
                <label for="posted_date" class="form-label">Posted Date</label>
                <input type="date" class="form-control" id="posted_date" name="posted_date" required>
            </div>
            <div class="mb-3">
                <label for="iss_id" class="form-label">Issue</label>
                <select name="iss_id" id="iss_id" class="form-control" required>
                    <?php foreach ($issues as $issue): ?>
                        <option value="<?= $issue['id'] ?>"><?= htmlspecialchars($issue['short_description']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Assuming per_id (person id) comes from the session -->
            <input type="hidden" name="per_id" value="1"> <!-- Example, replace with actual session value -->
            <button type="submit" class="btn btn-success" name="add_comment">Add Comment</button>
        </form>
    </div>

    <!-- Display comments for all issues -->
    <h4>Existing Comments</h4>
    <?php foreach ($issues as $issue): ?>
        <h5>Comments for Issue: <?= htmlspecialchars($issue['short_description']) ?></h5>
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Short Comment</th>
                    <th>Long Comment</th>
                    <th>Posted Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch comments for the current issue
                $stmt = $pdo->prepare("SELECT * FROM iss_comments WHERE iss_id = ?");
                $stmt->execute([$issue['id']]);
                $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <?php foreach ($comments as $comment): ?>
                    <tr>
                        <td><?= htmlspecialchars($comment['short_comment']) ?></td>
                        <td><?= htmlspecialchars($comment['long_comment']) ?></td>
                        <td><?= htmlspecialchars($comment['posted_date']) ?></td>
                        <td>
                            <a href="comments.php?delete_comment_id=<?= $comment['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this comment?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
