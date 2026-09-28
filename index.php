<?php
require "db.php";

$result = $conn->query("SELECT * FROM tasks ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tasks</title>
</head>

<body>

<h1>My Tasks</h1>

<a href="create.php">+ Add Task</a>

<?php while ($row = $result->fetch_assoc()): ?>

<div>

<h3><?php echo htmlspecialchars($row['title']); ?></h3>

<p><?php echo htmlspecialchars($row['description']); ?></p>

<span><?php echo $row['status']; ?></span>

<br>

<a href="edit.php?id=<?php echo $row['id']; ?>">Edit</a>

<a href="delete.php?id=<?php echo $row['id']; ?>" 
onclick="return confirm('Delete this task?');">
Delete
</a>

</div>

<hr>

<?php endwhile; ?>

</body>
</html>

<?php $conn->close(); ?>