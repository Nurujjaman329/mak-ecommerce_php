<?php
// controllers/AdminController.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../config/upload.php';
require_once __DIR__ . '/helpers.php';

// set JWT secret
JWT::setSecret(getenv('JWT_SECRET'));

/**
 * Decode items JSON safely
 */
function decodeItems($items)
{
    if (is_array($items)) {
        return $items;
    }

    if (!is_string($items)) {
        return [];
    }

    $decoded = json_decode($items, true);

    return is_array($decoded) ? $decoded : [];
}

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

    Response::success(['token' => $token], 'Login successful');
}



/**
 * ✅ Get all orders with proper shape
 */
function getOrders()
{
    global $pdo;

    $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = [];

    foreach ($orders as $order) {
        $formatted[] = [
            "_id" => (string)$order['id'],
            "customer" => [
                "name" => $order['customer_name'] ?? '',
                "phone" => $order['customer_phone'] ?? '',
                "address" => $order['customer_address'] ?? '',
            ],
            "items" => decodeItems($order['items'] ?? null),
            "totalAmount" => (float)$order['total_amount'],
            "paymentMethod" => $order['payment_method'] ?? 'COD',
            "status" => $order['status'] ?? 'pending',
            "createdAt" => $order['created_at'] ?? '',
        ];
    }

    Response::success($formatted);
}

/**
 * ✅ Get single order by ID with proper shape
 */
function getOrderById($id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=?");
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        Response::error('Order not found', 404);
    }

    $formatted = [
        "_id" => (string)$order['id'],
        "customer" => [
            "name" => $order['customer_name'] ?? '',
            "phone" => $order['customer_phone'] ?? '',
            "address" => $order['customer_address'] ?? '',
        ],
        "items" => decodeItems($order['items'] ?? null),
        "totalAmount" => (float)$order['total_amount'],
        "paymentMethod" => $order['payment_method'] ?? 'COD',
        "status" => $order['status'] ?? 'pending',
        "createdAt" => $order['created_at'] ?? '',
    ];

    Response::success($formatted);
}
