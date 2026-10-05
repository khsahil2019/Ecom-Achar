-- SQLite Schema for Local Development Fallback

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

CREATE TABLE admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'admin',
    avatar TEXT,
    status TEXT DEFAULT 'active',
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    mobile TEXT NOT NULL,
    password TEXT NOT NULL,
    avatar TEXT,
    status TEXT DEFAULT 'active',
    email_verified_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT,
    image TEXT,
    icon TEXT DEFAULT 'bi-basket',
    status TEXT DEFAULT 'active',
    display_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    sku TEXT NOT NULL UNIQUE,
    short_description TEXT NOT NULL,
    description TEXT NOT NULL,
    ingredients TEXT NOT NULL,
    price REAL NOT NULL DEFAULT 0.00,
    mrp REAL NOT NULL DEFAULT 0.00,
    discount_percent INTEGER DEFAULT 0,
    stock INTEGER NOT NULL DEFAULT 50,
    weight TEXT DEFAULT '500g',
    shelf_life TEXT DEFAULT '12 Months',
    storage_instructions TEXT DEFAULT 'Store in a cool, dry place. Always use a dry spoon.',
    main_image TEXT NOT NULL,
    is_featured INTEGER DEFAULT 0,
    is_bestseller INTEGER DEFAULT 0,
    is_new INTEGER DEFAULT 0,
    rating_cache REAL DEFAULT 5.0,
    reviews_count INTEGER DEFAULT 0,
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE product_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    image_path TEXT NOT NULL,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE product_variants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    weight_label TEXT NOT NULL,
    sku_variant TEXT NOT NULL,
    price REAL NOT NULL,
    mrp REAL NOT NULL,
    discount_percent INTEGER DEFAULT 0,
    stock INTEGER NOT NULL DEFAULT 20,
    is_default INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE cart (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER,
    session_id TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cart_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cart_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    variant_id INTEGER,
    quantity INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
);

CREATE TABLE wishlist (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE TABLE wishlist_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    wishlist_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wishlist_id) REFERENCES wishlist(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE(wishlist_id, product_id)
);

CREATE TABLE addresses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL,
    full_name TEXT NOT NULL,
    mobile TEXT NOT NULL,
    house_flat TEXT NOT NULL,
    street TEXT NOT NULL,
    landmark TEXT,
    city TEXT NOT NULL,
    state TEXT NOT NULL,
    pincode TEXT NOT NULL,
    type TEXT DEFAULT 'home',
    is_default INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_number TEXT NOT NULL UNIQUE,
    customer_id INTEGER,
    customer_name TEXT NOT NULL,
    customer_email TEXT NOT NULL,
    customer_mobile TEXT NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_city TEXT NOT NULL,
    shipping_state TEXT NOT NULL,
    shipping_pincode TEXT NOT NULL,
    subtotal REAL NOT NULL DEFAULT 0.00,
    discount_amount REAL NOT NULL DEFAULT 0.00,
    coupon_code TEXT,
    shipping_fee REAL NOT NULL DEFAULT 0.00,
    total_amount REAL NOT NULL DEFAULT 0.00,
    payment_method TEXT NOT NULL DEFAULT 'cod',
    payment_status TEXT NOT NULL DEFAULT 'pending',
    order_status TEXT NOT NULL DEFAULT 'Pending',
    tracking_number TEXT,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

CREATE TABLE order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    product_id INTEGER,
    product_name TEXT NOT NULL,
    variant_label TEXT NOT NULL,
    sku TEXT NOT NULL,
    unit_price REAL NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    subtotal REAL NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

CREATE TABLE payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    payment_method TEXT NOT NULL,
    transaction_id TEXT,
    gateway_order_id TEXT,
    gateway_signature TEXT,
    amount REAL NOT NULL,
    currency TEXT DEFAULT 'INR',
    status TEXT NOT NULL DEFAULT 'initiated',
    raw_response TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE coupons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    type TEXT NOT NULL DEFAULT 'percentage',
    value REAL NOT NULL,
    min_order_amount REAL DEFAULT 0.00,
    max_discount_amount REAL,
    usage_limit INTEGER DEFAULT 1000,
    used_count INTEGER DEFAULT 0,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE coupon_usage (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    coupon_id INTEGER NOT NULL,
    customer_id INTEGER,
    order_id INTEGER NOT NULL,
    discount_applied REAL NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    customer_id INTEGER,
    customer_name TEXT NOT NULL,
    customer_email TEXT,
    rating INTEGER NOT NULL DEFAULT 5,
    title TEXT,
    comment TEXT NOT NULL,
    is_verified_buyer INTEGER DEFAULT 1,
    status TEXT DEFAULT 'approved',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

CREATE TABLE banners (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    subtitle TEXT,
    badge_text TEXT,
    image TEXT NOT NULL,
    button_text TEXT DEFAULT 'Shop Now',
    button_url TEXT DEFAULT '/shop.php',
    sort_order INTEGER DEFAULT 0,
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE contact_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    mobile TEXT,
    subject TEXT NOT NULL,
    message TEXT NOT NULL,
    status TEXT DEFAULT 'new',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT NOT NULL UNIQUE,
    setting_value TEXT,
    setting_group TEXT DEFAULT 'general',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_type TEXT NOT NULL,
    user_id INTEGER,
    action TEXT NOT NULL,
    description TEXT,
    ip_address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    type TEXT DEFAULT 'info',
    is_read INTEGER DEFAULT 0,
    link TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- SEED DATA
INSERT INTO admins (name, email, password, role, status) VALUES
('Heritage Admin', 'admin@achar.com', '$2y$12$aZO9BDGJYdoRMsWNRBxGk.oMNtlOR.pyECO/R.59c0vRt8va0xNJK', 'super_admin', 'active');

INSERT INTO customers (name, email, mobile, password, status) VALUES
('Rahul Sharma', 'rahul@example.com', '9876543210', '$2y$12$fHWB.BgX/vvZVDONe4.gvOPo2geLKtyCm2lWWSANCEuZ6n5eyzafy', 'active');

INSERT INTO categories (id, name, slug, description, image, icon, display_order) VALUES
(1, 'Mango Pickles', 'mango-pickles', 'Handpicked raw Rajapuri & Ramkela mangoes sun-cured with cold-pressed mustard oil and aromatic spices.', 'cat-mango.jpg', 'bi-sun', 1),
(2, 'Lemon & Citrus', 'lemon-citrus', 'Juicy Kagzi lemons aged in earthen pots with rock salt, ajwain and warming spices.', 'cat-lemon.jpg', 'bi-brightness-high', 2),
(3, 'Garlic & Ginger', 'garlic-ginger', 'Hearty country garlic cloves and mountain ginger slow-cooked with fenugreek and mustard seeds.', 'cat-garlic.jpg', 'bi-shield-check', 3),
(4, 'Fiery Chilli', 'fiery-chilli', 'Traditional Banarasi stuffed red chillies and pungent green chillies for the ultimate spicy kick.', 'cat-chilli.jpg', 'bi-fire', 4),
(5, 'Punjabi Mixed Pickles', 'mixed-pickles', 'Seasonal winter vegetables like cauliflower, turnip, carrot & mango blended in rich mustard gravy.', 'cat-mixed.jpg', 'bi-palette', 5),
(6, 'Royal Heritage Combos', 'royal-combos', 'Artisanal gift sets and family combo jars bringing together our most-loved grandma recipes.', 'cat-combos.jpg', 'bi-gift', 6);

INSERT INTO products (id, category_id, name, slug, sku, short_description, description, ingredients, price, mrp, discount_percent, stock, weight, shelf_life, storage_instructions, main_image, is_featured, is_bestseller, is_new, rating_cache, reviews_count) VALUES
(1, 1, 'Traditional Kacchi Kairi Mango Achar', 'traditional-kacchi-kairi-mango-achar', 'ACH-MNG-001', 'Signature raw mango pickle sun-dried and preserved in cold-pressed mustard oil and crushed spices.', 'Our Traditional Kacchi Kairi Mango Achar is the crown jewel of Indian household dining. Hand-chopped green mangoes are cured in natural rock salt, tossed with cracked mustard, roasted fenugreek (methi dana), nigella (kalonji), and steeped in pure kachi ghani mustard oil. Every spoonful bursts with tangy crunch, warmth, and nostalgia.', 'Raw Green Mangoes, Pure Cold-Pressed Mustard Oil, Fenugreek Seeds, Fennel (Saunf), Mustard Seeds (Rai), Kalonji, Turmeric, Red Chilli Powder, Rock Salt, Asafoetida (Hing).', 269.00, 299.00, 10, 85, '500g', '18 Months', 'Store in a cool dry place. Keep oil floating above achar. Always use a clean dry spoon.', 'prod-mango.jpg', 1, 1, 0, 4.9, 28),
(2, 2, 'Khatta Meetha Nimbu Lemon Achar', 'khatta-meetha-nimbu-lemon-achar', 'ACH-LMN-002', 'Oil-free aged Kagzi lemon pickle balanced with rock sugar, roasted cumin, and black salt.', 'A medicinal, sweet-and-sour culinary masterpiece perfected over decades. Fresh thin-skinned Kagzi lemons are steeped in their own juice with Ayurvedic rock salt, ajwain, roasted jeera, and unrefined cane sugar. It improves digestion, pairs gloriously with stuffed parathas, khichdi, and mathri.', 'Kagzi Lemons, Unrefined Rock Sugar (Khand), Black Salt, Sendha Namak, Ajwain (Carom Seeds), Roasted Cumin Powder, Degi Mirch, Garam Masala.', 249.00, 289.00, 14, 60, '500g', '24 Months', 'No oil needed. The lemons get darker and richer as they age. Use a dry wooden or stainless steel spoon.', 'prod-lemon.jpg', 1, 1, 0, 4.8, 19),
(3, 5, 'Punjabi Heritage Mixed Vegetable Achar', 'punjabi-heritage-mixed-vegetable-achar', 'ACH-MIX-003', 'Winter special blend of cauliflower florets, red carrots, tender turnips, and raw mango.', 'An authentic North Indian winter celebration. Fresh cauliflower, sweet red carrots, baby turnips, and crisp raw mango are sun-dried to eliminate moisture, then tossed in an aromatic paste of mustard paste, jaggery, ginger, and aromatic spices in golden mustard oil.', 'Cauliflower, Red Carrots, Shalgam (Turnip), Raw Mango, Mustard Oil, Ginger, Garlic, Jaggery (Gur), Mustard Paste, Kashmiri Red Chilli, Turmeric, Salt.', 279.00, 320.00, 13, 45, '500g', '12 Months', 'Keep jar tightly closed in a cool pantry. Avoid wet spoons.', 'prod-mixed.jpg', 1, 1, 0, 4.7, 14),
(4, 3, 'Rajasthani Lahsun (Garlic) Spicy Achar', 'rajasthani-lahsun-garlic-spicy-achar', 'ACH-GAR-004', 'Whole peeled country garlic cloves steeped in roasted spices and pungent mustard oil.', 'Specially crafted for garlic lovers and heart wellness. Small, pungent country garlic pearls are softened naturally in sun-warmed mustard oil enriched with crushed coriander seeds, amchur (dry mango powder), and red chilli flakes. Bold, savory, and immensely appetizing.', 'Peeled Desi Garlic Cloves, Pure Mustard Oil, Coriander Seeds, Mustard Seeds, Fennel, Red Chilli Flakes, Amchur, Turmeric, Salt, Asafoetida.', 299.00, 349.00, 14, 38, '500g', '12 Months', 'Store in glass container away from direct sunlight.', 'prod-garlic.jpg', 1, 0, 1, 4.9, 22),
(5, 4, 'Banarasi Stuffed Lal Mirch Achar', 'banarasi-stuffed-lal-mirch-achar', 'ACH-RED-005', 'Plump whole red chillies packed with fragrant sattu and roasted spice masala.', 'The pride of Varanasi. Large vibrant red chillies are carefully slit, deseeded, and filled with a secret coarse spice blend roasted to perfection, drizzled with raw mustard oil and allowed to sun-cure for 21 days on rooftop terraces.', 'Sun-dried Red Chillies, Mustard Oil, Coarse Mustard, Saunf, Amchur, Kalonji, Fenugreek, Ajwain, Black Salt, Pure Asafoetida.', 319.00, 369.00, 14, 40, '500g', '18 Months', 'Keep chilled after opening for longest freshness. Oil level must cover the chillies.', 'prod-chilli.jpg', 1, 1, 0, 5.0, 31),
(6, 3, 'Adrak-Hari Mirch Zing Achar', 'adrak-hari-mirch-zing-achar', 'ACH-ZNG-006', 'Crunchy mountain ginger juliennes and slit green chillies infused with lemon juice.', 'A zesty palate cleanser! Crisp slivers of young ginger and fresh green chillies tossed with yellow mustard seeds, turmeric, and fresh lime juice. Crisp, tingling, and completely oil-free with high digestive benefits.', 'Fresh Ginger Juliennes, Fresh Green Chillies, Lemon Juice, Yellow Mustard, Salt, Turmeric, Roasted Cumin.', 239.00, 269.00, 11, 55, '500g', '6 Months', 'Refrigerate after receiving for maximum crispness.', 'prod-ginger.jpg', 0, 0, 1, 4.6, 12),
(7, 1, 'Hing & Mango Digestif Special Achar', 'hing-mango-digestif-special-achar', 'ACH-HNG-007', 'Finely diced raw mangoes steeped with potent Hathras Hing and red spices.', 'An intensely aromatic pickle featuring superior grade Hathras Asafoetida (Hing). Diced green mango cubes absorb the bold sulfurous, savory pungency of pure hing, making it an irresistible companion for dal chawal, poori, and theplas.', 'Raw Mango Cubes, Pure Mustard Oil, Hathras Hing (Asafoetida), Mustard Seeds, Methi Dana, Kashmiri Mirch, Rock Salt, Turmeric.', 289.00, 329.00, 12, 35, '500g', '18 Months', 'Store dry, airtight, and away from direct moisture.', 'prod-hing.jpg', 0, 0, 1, 4.8, 8),
(8, 6, 'Shahi Dawat Combo Trio (Mango, Lemon, Garlic)', 'shahi-dawat-combo-trio', 'ACH-CMB-008', 'The ultimate culinary gift set featuring 3 bestselling 350g jars in a regal packaging.', 'Experience the royal trinity of Indian flavours. Contains 350g of Traditional Kacchi Kairi, 350g of Khatta Meetha Lemon, and 350g of Rajasthani Lahsun Achar packed in an eco-friendly jute and gold foiled gift box.', 'Assorted ingredients: Mango, Lemon, Garlic, Mustard Oil, Spices, Salt, Rock Sugar.', 749.00, 899.00, 17, 25, '1050g', '18 Months', 'Store in cool ambient environment. Individual jars have airtight inner lids.', 'prod-combo.jpg', 1, 1, 1, 5.0, 45);

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

INSERT INTO addresses (customer_id, full_name, mobile, house_flat, street, landmark, city, state, pincode, type, is_default) VALUES
(1, 'Rahul Sharma', '9876543210', 'Flat 402, Nilgiri Heights', 'MG Road, Sector 14', 'Near Central Park', 'Jaipur', 'Rajasthan', '302015', 'home', 1);

INSERT INTO coupons (code, type, value, min_order_amount, max_discount_amount, usage_limit, used_count, start_date, end_date, status) VALUES
('WELCOME10', 'percentage', 10.00, 200.00, 100.00, 500, 12, '2026-01-01', '2027-12-31', 'active'),
('FESTIVE100', 'fixed', 100.00, 499.00, 100.00, 250, 4, '2026-01-01', '2027-12-31', 'active'),
('ACHARLOVE', 'percentage', 15.00, 599.00, 150.00, 200, 18, '2026-01-01', '2027-12-31', 'active');

INSERT INTO reviews (product_id, customer_name, customer_email, rating, title, comment, is_verified_buyer, status) VALUES
(1, 'Meenakshi Sundaram', 'meenakshi@example.com', 5, 'Takes me back to my grandmother’s kitchen!', 'The mustard oil aroma hit me the second I opened the seal. Perfectly spiced and crunchy raw mango pieces. Truly unmatched quality.', 1, 'approved'),
(1, 'Vikram Oberoi', 'vikram@example.com', 5, 'Authentic mustard oil goodness', 'Tastes just like traditional UP style achar. Excellent packaging with zero leakage. Ordered 1kg jar again.', 1, 'approved'),
(2, 'Ananya Das', 'ananya@example.com', 5, 'Perfect sweet tangy balance', 'The aged lemon achar is so soothing on the stomach and delicious with theplas. Truly homemade feel.', 1, 'approved'),
(5, 'Siddharth Varma', 'sid@example.com', 5, 'Banarasi red chillies are unbelievable!', 'Crisp outer chilli, packed with roasted masala inside. Pure heaven with hot dal and rice.', 1, 'approved'),
(8, 'Priya Kulkarni', 'priya@example.com', 5, 'Gifted this to my parents', 'The royal gift packaging is stunning and all 3 flavors are extraordinary. Highly recommended!', 1, 'approved');

INSERT INTO banners (title, subtitle, badge_text, image, button_text, button_url, sort_order, status) VALUES
('Authentic Achar. Traditional Taste.', 'Handcrafted Indian pickles slow-cured under the Indian sun in cold-pressed mustard oil.', 'Fresh • Traditional • Pure', 'banner-hero.jpg', 'Shop Achar Now', '/shop.php', 1, 'active'),
('Royal Festive Pickles Collection', 'Get up to 20% OFF on all Heritage Combo Jars & Family Packs.', 'Special Limited Offer', 'banner-festive.jpg', 'Explore Combos', '/shop.php?category=royal-combos', 2, 'active');

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

INSERT INTO orders (id, order_number, customer_id, customer_name, customer_email, customer_mobile, shipping_address, shipping_city, shipping_state, shipping_pincode, subtotal, discount_amount, coupon_code, shipping_fee, total_amount, payment_method, payment_status, order_status, tracking_number) VALUES
(1, 'ACH202610050001', 1, 'Rahul Sharma', 'rahul@example.com', '9876543210', 'Flat 402, Nilgiri Heights, MG Road, Sector 14, Near Central Park', 'Jaipur', 'Rajasthan', '302015', 538.00, 53.80, 'WELCOME10', 0.00, 484.20, 'cod', 'pending', 'Shipped', 'DELHIVERY-ACH-89210');

INSERT INTO order_items (order_id, product_id, product_name, variant_label, sku, unit_price, quantity, subtotal) VALUES
(1, 1, 'Traditional Kacchi Kairi Mango Achar', '500g', 'ACH-MNG-001-500', 269.00, 1, 269.00),
(1, 3, 'Punjabi Heritage Mixed Vegetable Achar', '500g', 'ACH-MIX-003-500', 279.00, 1, 279.00);
