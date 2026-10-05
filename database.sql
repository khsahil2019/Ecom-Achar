-- ==============================================================
-- Achar Heritage E-Commerce Database Schema (MySQL 8.0+)
-- ==============================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS banners;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS coupon_usage;
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS addresses;
DROP TABLE IF EXISTS wishlist_items;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS product_variants;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS admins;
SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- 1. Admins Table
-- -------------------------------------------------------------
CREATE TABLE admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'admin',
    avatar VARCHAR(255) NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2. Customers Table
-- -------------------------------------------------------------
CREATE TABLE customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mobile VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) NULL,
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    email_verified_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_email (email),
    INDEX idx_customer_mobile (mobile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3. Categories Table
-- -------------------------------------------------------------
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    icon VARCHAR(50) DEFAULT 'bi-basket',
    status ENUM('active', 'inactive') DEFAULT 'active',
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 4. Products Table
-- -------------------------------------------------------------
CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    sku VARCHAR(50) NOT NULL UNIQUE,
    short_description VARCHAR(500) NOT NULL,
    description TEXT NOT NULL,
    ingredients TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    mrp DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_percent INT DEFAULT 0,
    stock INT NOT NULL DEFAULT 50,
    weight VARCHAR(50) DEFAULT '500g',
    shelf_life VARCHAR(100) DEFAULT '12 Months',
    storage_instructions VARCHAR(255) DEFAULT 'Store in a cool, dry place. Always use a dry spoon.',
    main_image VARCHAR(255) NOT NULL,
    is_featured TINYINT(1) DEFAULT 0,
    is_bestseller TINYINT(1) DEFAULT 0,
    is_new TINYINT(1) DEFAULT 0,
    rating_cache DECIMAL(2,1) DEFAULT 5.0,
    reviews_count INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_product_slug (slug),
    INDEX idx_product_sku (sku),
    INDEX idx_product_category (category_id),
    INDEX idx_product_price (price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 5. Product Images Table
-- -------------------------------------------------------------
CREATE TABLE product_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 6. Product Variants Table (Weights e.g. 250g, 500g, 1kg)
-- -------------------------------------------------------------
CREATE TABLE product_variants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    weight_label VARCHAR(50) NOT NULL,
    sku_variant VARCHAR(60) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    mrp DECIMAL(10,2) NOT NULL,
    discount_percent INT DEFAULT 0,
    stock INT NOT NULL DEFAULT 20,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_variant_sku (sku_variant)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 7. Cart & Cart Items
-- -------------------------------------------------------------
CREATE TABLE cart (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NULL,
    session_id VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cart_customer (customer_id),
    INDEX idx_cart_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cart_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cart_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    variant_id INT UNSIGNED NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 8. Wishlist & Wishlist Items
-- -------------------------------------------------------------
CREATE TABLE wishlist (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    UNIQUE KEY uq_customer_wishlist (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wishlist_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wishlist_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wishlist_id) REFERENCES wishlist(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uq_wishlist_product (wishlist_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 9. Addresses Table
-- -------------------------------------------------------------
CREATE TABLE addresses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    house_flat VARCHAR(150) NOT NULL,
    street VARCHAR(200) NOT NULL,
    landmark VARCHAR(150) NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    type ENUM('home', 'work', 'other') DEFAULT 'home',
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 10. Orders Table
-- -------------------------------------------------------------
CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT UNSIGNED NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(150) NOT NULL,
    customer_mobile VARCHAR(20) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(100) NOT NULL,
    shipping_state VARCHAR(100) NOT NULL,
    shipping_pincode VARCHAR(10) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    coupon_code VARCHAR(50) NULL,
    shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('cod', 'online') NOT NULL DEFAULT 'cod',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    order_status ENUM('Pending', 'Confirmed', 'Processing', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled') NOT NULL DEFAULT 'Pending',
    tracking_number VARCHAR(100) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_order_status (order_status),
    INDEX idx_order_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 11. Order Items Table
-- -------------------------------------------------------------
CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    product_name VARCHAR(200) NOT NULL,
    variant_label VARCHAR(50) NOT NULL,
    sku VARCHAR(60) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 12. Payments Table
-- -------------------------------------------------------------
CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100) NULL,
    gateway_order_id VARCHAR(100) NULL,
    gateway_signature VARCHAR(255) NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'INR',
    status ENUM('initiated', 'captured', 'failed', 'refunded') NOT NULL DEFAULT 'initiated',
    raw_response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 13. Coupons Table & Coupon Usage
-- -------------------------------------------------------------
CREATE TABLE coupons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0.00,
    max_discount_amount DECIMAL(10,2) NULL,
    usage_limit INT DEFAULT 1000,
    used_count INT DEFAULT 0,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_coupon_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE coupon_usage (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NULL,
    order_id INT UNSIGNED NOT NULL,
    discount_applied DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 14. Reviews Table
-- -------------------------------------------------------------
CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(150) NULL,
    rating INT UNSIGNED NOT NULL DEFAULT 5,
    title VARCHAR(150) NULL,
    comment TEXT NOT NULL,
    is_verified_buyer TINYINT(1) DEFAULT 1,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_review_product (product_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 15. Banners Table
-- -------------------------------------------------------------
CREATE TABLE banners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    subtitle VARCHAR(255) NULL,
    badge_text VARCHAR(50) NULL,
    image VARCHAR(255) NOT NULL,
    button_text VARCHAR(50) DEFAULT 'Shop Now',
    button_url VARCHAR(255) DEFAULT '/shop',
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 16. Contact Messages Table
-- -------------------------------------------------------------
CREATE TABLE contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    mobile VARCHAR(20) NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'resolved') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 17. Settings Table (Key-Value Store)
-- -------------------------------------------------------------
CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    setting_group VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 18. Activity Logs & Notifications
-- -------------------------------------------------------------
CREATE TABLE activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_type VARCHAR(20) NOT NULL,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================
-- SEED DATA
-- =============================================================

-- Default Admin (Password: Admin@123)
INSERT INTO admins (name, email, password, role, status) VALUES
('Heritage Admin', 'admin@achar.com', '$2y$12$aZO9BDGJYdoRMsWNRBxGk.oMNtlOR.pyECO/R.59c0vRt8va0xNJK', 'super_admin', 'active');

-- Default Customer (Password: Customer@123)
INSERT INTO customers (name, email, mobile, password, status) VALUES
('Rahul Sharma', 'rahul@example.com', '9876543210', '$2y$12$fHWB.BgX/vvZVDONe4.gvOPo2geLKtyCm2lWWSANCEuZ6n5eyzafy', 'active');

-- Categories
INSERT INTO categories (id, name, slug, description, image, icon, display_order) VALUES
(1, 'Mango Pickles', 'mango-pickles', 'Handpicked raw Rajapuri & Ramkela mangoes sun-cured with cold-pressed mustard oil and aromatic spices.', 'cat-mango.jpg', 'bi-sun', 1),
(2, 'Lemon & Citrus', 'lemon-citrus', 'Juicy Kagzi lemons aged in earthen pots with rock salt, ajwain and warming spices.', 'cat-lemon.jpg', 'bi-brightness-high', 2),
(3, 'Garlic & Ginger', 'garlic-ginger', 'Hearty country garlic cloves and mountain ginger slow-cooked with fenugreek and mustard seeds.', 'cat-garlic.jpg', 'bi-shield-check', 3),
(4, 'Fiery Chilli', 'fiery-chilli', 'Traditional Banarasi stuffed red chillies and pungent green chillies for the ultimate spicy kick.', 'cat-chilli.jpg', 'bi-fire', 4),
(5, 'Punjabi Mixed Pickles', 'mixed-pickles', 'Seasonal winter vegetables like cauliflower, turnip, carrot & mango blended in rich mustard gravy.', 'cat-mixed.jpg', 'bi-palette', 5),
(6, 'Royal Heritage Combos', 'royal-combos', 'Artisanal gift sets and family combo jars bringing together our most-loved grandma recipes.', 'cat-combos.jpg', 'bi-gift', 6);

-- Products
INSERT INTO products (id, category_id, name, slug, sku, short_description, description, ingredients, price, mrp, discount_percent, stock, weight, shelf_life, storage_instructions, main_image, is_featured, is_bestseller, is_new, rating_cache, reviews_count) VALUES
(1, 1, 'Traditional Kacchi Kairi Mango Achar', 'traditional-kacchi-kairi-mango-achar', 'ACH-MNG-001', 'Signature raw mango pickle sun-dried and preserved in cold-pressed mustard oil and crushed spices.', 'Our Traditional Kacchi Kairi Mango Achar is the crown jewel of Indian household dining. Hand-chopped green mangoes are cured in natural rock salt, tossed with cracked mustard, roasted fenugreek (methi dana), nigella (kalonji), and steeped in pure kachi ghani mustard oil. Every spoonful bursts with tangy crunch, warmth, and nostalgia.', 'Raw Green Mangoes, Pure Cold-Pressed Mustard Oil, Fenugreek Seeds, Fennel (Saunf), Mustard Seeds (Rai), Kalonji, Turmeric, Red Chilli Powder, Rock Salt, Asafoetida (Hing).', 269.00, 299.00, 10, 85, '500g', '18 Months', 'Store in a cool dry place. Keep oil floating above achar. Always use a clean dry spoon.', 'prod-mango.jpg', 1, 1, 0, 4.9, 28),
(2, 2, 'Khatta Meetha Nimbu Lemon Achar', 'khatta-meetha-nimbu-lemon-achar', 'ACH-LMN-002', 'Oil-free aged Kagzi lemon pickle balanced with rock sugar, roasted cumin, and black salt.', 'A medicinal, sweet-and-sour culinary masterpiece perfected over decades. Fresh thin-skinned Kagzi lemons are steeped in their own juice with Ayurvedic rock salt, ajwain, roasted jeera, and unrefined cane sugar. It improves digestion, pairs gloriously with stuffed parathas, khichdi, and mathri.', 'Kagzi Lemons, Unrefined Rock Sugar (Khand), Black Salt, Sendha Namak, Ajwain (Carom Seeds), Roasted Cumin Powder, Degi Mirch, Garam Masala.', 249.00, 289.00, 14, 60, '500g', '24 Months', 'No oil needed. The lemons get darker and richer as they age. Use a dry wooden or stainless steel spoon.', 'prod-lemon.jpg', 1, 1, 0, 4.8, 19),
(3, 5, 'Punjabi Heritage Mixed Vegetable Achar', 'punjabi-heritage-mixed-vegetable-achar', 'ACH-MIX-003', 'Winter special blend of cauliflower florets, red carrots, tender turnips, and raw mango.', 'An authentic North Indian winter celebration. Fresh cauliflower, sweet red carrots, baby turnips, and crisp raw mango are sun-dried to eliminate moisture, then tossed in an aromatic paste of mustard paste, jaggery, ginger, and aromatic spices in golden mustard oil.', 'Cauliflower, Red Carrots, Shalgam (Turnip), Raw Mango, Mustard Oil, Ginger, Garlic, Jaggery (Gur), Mustard Paste, Kashmiri Red Chilli, Turmeric, Salt.', 279.00, 320.00, 13, 45, '500g', '12 Months', 'Keep jar tightly closed in a cool pantry. Avoid wet spoons.', 'prod-mixed.jpg', 1, 1, 0, 4.7, 14),
(4, 3, 'Rajasthani Lahsun (Garlic) Spicy Achar', 'rajasthani-lahsun-garlic-spicy-achar', 'ACH-GAR-004', 'Whole peeled country garlic cloves steeped in roasted spices and pungent mustard oil.', 'Specially crafted for garlic lovers and heart wellness. Small, pungent country garlic pearls are softened naturally in sun-warmed mustard oil enriched with crushed coriander seeds, amchur (dry mango powder), and red chilli flakes. Bold, savory, and immensely appetizing.', 'Peeled Desi Garlic Cloves, Pure Mustard Oil, Coriander Seeds, Mustard Seeds, Fennel, Red Chilli Flakes, Amchur, Turmeric, Salt, Asafoetida.', 299.00, 349.00, 14, 38, '500g', '12 Months', 'Store in glass container away from direct sunlight.', 'prod-garlic.jpg', 1, 0, 1, 4.9, 22),
(5, 4, 'Banarasi Stuffed Lal Mirch Achar', 'banarasi-stuffed-lal-mirch-achar', 'ACH-RED-005', 'Plump whole red chillies packed with fragrant sattu and roasted spice masala.', 'The pride of Varanasi. Large vibrant red chillies are carefully slit, deseeded, and filled with a secret coarse spice blend roasted to perfection, drizzled with raw mustard oil and allowed to sun-cure for 21 days on rooftop terraces.', 'Sun-dried Red Chillies, Mustard Oil, Coarse Mustard, Saunf, Amchur, Kalonji, Fenugreek, Ajwain, Black Salt, Pure Asafoetida.', 319.00, 369.00, 14, 40, '500g', '18 Months', 'Keep chilled after opening for longest freshness. Oil level must cover the chillies.', 'prod-chilli.jpg', 1, 1, 0, 5.0, 31),
(6, 3, 'Adrak-Hari Mirch Zing Achar', 'adrak-hari-mirch-zing-achar', 'ACH-ZNG-006', 'Crunchy mountain ginger juliennes and slit green chillies infused with lemon juice.', 'A zesty palate cleanser! Crisp slivers of young ginger and fresh green chillies tossed with yellow mustard seeds, turmeric, and fresh lime juice. Crisp, tingling, and completely oil-free with high digestive benefits.', 'Fresh Ginger Juliennes, Fresh Green Chillies, Lemon Juice, Yellow Mustard, Salt, Turmeric, Roasted Cumin.', 239.00, 269.00, 11, 55, '500g', '6 Months', 'Refrigerate after receiving for maximum crispness.', 'prod-ginger.jpg', 0, 0, 1, 4.6, 12),
(7, 1, 'Hing & Mango Digestif Special Achar', 'hing-mango-digestif-special-achar', 'ACH-HNG-007', 'Finely diced raw mangoes steeped with potent Hathras Hing and red spices.', 'An intensely aromatic pickle featuring superior grade Hathras Asafoetida (Hing). Diced green mango cubes absorb the bold sulfurous, savory pungency of pure hing, making it an irresistible companion for dal chawal, poori, and theplas.', 'Raw Mango Cubes, Pure Mustard Oil, Hathras Hing (Asafoetida), Mustard Seeds, Methi Dana, Kashmiri Mirch, Rock Salt, Turmeric.', 289.00, 329.00, 12, 35, '500g', '18 Months', 'Store dry, airtight, and away from direct moisture.', 'prod-hing.jpg', 0, 0, 1, 4.8, 8),
(8, 6, 'Shahi Dawat Combo Trio (Mango, Lemon, Garlic)', 'shahi-dawat-combo-trio', 'ACH-CMB-008', 'The ultimate culinary gift set featuring 3 bestselling 350g jars in a regal packaging.', 'Experience the royal trinity of Indian flavours. Contains 350g of Traditional Kacchi Kairi, 350g of Khatta Meetha Lemon, and 350g of Rajasthani Lahsun Achar packed in an eco-friendly jute and gold foiled gift box.', 'Assorted ingredients: Mango, Lemon, Garlic, Mustard Oil, Spices, Salt, Rock Sugar.', 749.00, 899.00, 17, 25, '1050g', '18 Months', 'Store in cool ambient environment. Individual jars have airtight inner lids.', 'prod-combo.jpg', 1, 1, 1, 5.0, 45);

-- Variants for Products (250g, 500g, 1kg)
INSERT INTO product_variants (product_id, weight_label, sku_variant, price, mrp, discount_percent, stock, is_default) VALUES
(1, '250g', 'ACH-MNG-001-250', 149.00, 169.00, 12, 40, 0),
(1, '500g', 'ACH-MNG-001-500', 269.00, 299.00, 10, 85, 1),
(1, '1kg',  'ACH-MNG-001-1KG', 499.00, 560.00, 11, 30, 0),

(2, '250g', 'ACH-LMN-002-250', 139.00, 159.00, 13, 30, 0),
(2, '500g', 'ACH-LMN-002-500', 249.00, 289.00, 14, 60, 1),
(2, '1kg',  'ACH-LMN-002-1KG', 469.00, 530.00, 12, 25, 0),

(3, '250g', 'ACH-MIX-003-250', 159.00, 179.00, 11, 20, 0),
(3, '500g', 'ACH-MIX-003-500', 279.00, 320.00, 13, 45, 1),
(3, '1kg',  'ACH-MIX-003-1KG', 519.00, 599.00, 13, 18, 0),

(4, '250g', 'ACH-GAR-004-250', 169.00, 199.00, 15, 25, 0),
(4, '500g', 'ACH-GAR-004-500', 299.00, 349.00, 14, 38, 1),
(4, '1kg',  'ACH-GAR-004-1KG', 559.00, 649.00, 14, 15, 0),

(5, '250g', 'ACH-RED-005-250', 179.00, 209.00, 14, 20, 0),
(5, '500g', 'ACH-RED-005-500', 319.00, 369.00, 14, 40, 1),
(5, '1kg',  'ACH-RED-005-1KG', 599.00, 699.00, 14, 15, 0),

(6, '250g', 'ACH-ZNG-006-250', 129.00, 149.00, 13, 30, 0),
(6, '500g', 'ACH-ZNG-006-500', 239.00, 269.00, 11, 55, 1),
(6, '1kg',  'ACH-ZNG-006-1KG', 439.00, 499.00, 12, 20, 0),

(7, '250g', 'ACH-HNG-007-250', 159.00, 179.00, 11, 20, 0),
(7, '500g', 'ACH-HNG-007-500', 289.00, 329.00, 12, 35, 1),
(7, '1kg',  'ACH-HNG-007-1KG', 529.00, 599.00, 12, 15, 0),

(8, 'Family Box (3x350g)', 'ACH-CMB-008-STD', 749.00, 899.00, 17, 25, 1);

-- Product Images Gallery
INSERT INTO product_images (product_id, image_path, sort_order) VALUES
(1, 'prod-mango.jpg', 1),
(1, 'prod-mango-detail1.jpg', 2),
(2, 'prod-lemon.jpg', 1),
(3, 'prod-mixed.jpg', 1),
(4, 'prod-garlic.jpg', 1),
(5, 'prod-chilli.jpg', 1),
(6, 'prod-ginger.jpg', 1),
(7, 'prod-hing.jpg', 1),
(8, 'prod-combo.jpg', 1);

-- Default Customer Address
INSERT INTO addresses (customer_id, full_name, mobile, house_flat, street, landmark, city, state, pincode, type, is_default) VALUES
(1, 'Rahul Sharma', '9876543210', 'Flat 402, Nilgiri Heights', 'MG Road, Sector 14', 'Near Central Park', 'Jaipur', 'Rajasthan', '302015', 'home', 1);

-- Demo Coupons
INSERT INTO coupons (code, type, value, min_order_amount, max_discount_amount, usage_limit, used_count, start_date, end_date, status) VALUES
('WELCOME10', 'percentage', 10.00, 200.00, 100.00, 500, 12, '2026-01-01', '2027-12-31', 'active'),
('FESTIVE100', 'fixed', 100.00, 499.00, 100.00, 250, 4, '2026-01-01', '2027-12-31', 'active'),
('ACHARLOVE', 'percentage', 15.00, 599.00, 150.00, 200, 18, '2026-01-01', '2027-12-31', 'active');

-- Demo Reviews
INSERT INTO reviews (product_id, customer_name, customer_email, rating, title, comment, is_verified_buyer, status) VALUES
(1, 'Meenakshi Sundaram', 'meenakshi@example.com', 5, 'Takes me back to my grandmother’s kitchen!', 'The mustard oil aroma hit me the second I opened the seal. Perfectly spiced and crunchy raw mango pieces. Truly unmatched quality.', 1, 'approved'),
(1, 'Vikram Oberoi', 'vikram@example.com', 5, 'Authentic mustard oil goodness', 'Tastes just like traditional UP style achar. Excellent packaging with zero leakage. Ordered 1kg jar again.', 1, 'approved'),
(2, 'Ananya Das', 'ananya@example.com', 5, 'Perfect sweet tangy balance', 'The aged lemon achar is so soothing on the stomach and delicious with theplas. Truly homemade feel.', 1, 'approved'),
(5, 'Siddharth Varma', 'sid@example.com', 5, 'Banarasi red chillies are unbelievable!', 'Crisp outer chilli, packed with roasted masala inside. Pure heaven with hot dal and rice.', 1, 'approved'),
(8, 'Priya Kulkarni', 'priya@example.com', 5, 'Gifted this to my parents', 'The royal gift packaging is stunning and all 3 flavors are extraordinary. Highly recommended!', 1, 'approved');

-- Banners
INSERT INTO banners (title, subtitle, badge_text, image, button_text, button_url, sort_order, status) VALUES
('Authentic Achar. Traditional Taste.', 'Handcrafted Indian pickles slow-cured under the Indian sun in cold-pressed mustard oil.', 'Fresh • Traditional • Pure', 'banner-hero.jpg', 'Shop Achar Now', '/shop.php', 1, 'active'),
('Royal Festive Pickles Collection', 'Get up to 20% OFF on all Heritage Combo Jars & Family Packs.', 'Special Limited Offer', 'banner-festive.jpg', 'Explore Combos', '/shop.php?category=royal-combos', 2, 'active');

-- Store Settings
INSERT INTO settings (setting_key, setting_value, setting_group) VALUES
('site_name', 'Achar Heritage', 'general'),
('site_tagline', 'Authentic Handcrafted Indian Pickles', 'general'),
('contact_email', 'care@acharheritage.com', 'contact'),
('contact_phone', '+91 98765 43210', 'contact'),
('whatsapp_number', '919876543210', 'contact'),
('business_address', '42, Heritage Spice Street, Old Mandi, Jaipur, Rajasthan 302001', 'contact'),
('gst_number', '08AAAAA0000A1Z5', 'business'),
('currency_symbol', '₹', 'ecommerce'),
('currency_code', 'INR', 'ecommerce'),
('delivery_fee', '49', 'ecommerce'),
('free_delivery_threshold', '499', 'ecommerce'),
('cod_enabled', '1', 'ecommerce'),
('online_payment_enabled', '1', 'ecommerce'),
('razorpay_key_id', 'rzp_test_placeholderKey123', 'payment'),
('razorpay_key_secret', 'rzp_test_placeholderSecret456', 'payment'),
('facebook_url', 'https://facebook.com/acharheritage', 'social'),
('instagram_url', 'https://instagram.com/acharheritage', 'social'),
('youtube_url', 'https://youtube.com/acharheritage', 'social'),
('footer_about', 'Achar Heritage brings back grandmother’s secret pickle recipes. Prepared in small artisanal batches with cold-pressed oils, rock salt, and sun-cured perfection.', 'general');

-- Demo Order
INSERT INTO orders (id, order_number, customer_id, customer_name, customer_email, customer_mobile, shipping_address, shipping_city, shipping_state, shipping_pincode, subtotal, discount_amount, coupon_code, shipping_fee, total_amount, payment_method, payment_status, order_status, tracking_number) VALUES
(1, 'ACH202610050001', 1, 'Rahul Sharma', 'rahul@example.com', '9876543210', 'Flat 402, Nilgiri Heights, MG Road, Sector 14, Near Central Park', 'Jaipur', 'Rajasthan', '302015', 538.00, 53.80, 'WELCOME10', 0.00, 484.20, 'cod', 'pending', 'Shipped', 'DELHIVERY-ACH-89210');

INSERT INTO order_items (order_id, product_id, product_name, variant_label, sku, unit_price, quantity, subtotal) VALUES
(1, 1, 'Traditional Kacchi Kairi Mango Achar', '500g', 'ACH-MNG-001-500', 269.00, 1, 269.00),
(1, 3, 'Punjabi Heritage Mixed Vegetable Achar', '500g', 'ACH-MIX-003-500', 279.00, 1, 279.00);
