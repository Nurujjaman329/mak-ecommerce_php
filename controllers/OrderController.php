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
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
        $stmt->execute([$p['productId']]);
        $product = $stmt->fetch();
        if(!$product) continue;

        $variants = json_decode($product['variants'], true);
        $variant = null;
        foreach($variants as $v) {
            if($v['weight'] == $p['weight']) $variant = $v;
        }

        $items[] = [
            'productId' => $product['id'],
            'name' => $product['name'],
            'weight' => $p['weight'],
            'price' => $variant['price'] ?? 0,
            'quantity' => $p['quantity'] ?? 1
        ];
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

    Response::success($order, 'Order created successfully', 201);
}

function getOrders() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
    $orders = $stmt->fetchAll();
    Response::success($orders);
}

function getOrderById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    if(!$order) Response::error('Order not found', 404);
    Response::success($order);
}
