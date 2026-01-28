<?php
// Diagnostic script to test WooCommerce API connectivity

require __DIR__ . '/config/config.php';

echo "=== WooCommerce API Diagnostic ===\n\n";

// Check 1: Credentials loaded
echo "1. Checking credentials...\n";
echo "   API URL: " . (empty($WC_API_URL) ? "❌ NOT SET" : "✓ " . $WC_API_URL) . "\n";
echo "   Consumer Key: " . (empty($WC_CONSUMER_KEY) ? "❌ NOT SET" : "✓ " . substr($WC_CONSUMER_KEY, 0, 10) . "...") . "\n";
echo "   Consumer Secret: " . (empty($WC_CONSUMER_SECRET) ? "❌ NOT SET" : "✓ " . substr($WC_CONSUMER_SECRET, 0, 10) . "...") . "\n\n";

if (empty($WC_API_URL) || empty($WC_CONSUMER_KEY) || empty($WC_CONSUMER_SECRET)) {
    die("❌ Missing credentials. Check your .env file.\n");
}

// Check 2: API endpoint accessibility
echo "2. Testing API endpoint...\n";
$api_url = rtrim($WC_API_URL, '/');
$endpoints = [
    'products' => '/wp-json/wc/v3/products?per_page=1',
    'system' => '/wp-json/wc/v3/system_status',
    'settings' => '/wp-json/wc/v3/settings'
];

foreach ($endpoints as $name => $endpoint) {
    $url = $api_url . $endpoint;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $WC_CONSUMER_KEY . ':' . $WC_CONSUMER_SECRET);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "   $name: HTTP $httpCode";
    if (!empty($curlError)) {
        echo " (cURL Error: $curlError)";
    } else if ($httpCode == 401) {
        echo " ❌ UNAUTHORIZED - Check API key permissions";
    } else if ($httpCode == 403) {
        echo " ❌ FORBIDDEN - Check user permissions";
    } else if ($httpCode == 200) {
        echo " ✓ OK";
    } else if ($httpCode == 404) {
        echo " ❌ NOT FOUND";
    }
    echo "\n";
}

echo "\n3. Testing with Authorization header...\n";
$url = $api_url . '/wp-json/wc/v3/products?per_page=1';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$auth = base64_encode($WC_CONSUMER_KEY . ':' . $WC_CONSUMER_SECRET);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $auth]);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP $httpCode";
if ($httpCode == 200) {
    echo " ✓ Header method works!\n";
} else {
    echo "\n";
}

echo "\n=== Recommendations ===\n";
echo "1. If all endpoints return 401, check WooCommerce API key permissions\n";
echo "2. Go to WooCommerce > Settings > Advanced > REST API\n";
echo "3. Verify the API key permissions are set to 'Read' or 'Read/Write'\n";
echo "4. If still failing, regenerate a NEW API key with full permissions\n";
?>
