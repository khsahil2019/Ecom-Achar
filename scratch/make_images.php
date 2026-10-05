<?php
/**
 * Script to generate high-resolution branded gourmet product images and banners
 */

$products = [
    'prod-mango.jpg' => ['title' => 'KACCHI KAIRI', 'sub' => 'Traditional Mango Achar', 'bg1' => [234, 150, 20], 'bg2' => [180, 83, 9], 'accent' => [254, 240, 138]],
    'prod-mango-detail1.jpg' => ['title' => 'SUN-CURED MANGO', 'sub' => 'Slow Mustard Oil Steep', 'bg1' => [245, 158, 11], 'bg2' => [146, 64, 14], 'accent' => [254, 243, 199]],
    'prod-lemon.jpg' => ['title' => 'KHATTA MEETHA', 'sub' => 'Aged Nimbu Achar', 'bg1' => [245, 198, 44], 'bg2' => [202, 138, 4], 'accent' => [254, 249, 195]],
    'prod-mixed.jpg' => ['title' => 'PUNJABI MIXED', 'sub' => 'Seasonal Winter Blend', 'bg1' => [185, 28, 28], 'bg2' => [120, 20, 20], 'accent' => [254, 226, 226]],
    'prod-garlic.jpg' => ['title' => 'RAJASTHANI LAHSUN', 'sub' => 'Peeled Garlic in Mustard Oil', 'bg1' => [194, 65, 12], 'bg2' => [124, 45, 18], 'accent' => [255, 237, 213]],
    'prod-chilli.jpg' => ['title' => 'BANARASI LAL MIRCH', 'sub' => 'Stuffed Sun-dried Red Chilli', 'bg1' => [220, 38, 38], 'bg2' => [153, 27, 27], 'accent' => [254, 202, 202]],
    'prod-ginger.jpg' => ['title' => 'ADRAK HARI MIRCH', 'sub' => 'Zing Lemon Fresh Achar', 'bg1' => [101, 163, 13], 'bg2' => [63, 98, 18], 'accent' => [236, 252, 203]],
    'prod-hing.jpg' => ['title' => 'HATHRAS HING MANGO', 'sub' => 'Digestive Mango Special', 'bg1' => [217, 119, 6], 'bg2' => [161, 98, 7], 'accent' => [254, 243, 199]],
    'prod-combo.jpg' => ['title' => 'SHAHI DAWAT COMBO', 'sub' => 'Heritage Trio Gift Pack', 'bg1' => [20, 83, 45], 'bg2' => [10, 45, 25], 'accent' => [254, 240, 138]],
];

$categories = [
    'cat-mango.jpg' => ['title' => 'Mango Pickles', 'bg1' => 140, 'bg2' => 10, 'bg3' => 18, 'r2' => 195, 'g2' => 20, 'b2' => 25],
    'cat-lemon.jpg' => ['title' => 'Lemon & Citrus', 'bg1' => 165, 'bg2' => 15, 'bg3' => 22, 'r2' => 215, 'g2' => 30, 'b2' => 38],
    'cat-garlic.jpg' => ['title' => 'Garlic & Ginger', 'bg1' => 130, 'bg2' => 8, 'bg3' => 14, 'r2' => 185, 'g2' => 18, 'b2' => 24],
    'cat-chilli.jpg' => ['title' => 'Fiery Chilli', 'bg1' => 180, 'bg2' => 15, 'bg3' => 25, 'r2' => 235, 'g2' => 25, 'b2' => 35],
    'cat-mixed.jpg' => ['title' => 'Mixed Pickles', 'bg1' => 150, 'bg2' => 12, 'bg3' => 20, 'r2' => 200, 'g2' => 22, 'b2' => 30],
    'cat-combos.jpg' => ['title' => 'Royal Combos', 'bg1' => 110, 'bg2' => 6, 'bg3' => 12, 'r2' => 175, 'g2' => 15, 'b2' => 22],
];

$banners = [
    'banner-hero.jpg' => ['w' => 1200, 'h' => 600, 'title' => 'Authentic Handcrafted Achar', 'sub' => 'Slow Sun-Cured In Cold Pressed Mustard Oil'],
    'banner-festive.jpg' => ['w' => 1200, 'h' => 500, 'title' => 'Grandmother’s Secret Recipes', 'sub' => 'Pure Ingredients • No Chemical Preservatives'],
];

function drawJarImage($filename, $info, $w = 600, $h = 600) {
    $img = imagecreatetruecolor($w, $h);
    
    // Gradient Background
    for ($y = 0; $y < $h; $y++) {
        $ratio = $y / $h;
        $r = (int)($info['bg1'][0] * (1 - $ratio) + $info['bg2'][0] * $ratio);
        $g = (int)($info['bg1'][1] * (1 - $ratio) + $info['bg2'][1] * $ratio);
        $b = (int)($info['bg1'][2] * (1 - $ratio) + $info['bg2'][2] * $ratio);
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $w, $y, $color);
    }

    // Glass Jar Silhouette / Outline
    $glass = imagecolorallocatealpha($img, 255, 255, 255, 105);
    $glassHighlight = imagecolorallocatealpha($img, 255, 255, 255, 75);
    $lidGold = imagecolorallocate($img, 217, 160, 40);
    $clothTie = imagecolorallocate($img, 180, 50, 30);
    $white = imagecolorallocate($img, 255, 255, 255);
    $cream = imagecolorallocate($img, 253, 250, 240);
    $dark = imagecolorallocate($img, 30, 20, 10);
    $gold = imagecolorallocate($img, 234, 179, 8);

    // Jar body
    imagefilledellipse($img, $w / 2, $h / 2 + 30, 320, 360, $glass);
    imageellipse($img, $w / 2, $h / 2 + 30, 320, 360, $glassHighlight);

    // Jar Lid
    imagefilledrectangle($img, $w / 2 - 110, $h / 2 - 170, $w / 2 + 110, $h / 2 - 130, $lidGold);
    imagefilledrectangle($img, $w / 2 - 120, $h / 2 - 140, $w / 2 + 120, $h / 2 - 125, $clothTie);

    // Brand Label on Jar
    imagefilledrectangle($img, $w / 2 - 125, $h / 2 - 50, $w / 2 + 125, $h / 2 + 100, $cream);
    imagerectangle($img, $w / 2 - 120, $h / 2 - 45, $w / 2 + 120, $h / 2 + 95, $gold);
    imagerectangle($img, $w / 2 - 118, $h / 2 - 43, $w / 2 + 118, $h / 2 + 93, $gold);

    // Label Text
    imagestring($img, 3, $w / 2 - 55, $h / 2 - 35, "ACHAR HERITAGE", $dark);
    imagestring($img, 4, $w / 2 - (strlen($info['title']) * 4.5), $h / 2, $info['title'], $dark);
    imagestring($img, 2, $w / 2 - (strlen($info['sub']) * 3), $h / 2 + 25, $info['sub'], $dark);
    imagestring($img, 2, $w / 2 - 58, $h / 2 + 55, "100% PURE & ARTISANAL", $clothTie);
    imagestring($img, 2, $w / 2 - 40, $h / 2 + 72, "[ SUN CURED ]", $dark);

    // Badge
    imagefilledellipse($img, 90, 90, 110, 110, $white);
    imageellipse($img, 90, 90, 102, 102, $gold);
    imagestring($img, 3, 50, 75, "HANDCRAFTED", $dark);
    imagestring($img, 2, 55, 95, "ESTD 1968", $clothTie);

    // Save image
    $targetPath = __DIR__ . '/../uploads/products/' . $filename;
    imagejpeg($img, $targetPath, 90);
    imagedestroy($img);
}

function drawCategoryBanner($filename, $info) {
    $w = 400; $h = 300;
    $img = imagecreatetruecolor($w, $h);
    
    // Luxury Red Gradient
    $r1 = $info['bg1'] ?? 160; $g1 = $info['bg2'] ?? 15; $b1 = $info['bg3'] ?? 25;
    $r2 = $info['r2'] ?? 205; $g2 = $info['g2'] ?? 25; $b2 = $info['b2'] ?? 35;
    
    for ($y = 0; $y < $h; $y++) {
        $ratio = $y / $h;
        $r = (int)($r1 * (1 - $ratio) + $r2 * $ratio);
        $g = (int)($g1 * (1 - $ratio) + $g2 * $ratio);
        $b = (int)($b1 * (1 - $ratio) + $b2 * $ratio);
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $w, $y, $color);
    }
    
    $white = imagecolorallocate($img, 255, 255, 255);
    $cream = imagecolorallocate($img, 255, 245, 245);
    $gold = imagecolorallocate($img, 254, 240, 138);
    $crimson = imagecolorallocate($img, 180, 20, 30);
    
    // Elegant Inner Card Frame
    imagefilledrectangle($img, 25, 25, $w - 25, $h - 25, imagecolorallocatealpha($img, 255, 255, 255, 110));
    imagerectangle($img, 28, 28, $w - 28, $h - 28, $gold);
    
    // Center Circle Badge
    imagefilledellipse($img, (int)($w / 2), (int)($h / 2 - 15), 110, 110, $white);
    imageellipse($img, (int)($w / 2), (int)($h / 2 - 15), 104, 104, $crimson);
    imageellipse($img, (int)($w / 2), (int)($h / 2 - 15), 98, 98, $gold);
    
    // Pickle motif / text
    imagestring($img, 3, (int)($w / 2 - 42), (int)($h / 2 - 25), "AUTHENTIC", $crimson);
    imagestring($img, 2, (int)($w / 2 - 32), (int)($h / 2 - 5), "ACHAR", $crimson);
    
    // Category Title Banner
    imagefilledrectangle($img, 45, $h - 85, $w - 45, $h - 45, $crimson);
    imagerectangle($img, 47, $h - 83, $w - 47, $h - 43, $gold);
    imagestring($img, 4, (int)($w / 2 - (strlen($info['title']) * 4.2)), $h - 70, strtoupper($info['title']), $white);

    $targetPath = __DIR__ . '/../uploads/categories/' . $filename;
    imagejpeg($img, $targetPath, 92);
    imagedestroy($img);
}

function drawHeroBanner($filename, $info) {
    $w = $info['w']; $h = $info['h'];
    $img = imagecreatetruecolor($w, $h);
    
    // Deep royal culinary gradient
    for ($y = 0; $y < $h; $y++) {
        $ratio = $y / $h;
        $r = (int)(20 * (1 - $ratio) + 120 * $ratio);
        $g = (int)(60 * (1 - $ratio) + 40 * $ratio);
        $b = (int)(35 * (1 - $ratio) + 20 * $ratio);
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $w, $y, $color);
    }

    $gold = imagecolorallocate($img, 245, 198, 44);
    $white = imagecolorallocate($img, 255, 255, 255);
    $cream = imagecolorallocate($img, 254, 243, 199);

    imagestring($img, 5, 80, $h / 2 - 60, "ACHAR HERITAGE TRADITION", $gold);
    imagestring($img, 5, 80, $h / 2 - 20, strtoupper($info['title']), $white);
    imagestring($img, 4, 80, $h / 2 + 25, $info['sub'], $cream);

    $targetPath = __DIR__ . '/../uploads/banners/' . $filename;
    imagejpeg($img, $targetPath, 88);
    imagedestroy($img);
}

// Generate all
foreach ($products as $file => $info) {
    drawJarImage($file, $info);
}
foreach ($categories as $file => $info) {
    drawCategoryBanner($file, $info);
}
foreach ($banners as $file => $info) {
    drawHeroBanner($file, $info);
}

echo "All images generated successfully!\n";
