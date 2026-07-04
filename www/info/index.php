<?php
// Sample project. Reachable on every version, e.g.:
//   http://info.72.test ... http://info.84.test
//   http://localhost:8082/info/
echo "<h1>PHP " . PHP_VERSION . "</h1>";
echo "<p>DB check: ";
try {
    $pdo = new PDO("mysql:host=mysql;dbname=app", "app", "app");
    echo "connected to MySQL " . $pdo->query('SELECT VERSION()')->fetchColumn();
} catch (Throwable $e) {
    echo "not connected — " . htmlspecialchars($e->getMessage());
}
echo "</p>";
phpinfo();
