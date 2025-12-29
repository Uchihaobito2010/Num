<?php
/**
 * Tobi's Phone Info API
 * Owner: Tobi
 * Contact: @Aotpy
 * Website: https://Aotpy.vercel.app
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Get API information
 */
function getApiInfo() {
    return [
        "owner" => "Tobi",
        "year" => 2025,
        "contact" => [
            "telegram" => "@Aotpy",
            "website" => "https://Aotpy.vercel.app"
        ],
        "version" => "1.0",
        "api_name" => "num to info",
        "disclaimer" => "API is valid till my choice",
        "endpoints" => [
            "GET /api" => "API information",
            "GET /api/phone?number=XXXXXXXXXX" => "Phone lookup",
            "POST /api/phone" => "Phone lookup (JSON)",
            "GET /api/status" => "API status"
        ],
        "example" => "https://tobi-api.vercel.app/api/phone?number=919876543210"
    ];
}

/**
 * Create a new session for requests
 */
function createSession() {
    $url = "https://osintmynk.fun/";
    $sessionFile = tempnam(sys_get_temp_dir(), 'tobi_') . '.dat';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $sessionFile,
        CURLOPT_COOKIEFILE => $sessionFile,
        CURLOPT_USERAGENT => 'TobiAPI/1.0 (Vercel; +https://aotpy.vercel.app)',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    curl_exec($ch);
    curl_close($ch);
    
    return $sessionFile;
}

/**
 * Extract session ID from file
 */
function extractSessionId($file) {
    if (!file_exists($file)) return null;
    
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    
    foreach ($lines as $line) {
        if (strpos($line, 'PHPSESSID') !== false) {
            $parts = explode("\t", $line);
            if (count($parts) >= 7) {
                return trim($parts[6]);
            }
        }
    }
    
    return null;
}

/**
 * Perform phone number lookup
 */
function lookupPhoneNumber($phone) {
    $sessionFile = createSession();
    $sessionId = extractSessionId($sessionFile);
    
    if (!$sessionId) {
        return ['success' => false, 'error' => 'Session initialization failed'];
    }
    
    $apiUrl = "https://osintmynk.fun/backend.php";
    $boundary = "----TobiVercelBoundary" . time();
    
    $body = "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"action\"\r\n\r\n";
    $body .= "search\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"term\"\r\n\r\n";
    $body .= "{$phone}\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"slug\"\r\n\r\n";
    $body .= "Mobile v1\r\n";
    $body .= "--{$boundary}--\r\n";
    
    $headers = [
        "Content-Type: multipart/form-data; boundary={$boundary}",
        "User-Agent: TobiAPI/1.0 (Vercel; +https://aotpy.vercel.app)",
        "Referer: https://osintmynk.fun/",
        "Cookie: PHPSESSID={$sessionId}",
        "X-API-Owner: Tobi",
        "X-Contact: @Aotpy"
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Clean up session file
    if (file_exists($sessionFile)) {
        unlink($sessionFile);
    }
    
    // Parse response
    $data = json_decode($response, true);
    
    return [
        'success' => ($httpCode === 200 && !$error),
        'data' => $data ?: $response,
        'http_code' => $httpCode,
        'error' => $error ?: null,
        'session' => substr($sessionId, 0, 8) . '...'
    ];
}

/**
 * Clean phone number
 */
function cleanPhone($number) {
    return preg_replace('/[^0-9]/', '', $number);
}

/**
 * Validate phone number
 */
function validatePhone($number) {
    $clean = cleanPhone($number);
    return (strlen($clean) >= 8 && strlen($clean) <= 15);
}

/**
 * Main router
 */
$path = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Route: /api (root)
    if ($path === '/api' || $path === '/api/') {
        echo json_encode([
            'api' => getApiInfo(),
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'active'
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Route: /api/status
    if ($path === '/api/status' || strpos($path, '/status') !== false) {
        echo json_encode([
            'status' => 'online',
            'owner' => 'Tobi',
            'uptime' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3),
            'timestamp' => date('c'),
            'server' => 'Vercel PHP Runtime'
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Route: /api/phone
    if (strpos($path, '/api/phone') !== false) {
        $phone = '';
        
        if ($method === 'GET') {
            $phone = $_GET['number'] ?? $_GET['phone'] ?? $_GET['term'] ?? '';
        } elseif ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $phone = $input['number'] ?? $input['phone'] ?? $input['term'] ?? $_POST['number'] ?? $_POST['phone'] ?? $_POST['term'] ?? '';
        }
        
        if (empty($phone)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Phone number is required',
                'usage' => [
                    'GET' => '/api/phone?number=919876543210',
                    'POST' => '/api/phone with JSON {"number": "919876543210"}'
                ],
                'owner' => 'Tobi'
            ], JSON_PRETTY_PRINT);
            exit;
        }
        
        if (!validatePhone($phone)) {
            http_response_code(422);
            echo json_encode([
                'error' => 'Invalid phone number format',
                'provided' => $phone,
                'expected' => 'Valid international number (8-15 digits)'
            ], JSON_PRETTY_PRINT);
            exit;
        }
        
        $cleanNumber = cleanPhone($phone);
        $result = lookupPhoneNumber($cleanNumber);
        
        if (!$result['success']) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Phone lookup failed',
                'details' => $result['error'],
                'http_code' => $result['http_code'],
                'owner' => 'Tobi',
                'contact' => '@Aotpy'
            ], JSON_PRETTY_PRINT);
            exit;
        }
        
        // Success response
        echo json_encode([
            'success' => true,
            'data' => $result['data'],
            'query' => [
                'number' => $cleanNumber,
                'timestamp' => date('Y-m-d H:i:s')
            ],
            'meta' => [
                'owner' => 'Tobi',
                'year' => 2025,
                'contact' => '@Aotpy',
                'disclaimer' => 'API is valid till my choice',
                'processed_in' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3) . 's'
            ]
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Default route - API info
    echo json_encode(getApiInfo(), JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
        'owner' => 'Tobi',
        'contact' => '@Aotpy',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
