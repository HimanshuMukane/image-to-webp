<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    // Define upload directory
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // File upload handling
    $uploadedFile = $_FILES['image']['tmp_name'];
    $originalFileName = basename($_FILES['image']['name']);
    $extension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
    
    if (!in_array($extension, ['png', 'jpg', 'jpeg'])) {
        die("Error: Only PNG, JPG, and JPEG files are allowed.");
    }

    // Set file paths
    $tempFile = $uploadDir . uniqid('uploaded_', true) . '.' . $extension;
    $jpgFile = $uploadDir . uniqid('output_', true) . '.jpg';
    $webpFile = $uploadDir . uniqid('output_', true) . '.webp';

    // Move uploaded file
    if (!move_uploaded_file($uploadedFile, $tempFile)) {
        die("Error: File upload failed.");
    }

    // Function to resize and convert PNG/JPG to JPG
    function convertToJpg($inputFile, $outputFile, $quality = 90, $resizeWidth = 150, $resizeHeight = 150) {
        $image = null;
        $extension = strtolower(pathinfo($inputFile, PATHINFO_EXTENSION));

        // Load image based on type
        if ($extension === 'png') {
            $image = imagecreatefrompng($inputFile);
        } elseif (in_array($extension, ['jpg', 'jpeg'])) {
            $image = imagecreatefromjpeg($inputFile);
        } else {
            die("Error: Unsupported file type.");
        }

        if (!$image) {
            die("Error: Unable to create image resource.");
        }

        // Resize image
        $width = imagesx($image);
        $height = imagesy($image);
        $resizedImage = imagecreatetruecolor($resizeWidth, $resizeHeight);

        // Add white background for PNG transparency
        $white = imagecolorallocate($resizedImage, 255, 255, 255);
        imagefilledrectangle($resizedImage, 0, 0, $resizeWidth, $resizeHeight, $white);

        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $resizeWidth, $resizeHeight, $width, $height);

        // Save as JPG
        if (!imagejpeg($resizedImage, $outputFile, $quality)) {
            die("Error: Unable to save JPG.");
        }

        // Free memory
        imagedestroy($image);
        imagedestroy($resizedImage);
        return $outputFile;
    }

    // Function to convert JPG to WebP
    function convertToWebp($inputFile, $outputFile, $quality = 90) {
        $image = imagecreatefromjpeg($inputFile);
        if (!$image) {
            die("Error: Unable to create image from JPG.");
        }

        if (!imagewebp($image, $outputFile, $quality)) {
            die("Error: Unable to save WebP.");
        }

        imagedestroy($image);
        return $outputFile;
    }

    // Convert and resize PNG/JPG to JPG
    convertToJpg($tempFile, $jpgFile, 90, 150, 150);

    // Convert resized JPG to WebP
    convertToWebp($jpgFile, $webpFile, 90);

    echo "Conversion completed!<br>";
    echo "WebP File: <a href='$webpFile'>$webpFile</a>";
} else {
    // Display the upload form
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Image Upload and Convert</title>
    </head>
    <body>
        <h1>Upload an Image</h1>
        <form action="" method="POST" enctype="multipart/form-data">
            <label for="image">Choose an image (PNG, JPG):</label>
            <input type="file" name="image" id="image" required>
            <button type="submit">Upload and Convert</button>
        </form>
    </body>
    </html>
    HTML;
}
?>
