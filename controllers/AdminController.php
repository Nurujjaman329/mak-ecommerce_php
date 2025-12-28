<?php
// controllers/AdminController.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../config/upload.php';

// set JWT secret
JWT::setSecret(getenv('JWT_SECRET'));

function login($data)
{
    global $pdo;

    $username = $data['username'] ?? null;
    $password = $data['password'] ?? null;

    if (!$username || !$password) {
        Response::error('Username and password are required', 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username=?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        Response::error('Invalid username or password', 401);
    }

    $token = JWT::encode([
        'id'   => $admin['id'],
        'role' => 'admin',
    ]);

    Response::success(
        ['token' => $token],
        'Login successful'
    );
}

function createProduct($data, $files)
{
    global $pdo;

    $name        = $data['name'] ?? '';
    $description = $data['description'] ?? '';
    $variants    = $data['variants'] ?? null;

    if (!$files || empty($files['images']['name'][0])) {
        Response::error('At least one image is required', 400);
    }

    if (!$variants) {
        Response::error('Variants required', 400);
    }

    $uploaded = uploadFiles($files['images']);

    $images_json = json_encode(
        array_map(fn ($f) => "/uploads/$f", $uploaded)
    );

    $variants_json = json_encode(
        json_decode($variants, true)
    );

    $stmt = $pdo->prepare(
        "INSERT INTO products 
        (name, description, images, variants, created_at, updated_at)
        VALUES (?,?,?,?,NOW(),NOW())"
    );

    $stmt->execute([
        $name,
        $description,
        $images_json,
        $variants_json,
    ]);

    $id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    Response::success(
        $product,
        'Product created successfully',
        201
    );
}

function getOrders()
{
    global $pdo;

    $stmt = $pdo->query(
        "SELECT * FROM orders ORDER BY created_at DESC"
    );

    $orders = $stmt->fetchAll();

    Response::success($orders);
}

function getOrderById($id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if (!$order) {
        Response::error('Order not found', 404);
    }

    Response::success($order);
}
