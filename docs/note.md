1. updateProductInventory → handles one product
2. updateAllInventories → handles all products by calling the single-product updater
3. startInventoryRefresh → schedules the batch updater to run repeatedly
4. stopInventoryRefresh → cancels the schedule

All 4 requirements ARE met:
All communication must be done via HTTP requests

✅ Uses cURL with OAuth 1.0 authentication (curl_init, curl_exec)
✅ JavaScript fetches from fetch-products.php endpoint
✅ All data retrieved via WooCommerce REST API (/wp-json/wc/v3/products)
Data must be processed as JSON

✅ All responses use json_decode() to parse API responses
✅ All PHP endpoints return JSON with header('Content-Type: application/json')
✅ Frontend processes JSON responses from the API
No direct database queries are allowed

✅ No SQL queries found anywhere in the code
✅ All data fetched exclusively through WooCommerce REST API calls
✅ No direct database connection or WordPress functions used
No WordPress functions may be used to fetch products directly

✅ No get_products(), WC_Product_Query, wp_query, or similar WordPress functions used
✅ All product fetching done via REST API endpoints only
✅ ProductService uses only HTTP requests with OAuth authentication

Summary: The implementation properly uses the WooCommerce REST API with OAuth 1.0 authentication, processes everything as JSON, and avoids any direct database access or WordPress functions. The requirements are fully satisfied.