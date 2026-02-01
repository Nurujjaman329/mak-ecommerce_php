<?php
require_once __DIR__ . '/../controllers/ProductController.php';
require_once __DIR__ . '/../middlewares/AdminAuth.php';
require_once __DIR__ . '/../utils/Response.php';

// AdminController.php


/**
 * Admin: CREATE PRODUCT
 */
function adminCreateProduct($data, $files) {
    $admin = AdminAuth(); // Validate admin token
    createProduct($data, $files);
}

/**
 * Admin: UPDATE PRODUCT
 */
function adminUpdateProduct($id, $data, $files) {
    $admin = AdminAuth();
    updateProduct($id, $data, $files);
}

/**
 * Admin: DELETE PRODUCT
 */
function adminDeleteProduct($id) {
    $admin = AdminAuth();
    deleteProduct($id);
}

/**
 * Admin: GET ALL PRODUCTS (optional)
 */
function adminGetProducts() {
    $admin = AdminAuth();
    getProducts();
}

/**
 * Admin: GET PRODUCT BY ID (optional)
 */
function adminGetProductById($id) {
    $admin = AdminAuth();
    getProductById($id);
}
