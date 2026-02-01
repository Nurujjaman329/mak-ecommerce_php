<?php
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
