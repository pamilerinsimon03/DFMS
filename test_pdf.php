<?php
$in  = __DIR__ . '/public/uploads/1748466322_Oladunjoye Esther_CHAPTER_(1-5).pdf'; // replace with actual PDF path
$out = __DIR__ . '/output_page0.png';

try {
    $im = new Imagick();
    $im->setResolution(150,150);        // Critical for PDF quality
    $im->readImage($in . '[0]');        // First page only
    $im->setImageFormat('png');
    $im->writeImage($out);
    echo "✅ PDF converted to PNG successfully.\n";
} catch (Exception $e) {
    echo "❌ Imagick error: " . $e->getMessage() . "\n";
}