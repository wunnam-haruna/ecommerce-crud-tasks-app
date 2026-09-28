<?php

require "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $status = $_POST["status"];

    $stmt = $conn->prepare(
        "INSERT INTO tasks (title, description, status) VALUES (?, ?, ?)"
    );

    $stmt->bind_param("sss", $title, $description, $status);

    $stmt->execute();

    $stmt->close();
    $conn->close();

    header("Location: index.php");
    exit;
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Add Task</title>
</head>

<body>

<h2>Add Task</h2>

<form method="POST" action="create.php">

<label>Title</label><br>
<input type="text" name="title" required><br>

<label>Description</label><br>
<textarea name="description"></textarea><br>

<label>Status</label><br>

<select name="status">
    <option value="pending">Pending</option>
    <option value="in_progress">In Progress</option>
    <option value="done">Done</option>
</select>

<br>

<button type="submit">Save Task</button>

</form>

<a href="index.php">Back</a>

</body>

</html>