<?php
function require_auth() {
    $dbFile = __DIR__ . '/database.sqlite';
    $db = new PDO('sqlite:' . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ensure table exists
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE,
        password TEXT
    )");

    // Insert default admin if none exists
    $count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count == 0) {
        $username = 'admin';
        $password = password_hash('admin', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $password]);
        error_log("SQLite initialized with default credentials: admin / admin");
    }

    // Basic auth prompt
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="Upload Area"');
        header('HTTP/1.0 401 Unauthorized');
        echo "Authentication required.";
        exit;
    }

    // Verify credentials
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($_SERVER['PHP_AUTH_PW'], $user['password'])) {
        header('WWW-Authenticate: Basic realm="Upload Area"');
        header('HTTP/1.0 401 Unauthorized');
        echo "Invalid credentials.";
        exit;
    }
}
