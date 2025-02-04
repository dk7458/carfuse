<?php
echo "<pre>";
echo "Checking last 20 Apache/Nginx error logs...\n\n";

// Read logs (Modify path based on your server setup)
$logPaths = [
    "/var/log/apache2/error.log",
    "/var/log/httpd/error_log",
    "/var/log/nginx/error.log",
];

foreach ($logPaths as $logPath) {
    if (file_exists($logPath)) {
        echo "Reading log file: $logPath\n";
        echo shell_exec("tail -n 20 " . escapeshellarg($logPath));
    } else {
        echo "Log file not found: $logPath\n";
    }
}

echo "</pre>";
?>
