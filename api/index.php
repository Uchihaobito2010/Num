<?php
/**
 * Tobi's Phone Info API
 * Owner: Tobi | Contact: @Aotpy
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$requestPath = $_SERVER['REQUEST_URI'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// API Info
if (strpos($requestPath, '/api') === 0 || $requestPath === '/info' || $requestPath === '/status') {
    $response = [
        "owner" => "Tobi",
        "year" => 2025,
        "contact" => [
            "telegram" => "@Aotpy",
            "website" => "https://Aotpy.vercel.app"
        ],
        "version" => "1.0",
        "api_name" => "num to info",
        "disclaimer" => "api is valid till my choice",
        "endpoints" => [
            "GET /api" => "API information",
            "GET /api/phone?number=919876543210" => "Phone lookup",
            "GET /status" => "API status",
            "GET /info" => "API info"
        ],
        "status" => "active",
        "timestamp" => date('Y-m-d H:i:s')
    ];
    
    if (strpos($requestPath, '/status') !== false) {
        $response['uptime'] = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        $response['server'] = 'Vercel PHP Runtime';
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

// Phone lookup
if (strpos($requestPath, '/api/phone') !== false) {
    $phone = $_GET['number'] ?? $_GET['phone'] ?? $_GET['term'] ?? '';
    
    if (empty($phone)) {
        http_response_code(400);
        echo json_encode([
            "error" => "Phone number required",
            "example" => "/api/phone?number=919876543210",
            "owner" => "Tobi"
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Clean phone number
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    
    // Demo response (replace with actual API call)
    $result = [
        "success" => true,
        "phone" => $cleanPhone,
        "data" => [
            "carrier" => "Demo Carrier",
            "location" => "Demo Location",
            "status" => "active",
            "lookup_time" => date('H:i:s')
        ],
        "meta" => [
            "owner" => "Tobi",
            "processed_at" => date('Y-m-d H:i:s'),
            "note" => "This is a demo response. Add actual API logic."
        ]
    ];
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

// Default response
echo json_encode([
    "message" => "Tobi API v1.0",
    "owner" => "Tobi",
    "contact" => "@Aotpy",
    "endpoints" => [
        "/api",
        "/api/phone?number=XXX",
        "/status",
        "/info"
    ]
], JSON_PRETTY_PRINT);
?>
