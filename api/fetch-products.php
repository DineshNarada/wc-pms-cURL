<?php

// Set strict error handling - no output before JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set JSON header first
header('Content-Type: application/json; charset=utf-8');

// Capture any output/errors to prevent HTML in JSON response
ob_start();

// Load configuration
$config_path = __DIR__ . '/../config/config.php';
if (!file_exists($config_path)) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Configuration file not found: ' . $config_path,
    ]);
    exit;
}
require $config_path;

// Load ProductService
require __DIR__ . '/ProductService.php';

use ProductAPI\ProductService;

try {
    // Validate credentials
    if (empty($WC_API_URL) || empty($WC_CONSUMER_KEY) || empty($WC_CONSUMER_SECRET)) {
        throw new Exception('API credentials not configured. Please check your .env file.');
    }

    // Get parameters
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? max(1, min(100, (int) $_GET['per_page'])) : 12;

    // Create service and fetch products
    $productService = new ProductService($WC_API_URL, $WC_CONSUMER_KEY, $WC_CONSUMER_SECRET, $perPage);
    $result = $productService->getProducts($page, $perPage);

    // Clear any buffered output
    ob_end_clean();

    // Return appropriate HTTP status code
    if ($result['success']) {
        http_response_code(200);
    } else {
        // 401 for auth issues, 400 for bad requests, 500 for server errors
        if (strpos($result['error'], '401') !== false || strpos($result['error'], 'cannot list') !== false) {
            http_response_code(401);
        } else if (strpos($result['error'], 'credentials') !== false || strpos($result['error'], 'configured') !== false) {
            http_response_code(500);
        } else {
            http_response_code(400);
        }
    }
    echo json_encode($result);

} catch (Throwable $e) {
    // Clear any buffered output
    ob_end_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
