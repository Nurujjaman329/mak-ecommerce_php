<?php
// controllers/OrderController.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/Response.php';

function createOrder($data) {
    global $pdo;

    $customer = $data['customer'] ?? null;
    $products = $data['products'] ?? null;
    $totalAmount = $data['totalAmount'] ?? 0;
    $paymentMethod = $data['paymentMethod'] ?? 'COD';

    if(!$products || count($products) === 0) Response::error('No products provided', 400);
    if(!$customer) Response::error('Customer info required', 400);

    $items = [];
    foreach($products as $p) {
        // Check if productId is a valid integer
        if (is_numeric($p['productId'])) {
            $productId = (int)$p['productId'];
        } else {
            // If it's not numeric (like a MongoDB ID), skip this product
            // In a real application, you might want to implement ID mapping
            error_log("Invalid product ID format: " . $p['productId']);
            continue;
        }

        $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if(!$product) {
            error_log("Product not found with ID: " . $productId);
            continue;
        }

        $variants = json_decode($product['variants'], true);
        $variant = null;
        if ($variants) {
            foreach($variants as $v) {
                if($v['weight'] == $p['weight']) {
                    $variant = $v;
                    break;
                }
            }
        }

        if (!$variant) {
            error_log("Variant not found for product ID: " . $productId . " with weight: " . $p['weight']);
            continue;
        }

        $items[] = [
            'productId' => $product['id'],
            'name' => $product['name'],
            'weight' => $p['weight'],
            'price' => $variant['price'] ?? 0,
            'quantity' => $p['quantity'] ?? 1
        ];
    }

    // If no items were added, it might be due to ID format mismatch
    if (empty($items)) {
        error_log("No items added to order. Product IDs sent: " . json_encode(array_column($products, 'productId')));
    }

    $stmt = $pdo->prepare("INSERT INTO orders (items, total_amount, customer_name, customer_phone, customer_address, payment_method, status, created_at) VALUES (?,?,?,?,?,?,?,NOW())");
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
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    // Decode the items JSON
    if (isset($order['items'])) {
        $decoded_items = json_decode($order['items'], true);
        $order['items'] = $decoded_items !== null ? $decoded_items : [];
    } else {
        $order['items'] = [];
    }

    Response::success($order, 'Order created successfully', 201);
}

function getOrders() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
    $orders = $stmt->fetchAll();

    // Decode the items JSON for each order
    foreach ($orders as &$order) {
        if (isset($order['items'])) {
            $decoded_items = json_decode($order['items'], true);
            $order['items'] = $decoded_items !== null ? $decoded_items : [];
        } else {
            $order['items'] = [];
        }
    }

    Response::success($orders);
}

function getOrderById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    if(!$order) Response::error('Order not found', 404);

    // Decode the items JSON
    if (isset($order['items'])) {
        $decoded_items = json_decode($order['items'], true);
        $order['items'] = $decoded_items !== null ? $decoded_items : [];
    } else {
        $order['items'] = [];
    }

    Response::success($order);
}
