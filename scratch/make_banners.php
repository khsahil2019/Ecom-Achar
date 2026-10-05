<?php
/**
 * Script to generate high-resolution branded gourmet product images and banners
 * RED & WHITE LUXURY PALETTE
 */

$banners = [
    'banner-hero.jpg' => ['w' => 1200, 'h' => 600, 'title' => 'Authentic Handcrafted Achar', 'sub' => 'Slow Sun-Cured In Cold Pressed Mustard Oil', 'r1' => 122, 'g1' => 12, 'b1' => 17, 'r2' => 197, 'g2' => 22, 'b2' => 29],
    'banner-festive.jpg' => ['w' => 1200, 'h' => 500, 'title' => 'Grandmother’s Secret Recipes', 'sub' => 'Pure Ingredients • No Chemical Preservatives', 'r1' => 155, 'g1' => 15, 'b1' => 20, 'r2' => 220, 'g2' => 38, 'b2' => 38],
];

function drawHeroBanner($filename, $info) {
    $w = $info['w']; $h = $info['h'];
    $img = imagecreatetruecolor($w, $h);
    
    // Deep royal crimson culinary gradient (Red & White Theme)
    for ($y = 0; $y < $h; $y++) {
        $ratio = $y / $h;
        $r = (int)($info['r1'] * (1 - $ratio) + $info['r2'] * $ratio);
        $g = (int)($info['g1'] * (1 - $ratio) + $info['g2'] * $ratio);
        $b = (int)($info['b1'] * (1 - $ratio) + $info['b2'] * $ratio);
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $w, $y, $color);
    }

    $gold = imagecolorallocate($img, 254, 240, 138);
    $white = imagecolorallocate($img, 255, 255, 255);
    $cream = imagecolorallocate($img, 255, 245, 245);

    imagestring($img, 5, 80, (int)($h / 2 - 60), "ACHAR HERITAGE TRADITION", $gold);
    imagestring($img, 5, 80, (int)($h / 2 - 20), strtoupper($info['title']), $white);
    imagestring($img, 4, 80, (int)($h / 2 + 25), $info['sub'], $cream);

    $targetPath = __DIR__ . '/../uploads/banners/' . $filename;
    imagejpeg($img, $targetPath, 92);
}

foreach ($banners as $file => $info) {
    drawHeroBanner($file, $info);
}

echo "Banners regenerated in Red & White theme!\n";
