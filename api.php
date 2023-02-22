<?php
// Set headers to allow cross-origin resource sharing (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Read database credentials from .env file
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $env_vars = parse_ini_file($env_file);
    $host = $env_vars['DB_HOST'];
    $username = $env_vars['DB_USERNAME'];
    $password = $env_vars['DB_PASSWORD'];
    $dbname = $env_vars['DB_NAME'];
} else {
    die(".env file not found");
}

// Create connection using PDO
$dsn = "mysql:host=" . $host . ";dbname=" . $dbname . ";charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Check for query parameters
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$pageSize = isset($_GET['pageSize']) ? $_GET['pageSize'] : 10;
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';

// Calculate offset and limit
$offset = ($page - 1) * $pageSize;
$limit = $pageSize;

// Build SQL query
$sql = "SELECT * FROM random_dates";

// Add sorting to the query
if ($sort) {
    $sql .= " ORDER BY dateString ";
    $sql .= (strtolower($sort) == 'desc') ? 'DESC' : 'ASC';
}

// Add pagination to the query
$sql .= " LIMIT :limit OFFSET :offset";

// Prepare the SQL statement
$stmt = $pdo->prepare($sql);

// Bind parameters
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

// Execute the SQL statement
$stmt->execute();

// Fetch the data
$data = $stmt->fetchAll();

// Return the data in JSON format
echo json_encode($data);

// Close the database connection
$pdo = null;
?>