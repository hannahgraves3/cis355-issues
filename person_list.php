<?php
session_start();

// Check if the user is logged in, similar to how it's done in issues_list.php
if (!isset($_SESSION['user_id'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Include the database connection class (similar to how it's done in issues_list.php)
require 'db_connect.php'; 

// Connect to the database
$pdo = Database::connect();

// Handle adding a new person (similar to adding an issue)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create'])) {
    $first_name = htmlspecialchars($_POST['fname']);
    $last_name = htmlspecialchars($_POST['lname']);
    $email = htmlspecialchars($_POST['email']);

    $stmt = $pdo->prepare("INSERT INTO iss_persons (fname, lname, email) VALUES (?, ?, ?)");
    $stmt->execute([$first_name, $last_name, $email]);

    header("Location: person_list.php");
    exit();
}

// Handle editing an existing person (similar to editing an issue)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    if (!(($_SESSION['admin'] == "Y") || ($_SESSION['user_id'] == $_POST['per_id']))) {
        header("Location: person_list.php");
        exit();
    }

    $per_id = $_POST['per_id'];
    $first_name = htmlspecialchars($_POST['first_name']);
    $last_name = htmlspecialchars($_POST['last_name']);
    $email = htmlspecialchars($_POST['email']);

    $stmt = $pdo->prepare("UPDATE iss_persons SET fname = ?, lname = ?, email = ? WHERE per_id = ?");
    $stmt->execute([$first_name, $last_name, $email, $per_id]);

    header("Location: person_list.php");
    exit();
}

// Handle deleting a person (similar to deleting an issue)
if (isset($_GET['delete'])) {
    $per_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM iss_persons WHERE per_id = ?");
    $stmt->execute([$per_id]);

    header("Location: person_list.php");
    exit();
}

// Fetch all persons (similar to fetching issues)
$query = "SELECT * FROM iss_persons ORDER BY lname ASC";
$persons = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Close the database connection
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
    <title>Persons List</title>
    <style>
        table, th, td {
            border: 1px solid black;
            padding: 8px;
            border-collapse: collapse;
        }
        .form-section {
            margin-top: 20px;
        }
    </style>
</head>
<body>

<h1>Persons List</h1>

<table>
    <thead>
        <tr>
            <th>Per ID</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($persons as $person): ?>
            <tr>
                <td><?php echo htmlspecialchars($person['fname']); ?></td>
                <td><?php echo htmlspecialchars($person['lname']); ?></td>
                <td><?php echo htmlspecialchars($person['email']); ?></td>
                <td>
                    <!-- Update Form -->
                    <form style="display:inline-block;" method="POST">
                        <input type="text" name="fname" value="<?php echo htmlspecialchars($person['fname']); ?>" required>
                        <input type="text" name="lname" value="<?php echo htmlspecialchars($person['lname']); ?>" required>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($person['email']); ?>" required>
                        <button type="submit" name="update">Update</button>
                    </form>

                    <!-- Delete Form -->
                    <form style="display:inline-block;" method="POST">
                        <button type="submit" name="delete" onclick="return confirm('Are you sure you want to delete this person?');">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Create New Person -->
<div class="form-section">
    <h2>Add New Person</h2>
    <form method="POST">
        <input type="text" name="fname" placeholder="First Name" required>
        <input type="text" name="lname" placeholder="Last Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <button type="submit" name="create">Add Person</button>
    </form>
</div>

</body>
</html>
