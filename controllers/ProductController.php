<?php
// controllers/ProductController.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../config/upload.php';
require_once __DIR__ . '/helpers.php';

if (!function_exists('getFullImageUrl')) {
    function getFullImageUrl($relativePath) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . '://' . $host;

        if (substr($relativePath, 0, 1) !== '/') {
            $relativePath = '/' . $relativePath;
        }

        return $baseUrl . $relativePath;
    }
}


/**
 * CREATE PRODUCT
 */
if (!function_exists('createProduct')) {
    function createProduct($data, $files) {
        global $pdo;

    $name             = $data['name'] ?? '';
    $description      = $data['description'] ?? '';
    $long_description = $data['long_description'] ?? null;
    $variants         = $data['variants'] ?? null;

    if (!$files || empty($files['images']['name'][0])) {
        Response::error('At least one image is required', 400);
    }

    if (!$variants) {
        Response::error('Variants required', 400);
    }

    // Decode variants JSON
    $variantsArray = json_decode($variants, true);

    $category_id    = $data['category_id'] ?? null;
    $subcategory_id = $data['subcategory_id'] ?? null;


    if ($category_id) {
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE id=?");
    $stmt->execute([$category_id]);
    if (!$stmt->fetch()) Response::error("Category not found", 400);
}

if ($subcategory_id) {
    $stmt = $pdo->prepare("SELECT id, category_id FROM subcategories WHERE id=?");
    $stmt->execute([$subcategory_id]);
    $sub = $stmt->fetch();
    if (!$sub) Response::error("Subcategory not found", 400);
    if ($category_id && $sub['category_id'] != $category_id) Response::error("Subcategory does not belong to selected category", 400);
}



    if (!is_array($variantsArray) || count($variantsArray) === 0) {
        Response::error('Variants must be a non-empty array', 400);
    }

    // Set main product price from first variant
    $price = $variantsArray[0]['price'] ?? 0;

    // Encode variants for DB
    $variants_json = json_encode($variantsArray);

    // Upload images
    $uploaded = uploadFiles($files['images']);
    $images_json = json_encode(array_map(fn($f) => "/uploads/$f", $uploaded));

    // Insert into DB
$stmt = $pdo->prepare(
    "INSERT INTO products 
    (name, description, long_description, price, images, variants, category_id, subcategory_id, created_at, updated_at)
    VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())"
);


$stmt->execute([
    $name,
    $description,
    $long_description,
    $price,
    $images_json,
    $variants_json,
    $category_id,
    $subcategory_id
]);


    // Fetch inserted product
    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if ($product) {
        // Decode JSON fields
        $product['images']   = json_decode($product['images'], true) ?? [];
        $product['variants'] = json_decode($product['variants'], true) ?? [];
        $product['long_description'] = $product['long_description'] ?? null;

        // Convert relative paths to full URLs
        $product['images'] = array_map(fn($img) => getFullImageUrl($img), $product['images']);
    }

    Response::success($product, 'Product created successfully', 201);
}
}



if (!function_exists('updateProduct')) {
    function updateProduct($id, $data, $files) {
        global $pdo;

    // Fetch existing product
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) Response::error('Product not found', 404);

    // Update fields if provided
    $name = $data['name'] ?? $product['name'];
    $description = $data['description'] ?? $product['description'];
    $long_description = $data['long_description'] ?? $product['long_description'];
    $variants = $data['variants'] ?? $product['variants'];
    $variantsArray = json_decode($variants, true) ?: [];
    $category_id    = $data['category_id'] ?? $product['category_id'];
$subcategory_id = $data['subcategory_id'] ?? $product['subcategory_id'];


    $price = $variantsArray[0]['price'] ?? $product['price'];
    $variants_json = json_encode($variantsArray);

    if ($category_id) {
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE id=?");
    $stmt->execute([$category_id]);
    if (!$stmt->fetch()) Response::error("Category not found", 400);
}

if ($subcategory_id) {
    $stmt = $pdo->prepare("SELECT id, category_id FROM subcategories WHERE id=?");
    $stmt->execute([$subcategory_id]);
    $sub = $stmt->fetch();
    if (!$sub) Response::error("Subcategory not found", 400);
    if ($category_id && $sub['category_id'] != $category_id) Response::error("Subcategory does not belong to selected category", 400);
}


    // Update images if new files uploaded
    $images_json = $product['images'];
    if($files && !empty($files['images']['name'][0])) {
        $uploaded = uploadFiles($files['images']);
        $images_json = json_encode(array_map(fn($f) => "/uploads/$f", $uploaded));
    }

    // Update DB
$stmt = $pdo->prepare(
    "UPDATE products SET 
        name=?, description=?, long_description=?, price=?, images=?, variants=?, category_id=?, subcategory_id=?, updated_at=NOW()
     WHERE id=?"
);
$stmt->execute([$name, $description, $long_description, $price, $images_json, $variants_json, $category_id, $subcategory_id, $id]);

    // Return updated product
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$id]);
    $updatedProduct = $stmt->fetch();

    $updatedProduct['images'] = json_decode($updatedProduct['images'], true) ?: [];
    $updatedProduct['variants'] = json_decode($updatedProduct['variants'], true) ?: [];
    $updatedProduct['long_description'] = $updatedProduct['long_description'] ?? null;
    $updatedProduct['images'] = array_map(fn($img) => getFullImageUrl($img), $updatedProduct['images']);

    Response::success($updatedProduct, 'Product updated successfully');
}
}



if (!function_exists('deleteProduct')) {
    function deleteProduct($id) {
        global $pdo;

    // Check if product exists
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) Response::error('Product not found', 404);

    // Optionally delete uploaded images here

    // Delete from DB
    $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
    $stmt->execute([$id]);

    Response::success(null, 'Product deleted successfully');
}
}



/**
 * GET ALL PRODUCTS
 */
if (!function_exists('getProducts')) {
    function getProducts() {
        global $pdo;

   $query  = "SELECT * FROM products WHERE 1=1";
$params = [];

if(isset($_GET['category_id'])) {
    $query .= " AND category_id=?";
    $params[] = $_GET['category_id'];
}

if(isset($_GET['subcategory_id'])) {
    $query .= " AND subcategory_id=?";
    $params[] = $_GET['subcategory_id'];
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);

$products = $stmt->fetchAll();


    foreach ($products as &$product) {
        $product['images']   = json_decode($product['images'], true) ?? [];
        $product['variants'] = json_decode($product['variants'], true) ?? [];
        $product['long_description'] = $product['long_description'] ?? null;

        $product['images'] = array_map(fn($img) => getFullImageUrl($img), $product['images']);
    }

    Response::success($products, 'Success');
}
}

/**
 * GET PRODUCT BY ID
 */
if (!function_exists('getProductById')) {
    function getProductById($id) {
        global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) Response::error('Product not found', 404);

    $product['images']   = json_decode($product['images'], true) ?? [];
    $product['variants'] = json_decode($product['variants'], true) ?? [];
    $product['long_description'] = $product['long_description'] ?? null;

    $product['images'] = array_map(fn($img) => getFullImageUrl($img), $product['images']);

    Response::success($product, 'Success');
}
}
