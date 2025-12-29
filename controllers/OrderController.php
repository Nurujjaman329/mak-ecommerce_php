<?php
// controllers/OrderController.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/Response.php';

/**
 * ✅ SINGLE SOURCE OF TRUTH
 * Always returns items as ARRAY
 */
function decodeItems($items) {
    if (is_array($items)) {
        return $items;
    }

    if (!is_string($items)) {
        return [];
    }

    $decoded = json_decode($items, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }

    return [];
}

function createOrder($data) {
    global $pdo;

    $customer = $data['customer'] ?? null;
    $products = $data['products'] ?? null;
    $totalAmount = $data['totalAmount'] ?? 0;
    $paymentMethod = $data['paymentMethod'] ?? 'COD';

    if (!$products || count($products) === 0) {
        Response::error('No products provided', 400);
    }

    if (!$customer) {
        Response::error('Customer info required', 400);
    }

    $items = [];

    foreach ($products as $p) {

        if (!isset($p['productId']) || !is_numeric($p['productId'])) {
            error_log("Invalid product ID format");
            continue;
        }

        $productId = (int)$p['productId'];

        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            error_log("Product not found with ID: " . $productId);
            continue;
        }

        $variants = json_decode($product['variants'], true);
        $variant = null;

        if (is_array($variants)) {
            foreach ($variants as $v) {
                if ($v['weight'] == $p['weight']) {
                    $variant = $v;
                    break;
                }
            }
        }

        if (!$variant) {
            error_log("Variant not found for product ID {$productId} ({$p['weight']})");
            continue;
        }

        $items[] = [
            'productId' => $product['id'],
            'name'      => $product['name'],
            'weight'    => $p['weight'],
            'price'     => $variant['price'] ?? 0,
            'quantity'  => $p['quantity'] ?? 1
        ];
    }

    if (empty($items)) {
        error_log("No valid items added to order");
    }

    $stmt = $pdo->prepare("
        INSERT INTO orders 
        (items, total_amount, customer_name, customer_phone, customer_address, payment_method, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        json_encode($items),
        $totalAmount,
        $customer['name'] ?? '',
        $customer['phone'] ?? '',
        $customer['address'] ?? '',
        $paymentMethod,
        'pending'
    ]);

    $id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    // ✅ FIX: always array
    $order['items'] = decodeItems($order['items'] ?? null);

    Response::success($order, 'Order created successfully', 201);
}

function getOrders() {
    global $pdo;

    $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
    $orders = $stmt->fetchAll();

    foreach ($orders as &$order) {
        // ✅ FIX: always array
        $order['items'] = decodeItems($order['items'] ?? null);
    }

    Response::success($orders);
}

function getOrderById($id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if (!$order) {
        Response::error('Order not found', 404);
    }

    // ✅ FIX: always array
    $order['items'] = decodeItems($order['items'] ?? null);

    Response::success($order);
}
