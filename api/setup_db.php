<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Connect to MySQL server using env variables or defaults
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'gs_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

try {
    // If we are on localhost, we can try to recreate the database.
    // Otherwise (on remote like Railway), we connect directly to the existing database.
    if ($host === 'localhost' || $host === '127.0.0.1') {
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("DROP DATABASE IF EXISTS `$dbname`");
        $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 2. Create Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        phone VARCHAR(50) DEFAULT '',
        role VARCHAR(50) DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 3. Create Categories Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        description TEXT NULL,
        image_url VARCHAR(255) NULL
    ) ENGINE=InnoDB;");

    // 4. Create Products Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        stock INT NOT NULL DEFAULT 0,
        image_url VARCHAR(255) DEFAULT '',
        is_featured TINYINT(1) DEFAULT 0,
        category VARCHAR(100) DEFAULT NULL,
        category_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;");

    // 5. Create Services Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        duration INT NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 6. Create Appointments Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        service_id INT NOT NULL,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        notes TEXT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 7. Create Cart Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS cart (
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        PRIMARY KEY (user_id, product_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 8. Create Orders Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        order_number VARCHAR(100) UNIQUE NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        grand_total DECIMAL(10,2) NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 9. Create Order Items Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // =============================================
    // SEEDING DATA
    // =============================================
    
    // Seed Users
    $password_hash = password_hash('password123', PASSWORD_DEFAULT);
    $admin_password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['admin@gscenter.com', $admin_password_hash, 'GS Center Admin', '+234 806 196 5586', 'admin']);
    $stmt->execute(['user@example.com', $password_hash, 'John Doe', '+1 234 567 890', 'user']);
    $stmt->execute(['customer@gscenter.com', $password_hash, 'Mustapha Ganiyat', '+234 806 196 5586', 'user']);

    // Seed Categories
    $categories = [
        ['Elixirs', 'elixirs', 'Premium liquid herbal preparations and extracts.'],
        ['Supplements', 'supplements', 'High-quality herbal capsules and tablets.'],
        ['Wellness Teas', 'wellness-teas', 'Traditional Chinese medicinal tea blends.'],
        ['Equipment', 'equipment', 'Bio-energy and far-infrared therapy instruments.'],
        ['Skincare', 'skincare', 'Natural herbal skincare products and cosmetic accessories.']
    ];
    
    $cat_ids = [];
    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
    foreach ($categories as $cat) {
        $stmt->execute([$cat[0], $cat[1], $cat[2]]);
        $cat_ids[$cat[0]] = $pdo->lastInsertId();
    }

    // Seed Products with exact filenames from public/images/products/
    $products = [
        [
            'FOHOW Oral Liquid (Elixir)', 
            'FOHOW Oral Liquid is a natural immune booster and cellular regenerator containing Cordyceps Sinensis and Ganoderma. It helps regulate the immune system, improve sleep, and fight fatigue.', 
            45.00, 50, '/images/products/fohow-oral-liquid.jpg', 1, 'Elixirs', $cat_ids['Elixirs']
        ],
        [
            'Sanbao Oral Liquid', 
            'FOHOW Sanbao Oral Liquid is enriched with Cordyceps and Black Mountain Ants extract, specifically designed to support joint health, enhance vitality, and strengthen the kidneys.', 
            50.00, 35, '/images/products/sanbao-oral-liquid.jpg', 1, 'Elixirs', $cat_ids['Elixirs']
        ],
        [
            'Sanqing Oral Liquid', 
            'FOHOW Sanqing Oral Liquid is a powerful detoxifier that cleanses the digestive tract, clears the liver and gallbladder, and regulates blood lipids.', 
            48.00, 40, '/images/products/sanqing-oral-liquid.jpg', 0, 'Elixirs', $cat_ids['Elixirs']
        ],
        [
            'Seaweed Calcium (Haicao Gai)', 
            'Liquid calcium extracted from seaweed, highly bioavailable and easily absorbed. Strengthens bones, teeth, and supports cardiovascular health.', 
            28.00, 100, '/images/products/seaweed-calcium.jpg', 1, 'Supplements', $cat_ids['Supplements']
        ],
        [
            'Xueqingfu (Serum Fu) Capsules', 
            'FOHOW Xueqingfu Capsules contain Nattokinase, which helps dissolve blood clots, regulates blood pressure, and improves microcirculation.', 
            38.00, 60, '/images/products/serum-fu-capsule.jpg', 1, 'Supplements', $cat_ids['Supplements']
        ],
        [
            'Ganoderma (Linchzhi) Capsules', 
            'Ganoderma Lucidum capsules help calm the nervous system, improve brain activity, protect the liver, and slow aging.', 
            35.00, 80, '/images/products/ganoderma-capsules.jpg', 0, 'Supplements', $cat_ids['Supplements']
        ],
        [
            'Garlic Oil Softgels', 
            'Pure garlic extract capsule acting as a natural antibiotic. Supports cardiovascular health, digestion, and immune defense.', 
            22.00, 120, '/images/products/garlic-oil.jpg', 0, 'Supplements', $cat_ids['Supplements']
        ],
        [
            'High Fiber (Gaoqian) Tablets', 
            'FOHOW Gaoqian Tablets help cleanse the intestines, bind toxins and heavy metals, reduce cholesterol, and support weight management.', 
            30.00, 75, '/images/products/high-fiber-tablet.jpg', 0, 'Supplements', $cat_ids['Supplements']
        ],
        [
            'Rose Oligosaccharide Paste', 
            'Meigui Paste regulates intestinal microflora, improves digestion, relieves constipation, and enhances skin complexion from within.', 
            20.00, 90, '/images/products/rose-oligosaccharide.jpg', 0, 'Supplements', $cat_ids['Supplements']
        ],
        [
            'Liuwei Tea', 
            'A unique blend of Pu-erh tea, Cordyceps, and five other TCM herbs. Cleanses the stomach, aids digestion, and lowers blood lipids.', 
            18.00, 150, '/images/products/liu-wei-tea.jpg', 1, 'Wellness Teas', $cat_ids['Wellness Teas']
        ],
        [
            'Boss Tea', 
            'Traditional Chinese herbal tea formulated to relieve fatigue, enhance alertness, and provide powerful antioxidant support.', 
            20.00, 110, '/images/products/boss-tea.jpg', 0, 'Wellness Teas', $cat_ids['Wellness Teas']
        ],
        [
            'Bio-energy Massager (Meridian Instrument)', 
            'The FOHOW Meridian Massager (WDS) combines acupuncture, massage, cupping, and heat therapy to unblock meridians, relieve pain, and improve circulation.', 
            450.00, 15, '/images/products/meridian-instrument.jpg', 1, 'Equipment', $cat_ids['Equipment']
        ],
        [
            'Life Energy Instrument', 
            'Advanced bio-electric and infrared therapy device to recharge cells, relieve chronic muscle pain, and enhance natural healing.', 
            680.00, 8, '/images/products/life-energy-instrument.jpg', 1, 'Equipment', $cat_ids['Equipment']
        ],
        [
            'Yang Sheng Cup', 
            'Energy cup that alkalizes water, filters impurities, and structures water molecules for better cellular hydration and detoxification.', 
            95.00, 25, '/images/products/yang-sheng-cup.jpg', 0, 'Equipment', $cat_ids['Equipment']
        ],
        [
            'Far Infrared Device', 
            'Portable infrared heating device designed for targeted muscle relief, joint pain management, and tissue repair.', 
            180.00, 12, '/images/products/far-infrared-device.jpg', 0, 'Equipment', $cat_ids['Equipment']
        ],
        [
            'Ganoderma Toothpaste', 
            'Double-action toothpaste with Ganoderma and Cordyceps extracts. Prevents bleeding gums, removes plaque, and maintains oral hygiene.', 
            12.00, 200, '/images/products/ganoderma-toothpaste.jpg', 0, 'Skincare', $cat_ids['Skincare']
        ],
        [
            'Aloe Vera Gel', 
            'Pure Aloe Vera gel enriched with Cordyceps extracts to soothe skin irritations, sunburns, hydrate skin, and speed up wound healing.', 
            15.00, 130, '/images/products/aloe-gel.jpg', 0, 'Skincare', $cat_ids['Skincare']
        ],
        [
            'Herbal Essential Oil', 
            'Concentrated TCM herbal essential oil for body massage. Relieves tension, warms meridians, and eases muscle aches.', 
            25.00, 85, '/images/products/herbal-essential-oil.jpg', 0, 'Skincare', $cat_ids['Skincare']
        ],
        [
            'Waist Support (Functional Belt)', 
            'Self-heating far-infrared waist belt. Helps relieve back pain, improves lumbar circulation, and assists with posture correction.', 
            120.00, 30, '/images/products/waist-support.jpg', 0, 'Equipment', $cat_ids['Equipment']
        ],
        [
            'Neck Support Belt', 
            'Thermal self-heating collar to relieve cervical stiffness, neck pain, and headaches by improving local blood flow.', 
            60.00, 45, '/images/products/neck-support.jpg', 0, 'Equipment', $cat_ids['Equipment']
        ],
        [
            'Knee Support Bands', 
            'Magnetic infrared knee support wraps to relieve joint pain, arthritis discomfort, and protect knees during activities.', 
            80.00, 40, '/images/products/knee-support.jpg', 0, 'Equipment', $cat_ids['Equipment']
        ],
        [
            'Youth Peptide', 
            'Concentrated active peptide essence for anti-aging, firming the skin, reducing fine lines, and restoring youthful radiance.', 
            75.00, 50, '/images/products/young-peptide.jpg', 1, 'Skincare', $cat_ids['Skincare']
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, image_url, is_featured, category, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($products as $p) {
        $stmt->execute($p);
    }

    // Seed Services
    $services = [
        ['TCM Health Consultation', 'Personalized health analysis and treatment plan based on Traditional Chinese Medicine principles.', 50.00, 30],
        ['Bio-electric Meridian Massage', 'Unblock energy pathways, improve blood circulation, and relieve chronic body pains.', 80.00, 60],
        ['Acupuncture & Moxibustion', 'Traditional needle therapy combined with moxa heating to regulate Qi flow and restore organ balance.', 75.00, 45],
        ['Herbal Medicine Therapy', 'Tailored selection and brewing of FOHOW/TCM herbs to target underlying internal imbalances.', 60.00, 30],
        ['Thermal Detox Therapy', 'Deep sweating and far-infrared treatment to eliminate metabolic toxins and ease joint stiffness.', 45.00, 40]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO services (name, description, price, duration) VALUES (?, ?, ?, ?)");
    foreach ($services as $serv) {
        $stmt->execute($serv);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Database gs_db successfully recreated, tables fixed, and seeded with premium FOHOW products, services, and test users.'
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database setup failed: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
