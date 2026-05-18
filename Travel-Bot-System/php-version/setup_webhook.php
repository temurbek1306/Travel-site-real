<?php
// ============================================================
//  WEBHOOK O'RNATISH SKRIPTI
//  Brauzerdan bir marta ochib tashlang:
//  https://sizning-domen.uz/setup_webhook.php
// ============================================================

// O'zingizning ma'lumotlaringizni kiriting:
$BOT_TOKEN  = '8680250666:AAGKVSraY_sd4IzrZ7SIpjZLECODxfPJ28Y';
$DOMAIN     = 'https://izlanish.com';  // ✅ To'g'ri domen nomi (izlanish.com)

$webhookUrl = $DOMAIN . '/webhook.php';
$apiUrl     = "https://api.telegram.org/bot{$BOT_TOKEN}/setWebhook";

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode(['url' => $webhookUrl]),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

echo '<pre>';
echo "Webhook URL: " . $webhookUrl . "\n\n";
echo "Javob:\n";
print_r($result);
echo '</pre>';

if (!empty($result['ok'])) {
    echo '<p style="color:green;font-size:20px;">✅ Webhook muvaffaqiyatli o\'rnatildi!</p>';
} else {
    echo '<p style="color:red;font-size:20px;">❌ Xatolik: ' . ($result['description'] ?? 'Noma\'lum xato') . '</p>';
}
