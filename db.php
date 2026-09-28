<?php

$host = "localhost";

$db_user = "YOUR_DATABASE_USER";
$db_pass = "YOUR_DATABASE_PASSWORD";
$db_name = "YOUR_DATABASE_NAME";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
