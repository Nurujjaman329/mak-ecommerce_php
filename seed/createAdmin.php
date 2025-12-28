<?php
// seed/createAdmin.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/Response.php';

$username = 'admin';
$password = 'admin123';

// check exists
$stmt = $pdo->prepare("SELECT * FROM admins WHERE username=?");
$stmt->execute([$username]);
if($stmt->fetch()) {
    echo "Admin already exists\n";
    exit;
}

// insert admin
$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
$stmt->execute([$username, $hash]);

echo "✅ Admin created with username: admin, password: admin123\n";
