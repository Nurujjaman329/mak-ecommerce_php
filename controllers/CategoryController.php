<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middlewares/AdminAuth.php';

/** Admin: Create Category */
function createCategory($data) {
    $admin = AdminAuth();
    global $pdo;

    $name = $data['name'] ?? '';
    $description = $data['description'] ?? null;

    if (!$name) Response::error('Category name is required', 400);

    $stmt = $pdo->prepare("INSERT INTO categories (name, description, created_at) VALUES (?,?,NOW())");
    $stmt->execute([$name, $description]);

    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();

    Response::success($category, 'Category created successfully', 201);
}

/** Admin: Get All Categories */
function getCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY created_at DESC");
    $categories = $stmt->fetchAll();
    Response::success($categories);
}

/** Admin: Update Category */
function updateCategory($id, $data) {
    $admin = AdminAuth();
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();
    if (!$category) Response::error('Category not found', 404);

    $name = $data['name'] ?? $category['name'];
    $description = $data['description'] ?? $category['description'];

    $stmt = $pdo->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
    $stmt->execute([$name, $description, $id]);

    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->execute([$id]);
    $updatedCategory = $stmt->fetch();

    Response::success($updatedCategory, 'Category updated successfully');
}

/** Admin: Delete Category */
function deleteCategory($id) {
    $admin = AdminAuth();
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();
    if (!$category) Response::error('Category not found', 404);

    $stmt = $pdo->prepare("DELETE FROM categories WHERE id=?");
    $stmt->execute([$id]);

    Response::success(null, 'Category deleted successfully');
}
