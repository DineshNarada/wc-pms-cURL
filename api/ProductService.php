<?php

namespace ProductAPI;

use Exception;

class ProductService
{
    private $apiUrl;
    private $consumerKey;
    private $consumerSecret;
    private $perPage = 12;

    public function __construct($apiUrl, $consumerKey, $consumerSecret, $perPage = 12)
    {
        if (empty($apiUrl) || empty($consumerKey) || empty($consumerSecret)) {
            throw new Exception('WooCommerce API credentials are not configured properly.');
        }

        $this->apiUrl = rtrim($apiUrl, '/'); // ensure no trailing slash
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->perPage = $perPage;
    }

    public function getProducts($page = 1, $perPage = null)
    {
        try {
            if ($perPage === null) {
                $perPage = $this->perPage;
            }

            // Base URL with X-WC-Request-Context header to get pagination headers
            $url = $this->apiUrl . '/wp-json/wc/v3/products?page=' . $page . '&per_page=' . $perPage;

            $ch = curl_init();

            // Generate OAuth 1.0 Authorization header
            $authHeader = $this->generateOAuth1Header('GET', $url);
            $headers = ['Authorization: ' . $authHeader];

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HEADER, true); // Capture headers

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);

            if ($httpCode !== 200) {
                // Log request details for debugging
                error_log("OAuth 1.0 request failed:");
                error_log("Request URL: " . $url);
                error_log("HTTP Code: " . $httpCode);
                throw new Exception("API returned HTTP $httpCode");
            }

            // Extract headers and body
            $headerText = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);
            
            $products = json_decode($body, true);
            
            // Extract total count from X-WP-Total header
            $totalProducts = 0;
            if (preg_match('/X-WP-Total:\\s*(\\d+)/i', $headerText, $matches)) {
                $totalProducts = (int) $matches[1];
            }

            $totalPages = $totalProducts > 0 ? ceil($totalProducts / $perPage) : 1;

            return [
                'success' => true,
                'data' => [
                    'products' => $this->formatProducts($products),
                    'pagination' => [
                        'currentPage' => $page,
                        'perPage' => $perPage,
                        'total' => $totalProducts,
                        'totalPages' => $totalPages,
                    ]
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch products: ' . $e->getMessage(),
                'data' => [
                    'products' => [],
                    'pagination' => []
                ]
            ];
        }
    }

    /**
     * Fetch a single product by ID
     * @param int $productId
     * @return array
     */
    public function getProduct($productId)
    {
        try {
            if (empty($productId) || !is_numeric($productId)) {
                throw new Exception('Invalid product ID');
            }

            $url = $this->apiUrl . '/wp-json/wc/v3/products/' . intval($productId);

            $ch = curl_init();

            // Generate OAuth 1.0 Authorization header
            $authHeader = $this->generateOAuth1Header('GET', $url);
            $headers = ['Authorization: ' . $authHeader];

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception('Product not found (HTTP ' . $httpCode . ')');
            }

            $product = json_decode($response, true);

            if (!$product) {
                throw new Exception('Invalid product data');
            }

            return [
                'success' => true,
                'data' => [
                    'product' => $this->formatSingleProduct($product),
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch product: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    private function generateOAuth1Header($method, $url)
    {
        // OAuth 1.0 parameters
        $oauthNonce = $this->generateNonce();
        $oauthTimestamp = time();
        $oauthVersion = '1.0';
        $oauthSignatureMethod = 'HMAC-SHA1';

        // Parse URL to extract base URL and query parameters
        $parsedUrl = parse_url($url);
        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
        
        // Collect all parameters for signature
        $oauthParams = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => $oauthNonce,
            'oauth_signature_method' => $oauthSignatureMethod,
            'oauth_timestamp' => $oauthTimestamp,
            'oauth_version' => $oauthVersion,
        ];

        // Parse query parameters from URL
        $queryParams = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }

        // Merge all parameters for signature base string
        $signatureParams = array_merge($oauthParams, $queryParams);
        ksort($signatureParams);

        // Build signature base string
        $signatureBaseString = $method . '&' . 
                               rawurlencode($baseUrl) . '&' . 
                               rawurlencode($this->buildParameterString($signatureParams));

        // Generate signature
        $signingKey = rawurlencode($this->consumerSecret) . '&';
        $signature = base64_encode(hash_hmac('sha1', $signatureBaseString, $signingKey, true));
        
        // Add signature to OAuth parameters
        $oauthParams['oauth_signature'] = $signature;

        // Build Authorization header
        $authHeader = 'OAuth ' . $this->buildAuthorizationHeader($oauthParams);
        
        return $authHeader;
    }

    private function generateNonce()
    {
        return base64_encode(random_bytes(20));
    }

    private function buildParameterString(array $params)
    {
        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = rawurlencode($key) . '=' . rawurlencode($value);
        }
        return implode('&', $pairs);
    }

    private function buildAuthorizationHeader(array $oauthParams)
    {
        $pairs = [];
        foreach ($oauthParams as $key => $value) {
            $pairs[] = $key . '="' . rawurlencode($value) . '"';
        }
        return implode(', ', $pairs);
    }

    private function formatProducts(array $products)
    {
        return array_map(function ($product) {
            return [
                'id' => $product['id'] ?? null,
                'name' => $product['name'] ?? '',
                'price' => $product['price'] ?? '',
                'regular_price' => $product['regular_price'] ?? '',
                'sale_price' => $product['sale_price'] ?? null,
                'featured_image' => $this->getFeaturedImage($product),
                'stock_status' => $product['stock_status'] ?? 'instock',
                'stock_quantity' => $product['stock_quantity'] ?? 0,
            ];
        }, $products);
    }

    private function formatSingleProduct(array $product)
    {
        return [
            'id' => $product['id'] ?? null,
            'name' => $product['name'] ?? '',
            'price' => $product['price'] ?? '',
            'regular_price' => $product['regular_price'] ?? '',
            'sale_price' => $product['sale_price'] ?? null,
            'featured_image' => $this->getFeaturedImage($product),
            'stock_status' => $product['stock_status'] ?? 'instock',
            'stock_quantity' => $product['stock_quantity'] ?? 0,
        ];
    }

    private function getFeaturedImage(array $product)
    {
        return $product['images'][0]['src'] ?? '';
    }

    /**
     * Format stock information for display
     * @param array $product
     * @return array
     */
    public function formatStockInfo(array $product)
    {
        $stockQuantity = $product['stock_quantity'] ?? 0;
        $stockStatus = $product['stock_status'] ?? 'outofstock';
        $stockClass = $stockStatus === 'instock' ? 'stock-instock' : 'stock-outofstock';
        $stockText = $stockStatus === 'instock'
            ? sprintf('✓ In Stock (%d %s)', $stockQuantity, $stockQuantity === 1 ? 'item' : 'items')
            : '✗ Out of Stock';

        return [
            'quantity' => $stockQuantity,
            'status' => $stockStatus,
            'text' => $stockText,
            'class' => $stockClass,
            'html' => sprintf('<span class="product-stock %s">%s</span>', $stockClass, $stockText),
        ];
    }
}
