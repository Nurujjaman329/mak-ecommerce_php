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



// CREATE CATEGORY
function createCategory($data) {
    global $pdo;
    $name = $data['name'] ?? null;
    $description = $data['description'] ?? null;

    if (!$name) Response::error('Category name is required', 400);

    $stmt = $pdo->prepare("INSERT INTO categories (name, description, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$name, $description]);

    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();

    Response::success($category, 'Category created successfully', 201);
}

// CREATE SUBCATEGORY
function createSubcategory($data) {
    global $pdo;
    $name = $data['name'] ?? null;
    $category_id = $data['category_id'] ?? null;
    $description = $data['description'] ?? null;

    if (!$name || !$category_id) Response::error('Subcategory name and category_id are required', 400);

    // ✅ Validate that category exists
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE id=?");
    $stmt->execute([$category_id]);
    if (!$stmt->fetch()) Response::error("Category not found", 400);

    $stmt = $pdo->prepare("INSERT INTO subcategories (name, category_id, description, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$name, $category_id, $description]);

    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM subcategories WHERE id=?");
    $stmt->execute([$id]);
    $subcategory = $stmt->fetch();

    Response::success($subcategory, 'Subcategory created successfully', 201);
}


// GET ALL CATEGORIES
function getCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
    Response::success($categories);
}

// GET ALL SUBCATEGORIES (optional category filter)
function getSubcategories($category_id = null) {
    global $pdo;
    $query = "SELECT s.*, c.name as category_name 
              FROM subcategories s 
              LEFT JOIN categories c ON s.category_id = c.id";
    $params = [];
    if ($category_id) {
        $query .= " WHERE s.category_id=?";
        $params[] = $category_id;
    }
    $query .= " ORDER BY s.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $subcategories = $stmt->fetchAll();
    Response::success($subcategories);
}


