<?php
/**
 * Laravel Blade Proxy for optimized-search
 * This file proxies requests to the Laravel development server
 */

// Check if Laravel development server is running
$laravel_url = 'http://127.0.0.1:8000/optimized-search';

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $laravel_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

// Forward headers
$headers = [];
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        $header = str_replace('HTTP_', '', $key);
        $header = str_replace('_', '-', $header);
        $headers[] = $header . ': ' . $value;
    }
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Forward POST data if present
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
}

// Execute request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

// Check for errors
if (curl_error($ch)) {
    // Fallback to static HTML if Laravel server is not available
    header('Location: /tecdoc-api/public/optimized-search.html');
    exit;
}

curl_close($ch);

// Set appropriate headers
http_response_code($http_code);
if ($content_type) {
    header('Content-Type: ' . $content_type);
}

// Output response
echo $response;
?>
