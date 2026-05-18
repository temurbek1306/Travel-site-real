# 🚀 IZLANISH TRAVEL — PHP Versiyasi: Deploy Qo'llanmasi

## 📁 Papkadagi Fayllar

```
php-version/
├── index.html          ← Asosiy veb-sahifa (API URL o'zgartirilgan)
├── api.php             ← REST API (turlar + ariza qabul)
├── webhook.php         ← Telegram Bot webhook handler
├── setup_webhook.php   ← Webhookni bir marta o'rnatish uchun
├── data.json           ← Ma'lumotlar bazasi (turlar, arizalar)
├── user_states.json    ← Bot holatlari (auto yaratiladi)
├── .htaccess           ← Apache URL routing
└── uploads/            ← Bot orqali yuklangan rasmlar (auto yaratiladi)
```

---

## ⚙️ 1-QADAM: TOKEN VA ID KIRITISH

**`webhook.php`** va **`api.php`** fayllarida bu 2 ta qatorni o'zgartiring:

```php
define('BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');   // BotFatherdan
define('ADMIN_ID',  'YOUR_ADMIN_ID_HERE');    // Sizning Telegram ID
```

> **Telegram ID ni qanday bilish?**  
> `@userinfobot` botiga `/start` yuboring — u sizning ID raqamingizni ko'rsatadi.

---

## 📤 2-QADAM: SERVERGA FAYLLLARNI YUKLASH

ISPmanager → **File Manager** orqali sayt papkasiga bu fayllarni yuklang:

| Fayl | Qayerga yuklash |
|------|----------------|
| `index.html` | `public_html/` yoki `www/` papkasiga |
| `api.php` | `public_html/` yoki `www/` papkasiga |
| `webhook.php` | `public_html/` yoki `www/` papkasiga |
| `setup_webhook.php` | `public_html/` yoki `www/` papkasiga |
| `data.json` | `public_html/` yoki `www/` papkasiga |
| `user_states.json` | `public_html/` yoki `www/` papkasiga |
| `.htaccess` | `public_html/` yoki `www/` papkasiga |
| `Logo/` papkasi | `public_html/` yoki `www/` papkasiga |

---

## 🔐 3-QADAM: FAYL HUQUQLARINI SOZLASH

ISPmanager → File Manager da quyidagi fayllarni **tanlang → "Rights" tugmasini bosing → `644` o'rnating**:

```
data.json          → 644 (o'qish + yozish)
user_states.json   → 644 (o'qish + yozish)
```

`uploads/` papkasini yarating va huquqini **`755`** qiling:
```
uploads/  →  755
```

---

## 🤖 4-QADAM: WEBHOOK O'RNATISH

`setup_webhook.php` faylini **brauzerda bir marta oching**:

```
https://sizning-domen.uz/setup_webhook.php
```

✅ `Webhook muvaffaqiyatli o'rnatildi!` xabarini ko'rsangiz tayyor!

> ⚠️ **Muhim:** `setup_webhook.php` faylini ishlatgandan keyin **o'chirib tashlang** (xavfsizlik uchun).

---

## ✅ 5-QADAM: TEKSHIRISH

### Veb-sayt ishlashini tekshirish:
```
https://sizning-domen.uz/
```
Turlar ro'yxati ko'rinishi kerak.

### API ishlashini tekshirish:
```
https://sizning-domen.uz/api/tours
```
JSON formatida turlar ko'rinishi kerak.

### Botni tekshirish:
Telegram botingizga `/start` yuboring → Tugmalar ko'rinishi kerak.

---

## ❗ TEZKOR MUAMMOLAR

| Muammo | Yechim |
|--------|--------|
| Turlar ko'rinmaydi | `data.json` fayli bor-yo'qligini tekshiring |
| Bot javob bermaydi | `webhook.php` da `BOT_TOKEN` to'g'ri kiritilganmi? |
| Rasm saqlanmaydi | `uploads/` papkasi huquqi `755` bo'lishi kerak |
| 500 xatoligi | `api.php` / `webhook.php` da token to'g'ri kiritilganmi? |
| Ariza Telegramga kelmaydi | `ADMIN_ID` to'g'ri kiritilganmi? |

---

## 🆚 Node.js vs PHP — Farqlar

| | Node.js (eski) | PHP (yangi) |
|---|---|---|
| Server | VPS/Node server kerak | Oddiy shared hosting |
| Bot usuli | Long polling | Webhook |
| Ma'lumot saqlash | `data.json` | `data.json` (bir xil) |
| Holat saqlash | RAM (xotirada) | `user_states.json` faylida |
| Deploy | `node server.js` | Fayl yuklash kifoya |

---

> 💡 **Eslatma:** PHP versiyasi `data.json` fayl formatini to'liq saqlab qolgan — eski ma'lumotlar (turlar, arizalar) hech o'zgarmaydi!
