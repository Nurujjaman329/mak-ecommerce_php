<?php
// middlewares/AdminAuth.php
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';

function AdminAuth() {
    $headers = getallheaders();
    if(!isset($headers['Authorization'])) Response::error('Unauthorized', 401);

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $payload = JWT::decode($token);
    if(!$payload || $payload['role'] !== 'admin') Response::error('Forbidden: Admins only', 403);

    return $payload;
}
