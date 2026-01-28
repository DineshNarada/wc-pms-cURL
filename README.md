# WooCommerce REST API Product Fetcher

A standalone product listing system that integrates with WooCommerce using the REST API to fetch and display product data with real-time inventory updates.

## 🎯 Features

- **Secure OAuth 1.0 Authentication**: Uses WooCommerce API keys with HMAC-SHA1 signature generation
- **Dynamic Product Listing**: Fetches and displays products from WooCommerce without WordPress dependencies
- **Pagination Support**: Navigate through products with customizable per-page limits (1-100)
- **Real-time Inventory Updates**: Auto-refresh inventory status every 90 seconds without page reload
- **Product Details**: Displays product name, price, sale price, stock status, and featured images
- **Image Lightbox**: Click to view product images in a modal dialog
- **Error Handling**: Graceful error messages for API failures and network issues
- **XSS Protection**: HTML escaping and sanitization throughout the application
- **Responsive Design**: Mobile-friendly product grid layout

## 📋 Requirements

- PHP 7.4+
- cURL extension enabled
- WooCommerce store with REST API enabled
- WooCommerce API credentials (Consumer Key & Consumer Secret)

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd pms-curl
```

### 2. Install Dependencies (if using Composer)
```bash
composer install
```

### 3. Configure Environment Variables
Create a `.env` file in the project root:
```env
WC_API_URL=https://your-woocommerce-store.com
WC_CONSUMER_KEY=your_consumer_key_here
WC_CONSUMER_SECRET=your_consumer_secret_here
```

### 4. Set Permissions
Ensure the project directory has proper read permissions:
```bash
chmod -R 755 .
```

### 5. Access the Application
Navigate to your web server:
```
http://localhost/pms-curl/frontend/products.html
```

## 🔐 Security Configuration

### Environment Variables
**Never commit `.env` file to version control**. The `.gitignore` file already excludes it.

Ensure your WooCommerce API credentials are:
- ✅ Stored in `.env` file (not hardcoded)
- ✅ Not exposed in frontend JavaScript
- ✅ Generated with appropriate permissions (read-only recommended)
- ✅ Regenerated if compromised

### API Keys Setup (WooCommerce)

1. Go to **WooCommerce → Settings → Advanced → REST API**
2. Click **Create an API key**
3. Give it a descriptive name (e.g., "Product Listing App")
4. Set permissions to **Read** (if only fetching products)
5. Copy the **Consumer Key** and **Consumer Secret** to `.env`

## 📁 Project Structure

```
pms-curl/
├── api/
│   ├── fetch-products.php           # Main product listing endpoint
│   ├── fetch-inventory-fragment.php # Single product inventory endpoint
│   └── ProductService.php           # Core service class (OAuth & API)
├── config/
│   └── config.php                   # Environment configuration loader
├── frontend/
│   └── products.html                # Product listing page
├── assets/
│   ├── css/
│   │   ├── index.css               # Main styles
│   │   └── products.css            # Product grid styles
│   └── js/
│       └── product-list.js         # Frontend logic & pagination
├── docs/
│   └── assessment-4.md             # Assignment requirements
├── .env                            # Environment variables (NOT in git)
├── .gitignore                      # Git ignore rules
└── README.md                       # This file
```

## 🔌 API Endpoints

### Get Products List
**Endpoint**: `api/fetch-products.php`  
**Method**: GET  
**Parameters**:
- `page` (int, default: 1) - Page number for pagination
- `per_page` (int, default: 12, max: 100) - Products per page

**Example Request**:
```bash
curl "http://localhost/pms-curl/api/fetch-products.php?page=1&per_page=12"
```

**Response**:
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 1,
        "name": "Product Name",
        "price": "29.99",
        "regular_price": "39.99",
        "sale_price": "29.99",
        "featured_image": "https://example.com/image.jpg",
        "stock_status": "instock",
        "stock_quantity": 50
      }
    ],
    "pagination": {
      "currentPage": 1,
      "perPage": 12,
      "total": 125,
      "totalPages": 11
    }
  }
}
```

### Get Single Product Inventory
**Endpoint**: `api/fetch-inventory-fragment.php`  
**Method**: GET  
**Parameters**:
- `product_id` (int, required) - Product ID

**Example Request**:
```bash
curl "http://localhost/pms-curl/api/fetch-inventory-fragment.php?product_id=1"
```

**Response**:
```json
{
  "success": true,
  "data": {
    "product_id": 1,
    "stock": {
      "quantity": 50,
      "status": "instock",
      "text": "✓ In Stock (50 items)"
    },
    "html": "<span class=\"product-stock stock-instock\">✓ In Stock (50 items)</span>"
  }
}
```

## 🖥️ Frontend Usage

### Opening the Application
Simply open `frontend/products.html` in your browser. The page will:
1. Load the first 12 products from your WooCommerce store
2. Display them in a responsive grid layout
3. Auto-refresh inventory every 90 seconds
4. Allow pagination through all products

### JavaScript API

#### Load Products
```javascript
loadProducts(pageNumber);  // Load specific page
```

#### Update Inventory
```javascript
updateAllInventories();    // Update all visible products
startInventoryRefresh(intervalMs);  // Start auto-refresh
stopInventoryRefresh();    // Stop auto-refresh
```

#### Image Lightbox
```javascript
openLightbox(imageUrl, altText);   // Open image in modal
closeLightbox();                   // Close modal
```

## 🐛 Troubleshooting

### "Configuration file not found"
- Ensure `config/config.php` exists
- Check file permissions

### "API credentials not configured"
- Verify `.env` file exists in project root
- Check environment variable names: `WC_API_URL`, `WC_CONSUMER_KEY`, `WC_CONSUMER_SECRET`

### "HTTP 401 - Unauthorized"
- Verify API credentials in `.env` are correct
- Check WooCommerce REST API is enabled
- Regenerate API keys in WooCommerce dashboard

### "HTTP 404 - Not Found"
- Verify `WC_API_URL` points to correct WooCommerce store
- Ensure REST API endpoints are accessible

### "No products displayed"
- Check browser console for error messages
- Verify WooCommerce store has published products
- Check network tab in browser developer tools for API response

## 📊 Performance

- **Pagination**: Default 12 products per page, configurable up to 100
- **Caching**: Utilizes WooCommerce pagination headers for accurate counts
- **Auto-Refresh**: Configurable inventory refresh interval (default: 90 seconds)
- **Lazy Loading**: Images load on demand with fallback placeholder

## 🔒 Security Best Practices

- ✅ API keys stored in environment variables (`.env`)
- ✅ OAuth 1.0 authentication with cryptographic signatures
- ✅ XSS protection via HTML escaping
- ✅ Input validation and sanitization
- ✅ No WordPress functions or database queries
- ✅ Secure error messages (no sensitive data leakage)
- ✅ Proper HTTP status codes
- ✅ Output buffering to prevent response corruption

## 📝 License

This project is provided as-is for educational purposes.

## 👨‍💻 Author

Created as part of WooCommerce REST API integration assessment.

## 📞 Support

For issues or questions:
1. Check the Troubleshooting section above
2. Review browser console for error messages
3. Check server error logs
4. Verify API credentials and WooCommerce configuration

---

**Last Updated**: January 28, 2026
# wc-pms-cURL
