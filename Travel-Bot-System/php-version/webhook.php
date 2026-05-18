<?php
// ============================================================
//  IZLANISH TRAVEL BOT — COMPATIBILITY VERSION (PHP)
// ============================================================

define('BOT_TOKEN',  '8680250666:AAGKVSraY_sd4IzrZ7SIpjZLECODxfPJ28Y');
define('ADMIN_IDS',  ['5701828462', '304710602', '8001224133']);
define('DATA_FILE',  __DIR__ . '/data.json');
define('STATE_FILE', __DIR__ . '/user_states.json');
define('UPLOAD_DIR', __DIR__ . '/uploads/');

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/bot_errors.log');

// -------------------------------------------------------
// YORDAMCHI FUNKSIYALAR
// -------------------------------------------------------

function apiRequest($method, $data) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

function downloadFile($fileId) {
    $fileData = apiRequest('getFile', ['file_id' => $fileId]);
    if (isset($fileData['result']['file_path'])) {
        $filePath = $fileData['result']['file_path'];
        $url = "https://api.telegram.org/file/bot" . BOT_TOKEN . "/" . $filePath;
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $newName = time() . "_" . uniqid() . "." . $ext;
        $localPath = UPLOAD_DIR . $newName;
        
        $content = @file_get_contents($url);
        if ($content) {
            file_put_contents($localPath, $content);
            return "/uploads/" . $newName; // Sayt uchun yo'l (/ bilan boshlanishi kerak)
        }
    }
    return "";
}

function sendMessage($chatId, $text, $keyboard = null) {
    $data = ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'Markdown'];
    if ($keyboard) $data['reply_markup'] = $keyboard;
    return apiRequest('sendMessage', $data);
}

function readData() {
    if (!file_exists(DATA_FILE)) return ['tours' => [], 'users' => [], 'leads' => []];
    $data = json_decode(file_get_contents(DATA_FILE), true);
    return $data ?: ['tours' => [], 'users' => [], 'leads' => []];
}

function writeData($data) {
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function getState($userId) {
    $states = file_exists(STATE_FILE) ? json_decode(file_get_contents(STATE_FILE), true) : [];
    return $states[(string)$userId] ?? [];
}

function setState($userId, $state) {
    $states = file_exists(STATE_FILE) ? json_decode(file_get_contents(STATE_FILE), true) : [];
    if (empty($state)) unset($states[(string)$userId]);
    else $states[(string)$userId] = $state;
    file_put_contents(STATE_FILE, json_encode($states));
}

function isAdmin($userId) {
    return in_array((string)$userId, ADMIN_IDS);
}

function isSessionAuthorized($userId) {
    if (!isAdmin($userId)) return false;
    $sessionFile = __DIR__ . '/admin_sessions.json';
    $sessions = file_exists($sessionFile) ? json_decode(file_get_contents($sessionFile), true) : [];
    return in_array((string)$userId, $sessions);
}

function authorizeSession($userId) {
    $sessionFile = __DIR__ . '/admin_sessions.json';
    $sessions = file_exists($sessionFile) ? json_decode(file_get_contents($sessionFile), true) : [];
    if (!in_array((string)$userId, $sessions)) {
        $sessions[] = (string)$userId;
        file_put_contents($sessionFile, json_encode($sessions));
    }
}

function showAdminPanel($chatId) {
    $adminButtons = [
        'inline_keyboard' => [
            [['text' => '➕ Yangi tur', 'callback_data' => 'add_tour']],
            [['text' => '🗑 Turni o\'chirish', 'callback_data' => 'delete_list']],
            [['text' => '📝 Arizalar', 'callback_data' => 'view_leads']],
            [['text' => '🗑 Arizalarni tozalash', 'callback_data' => 'clear_leads']],
            [['text' => '📊 Statistika', 'callback_data' => 'stats']],
            [['text' => '⚙️ Kontakt Sozlamalari', 'callback_data' => 'edit_settings']],
            [['text' => '🚪 Chiqish (Log out)', 'callback_data' => 'logout_admin']]
        ]
    ];
    sendMessage($chatId, "🛠 *Admin Panel*", $adminButtons);
}

function showContactSettings($chatId) {
    $db = readData();
    $settings = $db['settings'] ?? [];
    $phone = $settings['phone'] ?? '+998 90 638 76 78';
    $whatsapp = $settings['whatsapp'] ?? '+998 91 965 64 17';
    $telegram = $settings['telegram'] ?? 'izlanishcom';
    $email = $settings['email'] ?? 'izlanishtravel@gmail.com';
    $instagram = $settings['instagram'] ?? '@izlanishcom';

    $text = "⚙️ *Kontakt Sozlamalari:*\n\n"
          . "📞 *Telefon:* `{$phone}`\n"
          . "💬 *WhatsApp:* `{$whatsapp}`\n"
          . "✈️ *Telegram:* @{$telegram}\n"
          . "📧 *Email:* `{$email}`\n"
          . "📸 *Instagram:* `{$instagram}`\n\n"
          . "O'zgartirmoqchi bo'lgan ma'lumotni tanlang:";

    $buttons = [
        'inline_keyboard' => [
            [['text' => '📞 Telefonni o\'zgartirish', 'callback_data' => 'edit_phone']],
            [['text' => '💬 WhatsAppni o\'zgartirish', 'callback_data' => 'edit_whatsapp']],
            [['text' => '✈️ Telegramni o\'zgartirish', 'callback_data' => 'edit_telegram']],
            [['text' => '📧 Emailni o\'zgartirish', 'callback_data' => 'edit_email']],
            [['text' => '📸 Instagramni o\'zgartirish', 'callback_data' => 'edit_instagram']],
            [['text' => '⬅️ Orqaga', 'callback_data' => 'back_to_admin']]
        ]
    ];
    sendMessage($chatId, $text, $buttons);
}


// -------------------------------------------------------
// ASOSIY ISHLOV BERUVCHI
// -------------------------------------------------------

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) exit;

if (isset($update['message'])) {
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $userId = $message['from']['id'];
    $text = $message['text'] ?? '';

    if ($text === '/start') {
        $db = readData();
        if (!in_array($userId, $db['users'])) {
            $db['users'][] = $userId;
            writeData($db);
        }
        $keyboard = [
            'keyboard' => [[['text' => '🌍 Turlarni ko\'rish']]],
            'resize_keyboard' => true
        ];
        if (isAdmin($userId)) $keyboard['keyboard'][] = [['text' => '🛠 Admin Panel']];
        sendMessage($chatId, "Xush kelibsiz! 👋", $keyboard);
        exit;
    }

    if ($text === '🛠 Admin Panel' && isAdmin($userId)) {
        if (!isSessionAuthorized($userId)) {
            setState($userId, ['step' => 'auth_password']);
            sendMessage($chatId, "🔐 Admin panelga kirish uchun parolni kiriting:");
            exit;
        }
        showAdminPanel($chatId);
        exit;
    }

    if ($text === '🌍 Turlarni ko\'rish') {
        $db = readData();
        if (empty($db['tours'])) {
            sendMessage($chatId, "Hozircha turlar yo'q.");
        } else {
            foreach (array_slice($db['tours'], -5) as $t) {
                $caption = "📍 *{$t['name_uz']}*\n💰 Narxi: \${$t['price']}\n⏳ Davomiyligi: {$t['duration_uz']}\n\n📖 {$t['description_uz']}";
                sendMessage($chatId, $caption);
            }
        }
        exit;
    }

    $state = getState($userId);
    if (!empty($state) && isAdmin($userId)) {
        $step = $state['step'];
        
        if ($step === 'auth_password') {
            // Xavfsizlik yuzasidan foydalanuvchi yozgan parolni chatdan o'chiramiz
            if (isset($message['message_id'])) {
                apiRequest('deleteMessage', ['chat_id' => $chatId, 'message_id' => $message['message_id']]);
            }
            
            if ($text === '60638889m') {
                authorizeSession($userId);
                setState($userId, []);
                sendMessage($chatId, "🔓 Parol to'g'ri! Admin panel ochildi.");
                showAdminPanel($chatId);
            } else {
                sendMessage($chatId, "❌ Parol noto'g'ri! Iltimos, qaytadan urinib ko'ring yoki /start bosing:");
            }
            exit;
        }

        if (!isSessionAuthorized($userId)) {
            setState($userId, ['step' => 'auth_password']);
            sendMessage($chatId, "🔐 Admin panelga kirish uchun parolni kiriting:");
            exit;
        }
        
        if ($step === 'set_phone') {
            $db = readData();
            $db['settings']['phone'] = $text;
            writeData($db);
            setState($userId, []);
            sendMessage($chatId, "✅ Telefon raqami yangilandi: *{$text}*");
            showContactSettings($chatId);
        }
        elseif ($step === 'set_whatsapp') {
            $db = readData();
            $db['settings']['whatsapp'] = $text;
            writeData($db);
            setState($userId, []);
            sendMessage($chatId, "✅ WhatsApp raqami yangilandi: *{$text}*");
            showContactSettings($chatId);
        }
        elseif ($step === 'set_telegram') {
            $db = readData();
            $tgUser = ltrim($text, '@');
            $db['settings']['telegram'] = $tgUser;
            writeData($db);
            setState($userId, []);
            sendMessage($chatId, "✅ Telegram username yangilandi: *{$text}*");
            showContactSettings($chatId);
        }
        elseif ($step === 'set_email') {
            $db = readData();
            $db['settings']['email'] = $text;
            writeData($db);
            setState($userId, []);
            sendMessage($chatId, "✅ Email yangilandi: *{$text}*");
            showContactSettings($chatId);
        }
        elseif ($step === 'set_instagram') {
            $db = readData();
            $db['settings']['instagram'] = $text;
            writeData($db);
            setState($userId, []);
            sendMessage($chatId, "✅ Instagram yangilandi: *{$text}*");
            showContactSettings($chatId);
        }
        elseif ($step === 'name_uz') { $state['name_uz'] = $text; $state['step'] = 'name_ru'; setState($userId, $state); sendMessage($chatId, "🇷🇺 Название тура (RU):"); }
        elseif ($step === 'name_ru') { $state['name_ru'] = $text; $state['step'] = 'name_en'; setState($userId, $state); sendMessage($chatId, "🇺🇸 Tour Name (EN):"); }
        elseif ($step === 'name_en') { $state['name_en'] = $text; $state['step'] = 'price'; setState($userId, $state); sendMessage($chatId, "💰 Narxi ($):"); }
        elseif ($step === 'price') { $state['price'] = $text; $state['step'] = 'duration_uz'; setState($userId, $state); sendMessage($chatId, "⏳ Davomiyligi (UZ, masalan: 7 kun / 6 kecha):"); }
        elseif ($step === 'duration_uz') { $state['duration_uz'] = $text; $state['step'] = 'duration_ru'; setState($userId, $state); sendMessage($chatId, "⏳ Продолжительность (RU):"); }
        elseif ($step === 'duration_ru') { $state['duration_ru'] = $text; $state['step'] = 'duration_en'; setState($userId, $state); sendMessage($chatId, "⏳ Duration (EN):"); }
        elseif ($step === 'duration_en') { $state['duration_en'] = $text; $state['step'] = 'description_uz'; setState($userId, $state); sendMessage($chatId, "📖 Batafsil ma'lumot (UZ):"); }
        elseif ($step === 'description_uz') { $state['description_uz'] = $text; $state['step'] = 'description_ru'; setState($userId, $state); sendMessage($chatId, "📖 Описание тура (RU):"); }
        elseif ($step === 'description_ru') { $state['description_ru'] = $text; $state['step'] = 'description_en'; setState($userId, $state); sendMessage($chatId, "📖 Description (EN):"); }
        elseif ($step === 'description_en') { $state['description_en'] = $text; $state['step'] = 'image'; setState($userId, $state); sendMessage($chatId, "📸 Turning rasmini yuboring:"); }
        elseif ($step === 'image') {
            $localImg = "";
            if (isset($message['photo'])) {
                $fileId = end($message['photo'])['file_id'];
                $localImg = downloadFile($fileId);
            }
            
            $db = readData();
            $db['tours'][] = [
                'id' => time(),
                'name_uz' => $state['name_uz'], 'name_ru' => $state['name_ru'], 'name_en' => $state['name_en'],
                'price' => $state['price'],
                'duration_uz' => $state['duration_uz'], 'duration_ru' => $state['duration_ru'], 'duration_en' => $state['duration_en'],
                'description_uz' => $state['description_uz'], 'description_ru' => $state['description_ru'], 'description_en' => $state['description_en'],
                'image' => $localImg
            ];
            writeData($db);
            setState($userId, []);
            sendMessage($chatId, "✅ Yangi tur qo'shildi!");
        }
        exit;
    }
}

if (isset($update['callback_query'])) {
    $cb = $update['callback_query'];
    $userId = $cb['from']['id'];
    $chatId = $cb['message']['chat']['id'];
    $data = $cb['data'];

    if (!isAdmin($userId)) exit;
    
    if ($data !== 'logout_admin' && !isSessionAuthorized($userId)) {
        apiRequest('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => '❌ Ruxsat etilmagan seans!']);
        exit;
    }

    if ($data === 'add_tour') {
        setState($userId, ['step' => 'name_uz']);
        sendMessage($chatId, "🇺🇿 Turning nomini kiriting (UZ):");
    } elseif ($data === 'delete_list') {
        $db = readData();
        if (empty($db['tours'])) sendMessage($chatId, "Turlar yo'q.");
        else {
            $kb = ['inline_keyboard' => []];
            foreach ($db['tours'] as $t) $kb['inline_keyboard'][] = [['text' => '❌ ' . $t['name_uz'], 'callback_data' => 'del_' . $t['id']]];
            sendMessage($chatId, "O'chirmoqchi bo'lgan turni tanlang:", $kb);
        }
    } elseif (strpos($data, 'del_') === 0) {
        $id = substr($data, 4);
        $db = readData();
        $db['tours'] = array_values(array_filter($db['tours'], function($t) use ($id) { return (string)$t['id'] !== (string)$id; }));
        writeData($db);
        sendMessage($chatId, "✅ O'chirildi!");
    } elseif ($data === 'view_leads') {
        $db = readData();
        if (empty($db['leads'])) {
            sendMessage($chatId, "Arizalar yo'q.");
        } else {
            // Oxirgi 10 ta arizani bittalab yuboramiz
            $leads = array_slice($db['leads'], -10);
            sendMessage($chatId, "📝 *Oxirgi arizalar:*");
            foreach ($leads as $l) {
                $msg = "👤 *Ism:* {$l['name']}\n📞 *Tel:* {$l['phone']}\n🌍 *Tur:* {$l['tour']}\n📅 *Sana:* " . ($l['date'] ?? 'Noma\'lum');
                $kb = ['inline_keyboard' => [
                    [['text' => '🗑 Ushbu arizani o\'chirish', 'callback_data' => 'del_lead_' . $l['id']]]
                ]];
                sendMessage($chatId, $msg, $kb);
            }
        }
    } elseif (strpos($data, 'del_lead_') === 0) {
        $leadId = substr($data, 9);
        $db = readData();
        $db['leads'] = array_values(array_filter($db['leads'], function($l) use ($leadId) {
            return (string)$l['id'] !== (string)$leadId;
        }));
        writeData($db);
        // Xabarni o'chirib yuboramiz yoki yangilaymiz
        apiRequest('deleteMessage', ['chat_id' => $chatId, 'message_id' => $cb['message']['message_id']]);
        sendMessage($chatId, "✅ Ariza o'chirildi!");
    } elseif ($data === 'clear_leads') {
        $db = readData(); $db['leads'] = []; writeData($db);
        sendMessage($chatId, "✅ Barcha arizalar tozalandi!");
    } elseif ($data === 'stats') {
        $db = readData();
        sendMessage($chatId, "📊 *Statistika:*\n\n🌍 Turlar: " . count($db['tours']) . "\n👤 Users: " . count($db['users']) . "\n📝 Leads: " . count($db['leads']));
    } elseif ($data === 'edit_settings') {
        apiRequest('deleteMessage', ['chat_id' => $chatId, 'message_id' => $cb['message']['message_id']]);
        showContactSettings($chatId);
    } elseif ($data === 'back_to_admin') {
        apiRequest('deleteMessage', ['chat_id' => $chatId, 'message_id' => $cb['message']['message_id']]);
        showAdminPanel($chatId);
    } elseif ($data === 'edit_phone') {
        setState($userId, ['step' => 'set_phone']);
        sendMessage($chatId, "📞 Yangi telefon raqamini kiriting (masalan: `+998 91 965 64 17`):");
    } elseif ($data === 'edit_whatsapp') {
        setState($userId, ['step' => 'set_whatsapp']);
        sendMessage($chatId, "💬 Yangi WhatsApp raqamini kiriting (masalan: `+998 91 965 64 17`):");
    } elseif ($data === 'edit_telegram') {
        setState($userId, ['step' => 'set_telegram']);
        sendMessage($chatId, "✈️ Yangi Telegram usernameni kiriting (masalan: `izlanishcom` yoki `@izlanishcom`):");
    } elseif ($data === 'edit_email') {
        setState($userId, ['step' => 'set_email']);
        sendMessage($chatId, "📧 Yangi email manzilini kiriting (masalan: `izlanishtravel@gmail.com`):");
    } elseif ($data === 'edit_instagram') {
        setState($userId, ['step' => 'set_instagram']);
        sendMessage($chatId, "📸 Yangi Instagram taxallusini kiriting (masalan: `@izlanishcom`):");
    } elseif ($data === 'logout_admin') {
        $sessionFile = __DIR__ . '/admin_sessions.json';
        $sessions = file_exists($sessionFile) ? json_decode(file_get_contents($sessionFile), true) : [];
        $sessions = array_values(array_filter($sessions, function($id) use ($userId) { return (string)$id !== (string)$userId; }));
        file_put_contents($sessionFile, json_encode($sessions));
        
        apiRequest('deleteMessage', ['chat_id' => $chatId, 'message_id' => $cb['message']['message_id']]);
        sendMessage($chatId, "🚪 Siz muvaffaqiyatli chiqdingiz. Admin panelni qayta ochish uchun yana parolni kiritishingiz kerak bo'ladi.");
    }
}
