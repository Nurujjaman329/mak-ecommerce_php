<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middlewares/AdminAuth.php';

/** Admin: Create Subcategory */
function createSubcategory($data) {
    $admin = AdminAuth();
    global $pdo;

    $category_id = $data['category_id'] ?? null;
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? null;

    if (!$name || !$category_id) Response::error('Subcategory name and category_id required', 400);

    $stmt = $pdo->prepare("INSERT INTO subcategories (category_id, name, description, created_at) VALUES (?,?,?,NOW())");
    $stmt->execute([$category_id, $name, $description]);

    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM subcategories WHERE id=?");
    $stmt->execute([$id]);
    $subcategory = $stmt->fetch();

    Response::success($subcategory, 'Subcategory created successfully', 201);
}

/** Admin: Get All Subcategories (optional category filter) */
function getSubcategories($category_id = null) {
    global $pdo;

    if ($category_id) {
        $stmt = $pdo->prepare("
            SELECT s.*, c.name AS category_name 
            FROM subcategories s 
            JOIN categories c ON s.category_id = c.id
            WHERE s.category_id = ?
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([(int)$category_id]);
    } else {
        $stmt = $pdo->query("
            SELECT s.*, c.name AS category_name 
            FROM subcategories s 
            JOIN categories c ON s.category_id = c.id
            ORDER BY s.created_at DESC
        ");
    }

    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    Response::success($subcategories);
}



/** Admin: Update Subcategory */
function updateSubcategory($id, $data) {
    $admin = AdminAuth();
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM subcategories WHERE id=?");
    $stmt->execute([$id]);
    $subcategory = $stmt->fetch();
    if (!$subcategory) Response::error('Subcategory not found', 404);

    $name = $data['name'] ?? $subcategory['name'];
    $description = $data['description'] ?? $subcategory['description'];
    $category_id = $data['category_id'] ?? $subcategory['category_id'];

    $stmt = $pdo->prepare("UPDATE subcategories SET name=?, description=?, category_id=? WHERE id=?");
    $stmt->execute([$name, $description, $category_id, $id]);

    $stmt = $pdo->prepare("SELECT * FROM subcategories WHERE id=?");
    $stmt->execute([$id]);
    $updated = $stmt->fetch();

    Response::success($updated, 'Subcategory updated successfully');
}

/** Admin: Delete Subcategory */
function deleteSubcategory($id) {
    $admin = AdminAuth();
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM subcategories WHERE id=?");
    $stmt->execute([$id]);
    $subcategory = $stmt->fetch();
    if (!$subcategory) Response::error('Subcategory not found', 404);

    $stmt = $pdo->prepare("DELETE FROM subcategories WHERE id=?");
    $stmt->execute([$id]);

    Response::success(null, 'Subcategory deleted successfully');
}
