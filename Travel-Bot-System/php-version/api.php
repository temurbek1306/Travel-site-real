<?php
// ============================================================
//  IZLANISH TRAVEL — REST API (PHP)
//  Fayl: api.php
//  Endpointlar:
//    GET  api.php?action=tours         → barcha turlarni qaytaradi
//    POST api.php?action=booking       → ariza qabul qiladi
// ============================================================

define('BOT_TOKEN',  '8680250666:AAGKVSraY_sd4IzrZ7SIpjZLECODxfPJ28Y');
define('ADMIN_ID',   '5701828462');
define('DATA_FILE',  __DIR__ . '/data.json');

// CORS — boshqa domendan so'rov kelsa ham ishlashi uchun
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// -------------------------------------------------------
// YORDAMCHI FUNKSIYALAR
// -------------------------------------------------------
function readData(): array {
    if (!file_exists(DATA_FILE)) {
        return ['tours' => [], 'users' => [], 'leads' => [], 'settings' => ['admin_id' => null]];
    }
    return json_decode(file_get_contents(DATA_FILE), true)
        ?: ['tours' => [], 'users' => [], 'leads' => [], 'settings' => ['admin_id' => null]];
}

function writeData(array $data): void {
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function sendTelegramMessage(string $chatId, string $text): void {
    $url  = 'https://api.telegram.org/bot' . BOT_TOKEN . '/sendMessage';
    $payload = json_encode([
        'chat_id'    => $chatId,
        'text'       => $text,
        'parse_mode' => 'Markdown',
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------
// ROUTING
// -------------------------------------------------------
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// GET /api.php?action=tours
if ($action === 'tours' && $method === 'GET') {
    $data = readData();
    jsonResponse($data['tours']);
}

// GET /api.php?action=settings
if ($action === 'settings' && $method === 'GET') {
    $data = readData();
    $settings = $data['settings'] ?? [];
    if (empty($settings['phone'])) $settings['phone'] = '+998 90 638 76 78';
    if (empty($settings['whatsapp'])) $settings['whatsapp'] = '+998 91 965 64 17';
    if (empty($settings['telegram'])) $settings['telegram'] = 'izlanishcom';
    if (empty($settings['email'])) $settings['email'] = 'izlanishtravel@gmail.com';
    if (empty($settings['instagram'])) $settings['instagram'] = '@izlanishcom';
    jsonResponse($settings);
}

// POST /api.php?action=booking
if ($action === 'booking' && $method === 'POST') {
    $body  = json_decode(file_get_contents('php://input'), true);
    $name  = trim($body['name']  ?? '');
    $phone = trim($body['phone'] ?? '');
    $tour  = trim($body['tour']  ?? '');

    if ($name === '' || $phone === '') {
        jsonResponse(['success' => false, 'message' => 'Ism va telefon kerak'], 400);
    }

    $data    = readData();
    $newLead = [
        'id'    => (int)(microtime(true) * 1000),
        'name'  => $name,
        'phone' => $phone,
        'tour'  => $tour,
        'date'  => date('d/m/Y, H:i:s'),
    ];
    $data['leads'][] = $newLead;
    writeData($data);

    // Adminga Telegram xabar
    if (ADMIN_ID !== 'YOUR_ADMIN_ID_HERE') {
        $tgMsg = "⚡️ *Yangi ariza!*\n\n"
               . "👤 *Mijoz:* {$name}\n"
               . "📞 *Tel:* {$phone}\n"
               . "🌍 *Tur:* {$tour}\n"
               . "📅 *Vaqt:* {$newLead['date']}";
        sendTelegramMessage(ADMIN_ID, $tgMsg);
    }

    jsonResponse(['success' => true, 'message' => 'Ariza qabul qilindi']);
}

// Noto'g'ri endpoint
jsonResponse(['success' => false, 'message' => 'Notog\'ri so\'rov'], 404);
