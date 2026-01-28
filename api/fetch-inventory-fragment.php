<?php

// Set strict error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Capture any output/errors
ob_start();

// Load configuration
$config_path = __DIR__ . '/../config/config.php';
if (!file_exists($config_path)) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Config not found']);
    exit;
}
require $config_path;

// Load ProductService
require __DIR__ . '/ProductService.php';

use ProductAPI\ProductService;

try {
    if (empty($WC_API_URL) || empty($WC_CONSUMER_KEY) || empty($WC_CONSUMER_SECRET)) {
        throw new Exception('API credentials not configured.');
    }

    // Get product ID from query parameter
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    
    if ($product_id === 0) {
        throw new Exception('Product ID is required');
    }

    // Create ProductService instance and fetch single product
    $productService = new ProductService($WC_API_URL, $WC_CONSUMER_KEY, $WC_CONSUMER_SECRET);
    $result = $productService->getProduct($product_id);

    if (!$result['success']) {
        throw new Exception($result['error']);
    }

    $product = $result['data']['product'];
    $stockInfo = $productService->formatStockInfo($product);

    ob_end_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'product_id' => $product_id,
            'stock' => [
                'quantity' => $stockInfo['quantity'],
                'status' => $stockInfo['status'],
                'text' => $stockInfo['text'],
            ],
            'html' => $stockInfo['html'],
        ]
    ]);

} catch (Exception $e) {
    ob_end_clean();
    
    // Return appropriate HTTP status code
    $errorMsg = $e->getMessage();
    if (strpos($errorMsg, '401') !== false || strpos($errorMsg, 'cannot') !== false) {
        http_response_code(401);
    } else if (strpos($errorMsg, 'not found') !== false) {
        http_response_code(404);
    } else {
        http_response_code(400);
    }
    
    echo json_encode(['success' => false, 'error' => $errorMsg]);
}
?>
