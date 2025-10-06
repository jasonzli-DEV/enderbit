<?php
// Get the image ID from the URL path
$requestUri = $_SERVER['REQUEST_URI'];
$pathParts = explode('/', trim($requestUri, '/'));

// Extract image ID (e.g., /image/filename.jpg or /image.php?id=filename.jpg)
$imageId = '';
if (isset($_GET['id'])) {
    $imageId = $_GET['id'];
} elseif (count($pathParts) >= 2 && $pathParts[0] === 'image') {
    $imageId = $pathParts[1];
}

// Remove any query strings
$imageId = explode('?', $imageId)[0];

// Security: validate the image ID to prevent directory traversal
if (empty($imageId) || strpos($imageId, '..') !== false || strpos($imageId, '/') !== false) {
    header("HTTP/1.0 404 Not Found");
    die("Image not found");
}

// Look for the image in the uploads directory
$uploadsDir = __DIR__ . '/uploads/';
$imagePath = $uploadsDir . $imageId;

// Check if file exists
if (!file_exists($imagePath) || !is_file($imagePath)) {
    header("HTTP/1.0 404 Not Found");
    die("Image not found");
}

// Get file extension and set appropriate MIME type
$extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'pdf' => 'application/pdf',
    'txt' => 'text/plain',
    'log' => 'text/plain',
    'zip' => 'application/zip'
];

$mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

// For images, display them inline; for other files, show as attachment
$isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif']);

// Set headers
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($imagePath));

if ($isImage) {
    // Display image inline
    header('Content-Disposition: inline; filename="' . basename($imagePath) . '"');
    header('Cache-Control: public, max-age=86400'); // Cache for 1 day
} else {
    // Download other file types
    header('Content-Disposition: attachment; filename="' . basename($imagePath) . '"');
}

// Output the file
readfile($imagePath);
exit;
