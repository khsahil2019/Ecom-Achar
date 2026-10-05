# 🌶️ Achar Heritage – Authentic Handcrafted Pickle E-Commerce & Admin Console

An enterprise-grade, production-ready e-commerce platform and administrative operations system for an authentic Indian pickle (Achar) D2C brand. Built using **PHP 8+, MySQL 8+, HTML5, CSS3, JavaScript ES6+, Bootstrap 5, and AJAX** following the **BMAD (Business, Model, Architecture, Development)** methodology.

---

## 🌟 Key Features

### 🛒 1. Customer Storefront
* **Heritage Indian Food Branding**: Royal Forest Green (`#143D2B`), Spicy Mango Orange (`#D9531E`), and Turmeric Mustard (`#E69500`) visual palette.
* **Hero Experience**: Sun-curing tradition narrative, trust statistics, and featured seasonal specials.
* **Artisanal Catalog**: 6 distinct categories with rich imagery (Mango, Lemon & Citrus, Garlic & Ginger, Fiery Chilli, Mixed, Royal Combos).
* **Live AJAX Instant Search**: Real-time suggestion dropdown with images, weight categories, and prices.
* **Advanced Catalog Filtering & Sorting**: Filter by category, price ranges, best sellers, new arrivals, and sort by rating/popularity/price.
* **Product Details Page**:
  * Multi-weight variant selector (250g, 500g, 1kg) with dynamic price, MRP, and stock recalculation.
  * Image gallery previews.
  * Informational tabs: The Heritage Story, Ingredients, Grandma's Storage Tips, and Customer Reviews.
  * In-page verified diner review submission form.
* **Shopping Basket & Cart**:
  * Interactive quantity steppers with real-time AJAX recalculation.
  * Dynamic Free Shipping Progress Bar (free delivery above ₹499).
  * Promotional Coupon Voucher system (e.g., `WELCOME10`, `FESTIVE100`, `ACHARLOVE`).
* **Express Checkout Flow**:
  * Step 1: Customer details (auto-fills for logged-in users).
  * Step 2: Shipping destination with saved address selector & "Save address" checkbox.
  * Step 3: Order summary review.
  * Step 4: Payment selection (Cash on Delivery & Instant Online Payment Gateway structure).
* **Order Confirmation & Tracking**:
  * Unique Order Number generation (`ACH202610...`).
  * Visual 5-stage Order Timeline Stepper (`Placed -> Confirmed -> Packed -> Shipped -> Delivered`).
  * Printable invoice layout.
* **Customer Account Portal**:
  * Dashboard overview with KPI order cards (Total, In-Progress, Delivered, Cancelled).
  * Order history with live courier tracking links.
  * Customer Wishlist management with quick "Move to Cart".
  * Delivery Address Book management (Add, Edit, Delete, Default).
  * Profile & Password security updates.
* **Omnichannel WhatsApp Integration**: Floating WhatsApp support button with pre-filled customer service message.

---

### 🛡️ 2. Administrative Operations Console (`/admin`)
* **Secure Admin Authentication**: Session middleware with bcrypt encryption.
* **Operations Dashboard**:
  * Real-time KPI statistics (Total Revenue ₹, Orders count, Pending Courier Dispatches, Low Inventory Alert).
  * Interactive Chart.js graphs: Monthly Sales Trend Line Chart & Order Status Distribution Doughnut Chart.
  * Recent orders table & quick low stock warning table.
* **Product Management**:
  * Full CRUD (Create, Read, Update, Delete) with image file uploads (MIME checked).
  * Multi-weight variant creator (configure individual weights, prices, MRPs, and stock per variant).
  * Bestseller, New Arrival, and Homepage Featured toggles.
* **Inventory Control**:
  * Dedicated stock adjustment view with low-stock warnings (<20 units) to prevent overselling.
* **Category Management**:
  * Create, edit, and delete categories with custom banners and display ordering.
* **Order Fulfillment**:
  * Lifecycle state machine: `Pending -> Confirmed -> Processing -> Packed -> Shipped -> Out for Delivery -> Delivered -> Cancelled`.
  * Assign Courier Tracking IDs (Delhivery, BlueDart, DTDC).
  * Payment status controller (`pending`, `paid`, `refunded`).
* **Customer Relationship Management (CRM)**:
  * Inspect customer profiles, registration dates, total orders, and lifetime customer spend.
  * Account status toggle (Active / Banned).
* **Coupon Engine**:
  * Create percentage and fixed discount vouchers with minimum order limits, maximum discount caps, usage limits, and date validity.
* **Review Moderation**:
  * Approve, reject, or delete customer reviews before they appear publicly.
* **Homepage Banner & Promotion Manager**:
  * Upload promotional hero banners and configure button links without editing code.
* **Customer Inquiry Ticketing**:
  * Manage contact inquiries with status tracking (`New`, `Read`, `Resolved`).
* **Website & Commercial Settings**:
  * Update business name, phone, email, WhatsApp, GST number, delivery fees, and free delivery thresholds.
  * Configure Razorpay payment gateway credentials securely.
* **Reports & Data Export**:
  * Filter sales performance by Today, Yesterday, This Week, This Month, or Custom Date Range.
  * One-click **CSV Spreadsheet Export**.

---

## 💻 Tech Stack & Architecture

* **Backend**: PHP 8.0+ (PDO, prepared statements, BCrypt password hashing, CSRF protection).
* **Database**: MySQL 8.0+ / MariaDB (fully compatible with XAMPP / cPanel / Docker). Includes smart zero-configuration SQLite fallback for immediate local testing.
* **Frontend**: HTML5, CSS3, JavaScript ES6+, Bootstrap 5.3, Bootstrap Icons, Chart.js.
* **Design Pattern**: Modular MVC-style architecture with clean separation of concerns, reusable layout partials, and JSON AJAX endpoints.

---

## 🚀 Quick Setup & Installation Guide

### Option 1: Instant Local Testing via PHP Built-in Server (Zero Configuration)
The project includes automated database initialization:
1. Open your terminal in the project directory:
   ```bash
   cd /Users/sahilkhan/FlutterDev/Ecom-Achar
   ```
2. Start the local server:
   ```bash
   php -S localhost:8000
   ```
3. Open your browser and navigate to:
   * **Customer Website**: `http://localhost:8000`
   * **Admin Console**: `http://localhost:8000/admin/login.php`

---

### Option 2: XAMPP / Apache + MySQL 8+ Setup

1. **Move Project**:
   Copy the `Ecom-Achar` folder into your XAMPP `htdocs` directory (e.g., `C:/xampp/htdocs/achar-store` or `/Applications/XAMPP/htdocs/achar-store`).
2. **Create MySQL Database**:
   * Open `http://localhost/phpmyadmin`.
   * Create a new database named `achar_db` with collation `utf8mb4_unicode_ci`.
3. **Import Database Schema**:
   * In phpMyAdmin, click on `achar_db`, go to the **Import** tab, and select `database.sql` from the project root.
   * Click **Go** to import all tables and seed data.
4. **Configure Database Credentials**:
   * Open `config/database.php` and verify or set your MySQL credentials:
     ```php
     define('DB_HOST', '127.0.0.1');
     define('DB_PORT', '3306');
     define('DB_NAME', 'achar_db');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```
5. **Set Uploads Folder Permissions**:
   Ensure `uploads/` and its subdirectories (`products/`, `categories/`, `banners/`) are writable by the web server (`chmod -R 775 uploads/`).
6. **Access Application**:
   * Customer Storefront: `http://localhost/achar-store/`
   * Admin Portal: `http://localhost/achar-store/admin/`

---

## 🔑 Default Credentials

| Portal | Role | Email | Password |
|---|---|---|---|
| **Admin Console** | Super Administrator | `admin@achar.com` | `Admin@123` |
| **Customer Storefront** | Verified Diner | `rahul@example.com` | `Customer@123` |

*Note: You can also register any new customer account directly from `/auth/register.php`.*

---

## 🏷️ Demo Active Coupons

* `WELCOME10` – 10% Discount on orders above ₹200 (Max discount: ₹100).
* `FESTIVE100` – Flat ₹100 Discount on orders above ₹499.
* `ACHARLOVE` – 15% Discount on orders above ₹599 (Max discount: ₹150).

---

## 🔒 Security Measures Implemented

1. **SQL Injection Immune**: 100% of database interactions use PDO prepared statements with parameter binding.
2. **Cross-Site Scripting (XSS) Prevention**: All user-provided and dynamic output is strictly escaped via `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
3. **Cross-Site Request Forgery (CSRF) Guard**: Secure cryptographic tokens verified on all state-changing POST and AJAX requests.
4. **Session Hardening**: `HttpOnly`, `SameSite=Lax` cookies prevent cookie theft via script injection.
5. **Secure Password Hashing**: Passwords stored exclusively as one-way cryptographic BCrypt hashes (`PASSWORD_BCRYPT`). Plain-text passwords are never stored.
6. **File Upload Verification**: Multi-layer validation verifying file size (max 5MB), file extension whitelisting (`jpg`, `png`, `webp`), and server-side MIME type detection (`finfo`).
7. **Directory Traversal Protection**: `.htaccess` rules block direct web access to `.sql`, `.sqlite`, and `.env` files.

---

## 📂 Project Directory Structure

```text
/Ecom-Achar
├── config/
│   ├── config.php                 # Global app constants, paths, and currency settings
│   └── database.php               # PDO database connection manager with MySQL & SQLite fallback
├── includes/
│   ├── header.php                 # Master HTML head, meta, SEO tags, Bootstrap 5 CSS
│   ├── navbar.php                 # Announcement bar, brand logo, AJAX live search, navigation
│   ├── footer.php                 # 4-column footer, newsletter, WhatsApp widget, Toast container
│   ├── functions.php              # Security, CSRF, flash messages, cart session, auth helpers
│   └── product_card.php           # Reusable product card component with badges & cart button
├── assets/
│   ├── css/
│   │   └── style.css              # Custom Indian pickle gourmet styling & responsive layouts
│   └── js/
│       └── main.js                # AJAX Add-to-Cart, Wishlist, Search suggestions, Quantity steppers
├── ajax/
│   ├── search.php                 # Live AJAX search endpoint
│   ├── cart.php                   # AJAX Cart add, update, remove
│   ├── wishlist.php               # AJAX Wishlist toggle
│   └── coupon.php                 # AJAX Coupon validator & discount calculation
├── auth/
│   ├── login.php                  # Customer login page with demo autofill
│   ├── register.php               # Customer registration page
│   └── logout.php                 # Customer session destroyer
├── account/
│   ├── account_nav.php            # Shared customer account navigation sidebar
│   ├── dashboard.php              # Order summary metrics & recent orders
│   ├── orders.php                 # Order history & visual tracking timeline
│   ├── wishlist.php               # Customer saved favorites
│   ├── addresses.php              # Delivery address book (Add, Delete, Default)
│   └── profile.php               # Edit contact profile & change password
├── checkout/
│   └── success.php                # Order confirmation & visual order journey stepper
├── uploads/
│   ├── products/                  # Product jar images
│   ├── categories/                # Category banner images
│   └── banners/                   # Promotional hero banners
├── admin/
│   ├── includes/
│   │   ├── header.php             # Admin topbar & navigation
│   │   ├── sidebar.php            # Admin sidebar menu
│   │   └── footer.php             # Admin footer & scripts
│   ├── products/
│   │   ├── index.php              # Products list with search & category filters
│   │   ├── add.php                # Add product with multi-weight variant builder
│   │   ├── edit.php               # Edit product & variants
│   │   ├── delete.php             # Delete product
│   │   └── inventory.php          # Quick stock quantity management
│   ├── categories/
│   │   └── index.php              # Category CRUD & ordering
│   ├── orders/
│   │   ├── index.php              # Orders list with status filters & bulk actions
│   │   ├── view.php               # Order inspector, lifecycle updater & tracking code
│   │   └── invoice.php            # Official printable GST Tax Invoice template
│   ├── customers/
│   │   └── index.php              # Customer directory & ban/activate toggles
│   ├── coupons/
│   │   └── index.php              # Discount coupon voucher manager
│   ├── reviews/
│   │   └── index.php              # Customer review moderation
│   ├── banners/
│   │   └── index.php              # Homepage promotional banners manager
│   ├── contact/
│   │   └── index.php              # Customer inquiries & ticketing
│   ├── reports/
│   │   └── index.php              # Analytics reports with CSV spreadsheet export
│   ├── settings.php               # Store settings, delivery fees & payment keys
│   ├── automation.php             # System Auto-Pilot Hub (1-click fulfill, restock, backup)
│   ├── cron.php                   # Unattended background cron worker (CLI & Webhook)
│   ├── dashboard.php              # Admin KPI dashboard with Auto-Pilot Command Bar
│   ├── login.php                  # Admin login portal
│   └── logout.php                 # Admin logout
├── database.sql                   # MySQL 8+ complete database schema and seed data
├── index.php                      # Homepage with Hero, Categories, Bestsellers, Reviews
├── shop.php                       # Product catalogue with filters & pagination
├── product.php                    # Product details page with variant selectors & reviews
├── cart.php                       # Shopping cart page
├── checkout.php                   # Multi-step checkout with COD & Online payment
├── about.php                      # Heritage brand story & grandmother's tradition
├── contact.php                    # Contact form & workshop locations
├── policies.php                   # Shipping, returns, privacy, and terms policies
├── robots.txt                     # SEO search engine crawl rules
├── sitemap.xml                    # XML sitemap for Google/Bing indexing
└── .htaccess                      # Apache security headers, Gzip, and rewrite rules
```

---

## 📜 License
Crafted with pride by the **Achar Heritage Culinary Engineering Team**. Free to deploy for commercial Indian artisanal food and pickle e-commerce ventures.
