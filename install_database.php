<?php
// Read database credentials from .env file
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $env_vars = parse_ini_file($env_file);
    $servername = $env_vars['DB_HOST'];
    $username = $env_vars['DB_USERNAME'];
    $password = $env_vars['DB_PASSWORD'];
    $dbname = $env_vars['DB_NAME'];
} else {
    die(".env file not found");
}

// Set up the database connection
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create the database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Create the table
$sql = "CREATE TABLE IF NOT EXISTS random_dates (
    dateString DATE UNIQUE,
    differenceDays INT,
    valid BOOLEAN
)";
if ($conn->query($sql) !== TRUE) {
    die("Error creating table: " . $conn->error);
}

// Close the connection
$conn->close();
echo "DB installed successfully"
?>