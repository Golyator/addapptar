<?php
class DateFetcher {
  private $url;
  private $reference_date;
  private $flux_valid_days;

  public function __construct($url, $reference_date, $flux_valid_days) {
    $this->url = $url;
    $this->reference_date = $reference_date;
    $this->flux_valid_days = $flux_valid_days;
  }

  public function fetchDates($data_size) {
    $mh = curl_multi_init();
    $handles = [];

    // Add $data_size curl handles to the multi-curl handle
    for ($i = 0; $i < $data_size; $i++) {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_multi_add_handle($mh, $ch);
        $handles[] = $ch;
    }

    // Execute the multi-curl requests
    $running = null;
    do {
        curl_multi_exec($mh, $running);
    } while ($running > 0);

    // Get the content of each response and calculate the difference in days
    $dates = [];
    foreach ($handles as $ch) {
        $date_response = curl_multi_getcontent($ch);
        if ($date_response === false) {
            echo "Error: Failed to get content for handle " . $ch . "\n";
            continue;
        }
        $date = strtotime($date_response);
        if ($date === false) {
            echo $date_response . " is not a valid date\n";
            continue;
        }  
        $diff_days = floor(($date - $this->reference_date) / 86400);
        $flux_valid = $diff_days < $this->flux_valid_days;
        $dates[] = [$date_response, $diff_days, $flux_valid];
        curl_multi_remove_handle($mh, $ch);
    }

    // Close the multi-curl handle
    curl_multi_close($mh);

    return $dates;
  }
}

// Load environment variables
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $env_vars = parse_ini_file($env_file);
    $dateFetcher = new DateFetcher(
        $env_vars['URL'],
        strtotime($env_vars['REFERENCE_DATE']),
        $env_vars['FLUX_VALID_DAYS']
    );
    $host = $env_vars['DB_HOST'];
    $username = $env_vars['DB_USERNAME'];
    $password = $env_vars['DB_PASSWORD'];
    $dbname = $env_vars['DB_NAME'];
} else {
    die(".env file not found");
}

// Fetch and process the dates
$dates = $dateFetcher->fetchDates($env_vars['DATA_SIZE']);

// create a PDO object to connect to the database
$dbh = new PDO('mysql:host=' . $host . ';dbname=' . $dbname, $username, $password);

// prepare the query with placeholders
$stmt = $dbh->prepare("INSERT INTO random_dates (dateString, differenceDays, valid) VALUES (?, ?, ?)");

// loop through the data array and execute the query for each row
foreach ($dates as $row) {
    $stmt->execute($row);
}

echo "added " . count($dates) . " elements to database";
?>