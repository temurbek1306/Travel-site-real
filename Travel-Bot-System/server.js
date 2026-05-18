require('dotenv').config();
const express = require('express');
const { Telegraf, Markup } = require('telegraf');
const fs = require('fs');
const path = require('path');
const cors = require('cors');
const axios = require('axios');

const app = express();
const bot = new Telegraf(process.env.BOT_TOKEN);
const DATA_FILE = path.join(__dirname, 'data.json');

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static('public'));
app.use('/uploads', express.static(path.join(__dirname, 'public/uploads')));
app.use('/Logo', express.static(path.join(__dirname, 'Logo')));

app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});

// Ensure upload directory exists
const uploadDir = path.join(__dirname, 'public/uploads');
if (!fs.existsSync(uploadDir)){
    fs.mkdirSync(uploadDir, { recursive: true });
}

// Data Helper Functions
function readData() {
    if (!fs.existsSync(DATA_FILE)) {
        return { tours: [], users: [], leads: [], settings: { admin_id: null } };
    }
    const data = fs.readFileSync(DATA_FILE);
    return JSON.parse(data);
}

function writeData(data) {
    fs.writeFileSync(DATA_FILE, JSON.stringify(data, null, 2));
}

async function downloadImage(url, filename) {
    const filePath = path.join(__dirname, 'public/uploads', filename);
    const writer = fs.createWriteStream(filePath);
    const response = await axios({
        url,
        method: 'GET',
        responseType: 'stream'
    });
    response.data.pipe(writer);
    return new Promise((resolve, reject) => {
        writer.on('finish', resolve);
        writer.on('error', reject);
    });
}

// --- API Endpoints ---
app.get('/api/tours', (req, res) => {
    const data = readData();
    res.json(data.tours);
});

app.post('/api/booking', (req, res) => {
    const { name, phone, tour } = req.body;
    const data = readData();
    const newLead = {
        id: Date.now(),
        name,
        phone,
        tour,
        date: new Date().toLocaleString('uz-UZ')
    };
    data.leads.push(newLead);
    writeData(data);
    
    const adminId = process.env.ADMIN_ID;
    if (adminId) {
        bot.telegram.sendMessage(adminId, 
            `⚡️ *Yangi ariza!*\n\n👤 *Mijoz:* ${name}\n📞 *Tel:* ${phone}\n🌍 *Tur:* ${tour}\n📅 *Vaqt:* ${newLead.date}`,
            { parse_mode: 'Markdown' }
        ).catch(err => console.error("Telegramga yuborishda xatolik:", err));
    }
    res.json({ success: true, message: 'Ariza qabul qilindi' });
});

// --- Telegram Bot Logic ---
const userState = {};

const isAdmin = (ctx, next) => {
    if (ctx.from.id.toString() === process.env.ADMIN_ID) return next();
    ctx.reply("Siz admin emassiz! ❌");
};

const showAdminMenu = (ctx) => {
    ctx.reply("🛠 *Admin Panel*", {
        parse_mode: 'Markdown',
        ...Markup.inlineKeyboard([
            [Markup.button.callback('➕ Yangi tur qo\'shish', 'add_tour')],
            [Markup.button.callback('🗑 Turni o\'chirish', 'delete_tour_list')],
            [Markup.button.callback('📝 Arizalarni ko\'rish', 'view_leads')],
            [Markup.button.callback('🗑 Arizalarni tozalash', 'clear_leads')],
            [Markup.button.callback('📊 Statistika', 'stats')]
        ])
    });
};

// 1. Commands & Main Menu
bot.start((ctx) => {
    const data = readData();
    if (!data.users.includes(ctx.from.id)) {
        data.users.push(ctx.from.id);
        writeData(data);
    }
    const isAdminUser = ctx.from.id.toString() === process.env.ADMIN_ID;
    const keyboard = isAdminUser ? [['🌍 Turlarni ko\'rish'], ['🛠 Admin Panel']] : [['🌍 Turlarni ko\'rish']];
    ctx.reply(`Xush kelibsiz!`, Markup.keyboard(keyboard).resize());
});

bot.hears('🛠 Admin Panel', isAdmin, (ctx) => showAdminMenu(ctx));

bot.hears('🌍 Turlarni ko\'rish', (ctx) => {
    const data = readData();
    if (data.tours.length === 0) {
        return ctx.reply("Hozircha turlar mavjud emas. 😔");
    }

    let message = "🌟 *Bizning barcha sayohatlarimiz:*\n\n";
    data.tours.forEach((t, i) => {
        message += `${i + 1}. *${t.name_uz}*\n💰 Narxi: $${t.price}\n📅 Davomiyligi: ${t.duration_uz}\n\n`;
    });
    
    ctx.replyWithMarkdown(message);
});

// 2. Actions (Inline Buttons)
bot.action('add_tour', isAdmin, (ctx) => {
    userState[ctx.from.id] = { step: 'name_uz' };
    ctx.reply("📝 *UZ:* Turning nomini yuboring:");
});

bot.action('delete_tour_list', isAdmin, (ctx) => {
    const data = readData();
    const buttons = data.tours.map(t => [Markup.button.callback(`❌ ${t.name_uz}`, `del_${t.id}`)]);
    buttons.push([Markup.button.callback('⬅️ Orqaga', 'cancel')]);
    ctx.editMessageText("O'chirmoqchi bo'lgan turningizni tanlang:", Markup.inlineKeyboard(buttons));
});

bot.action(/del_(\d+)/, isAdmin, (ctx) => {
    const id = parseInt(ctx.match[1]);
    const data = readData();
    data.tours = data.tours.filter(t => t.id !== id);
    writeData(data);
    ctx.reply("Tur o'chirildi! ✅");
    showAdminMenu(ctx);
});
bot.action('view_leads', isAdmin, (ctx) => {
    const data = readData();
    let msg = "📝 *Arizalar:*\n\n";
    const recentLeads = data.leads.slice(-10);
    if (recentLeads.length === 0) msg += "Hozircha arizalar yo'q.";
    recentLeads.forEach(l => msg += `👤 ${l.name}\n📞 ${l.phone}\n🌍 ${l.tour}\n📅 ${l.date}\n\n`);
    ctx.replyWithMarkdown(msg);
});

bot.action('stats', isAdmin, (ctx) => {
    const data = readData();
    const statsMsg = `📊 *Platforma statistikasi:*\n\n` +
        `🌍 *Turlar soni:* ${data.tours.length} ta\n` +
        `📝 *Jami arizalar:* ${data.leads.length} ta\n` +
        `👤 *Jami foydalanuvchilar:* ${data.users.length} ta`;
    
    ctx.replyWithMarkdown(statsMsg);
});

bot.action('clear_leads', isAdmin, (ctx) => {
    const data = readData();
    data.leads = [];
    writeData(data);
    ctx.reply("Barcha arizalar muvaffaqiyatli o'chirildi! 🗑✅");
    showAdminMenu(ctx);
});

bot.action('cancel', isAdmin, (ctx) => {
    delete userState[ctx.from.id];
    showAdminMenu(ctx);
});

// 3. Text Input (State Machine) - MUST BE AFTER .hears
bot.on('text', (ctx) => {
    const state = userState[ctx.from.id];
    if (!state) return;

    switch(state.step) {
        case 'name_uz':
            state.name_uz = ctx.message.text;
            state.step = 'name_ru';
            ctx.reply("📝 *RU:* Введите название тура:");
            break;
        case 'name_ru':
            state.name_ru = ctx.message.text;
            state.step = 'name_en';
            ctx.reply("📝 *EN:* Enter tour name:");
            break;
        case 'name_en':
            state.name_en = ctx.message.text;
            state.step = 'price';
            ctx.reply("💰 Narxni kiriting (raqamda, masalan: 800):");
            break;
        case 'price':
            state.price = ctx.message.text;
            state.step = 'duration_uz';
            ctx.reply("📅 *UZ:* Davomiyligini yuboring (masalan: 7 kun):");
            break;
        case 'duration_uz':
            state.duration_uz = ctx.message.text;
            state.step = 'duration_ru';
            ctx.reply("📅 *RU:* Введите длительность:");
            break;
        case 'duration_ru':
            state.duration_ru = ctx.message.text;
            state.step = 'duration_en';
            ctx.reply("📅 *EN:* Enter duration:");
            break;
        case 'duration_en':
            state.duration_en = ctx.message.text;
            state.step = 'desc_uz';
            ctx.reply("ℹ️ *UZ:* Tavsifni yuboring:");
            break;
        case 'desc_uz':
            state.desc_uz = ctx.message.text;
            state.step = 'desc_ru';
            ctx.reply("ℹ️ *RU:* Введите описание:");
            break;
        case 'desc_ru':
            state.desc_ru = ctx.message.text;
            state.step = 'desc_en';
            ctx.reply("ℹ️ *EN:* Enter description:");
            break;
        case 'desc_en':
            state.desc_en = ctx.message.text;
            state.step = 'image';
            ctx.reply("🖼 Endi tur uchun rasm yuboring:");
            break;
    }
});

bot.on('photo', isAdmin, async (ctx) => {
    const state = userState[ctx.from.id];
    if (!state || state.step !== 'image') return;

    try {
        const fileId = ctx.message.photo[ctx.message.photo.length - 1].file_id;
        const link = await bot.telegram.getFileLink(fileId);
        const filename = `${Date.now()}.jpg`;
        await downloadImage(link.href, filename);
        
        const data = readData();
        const newTour = {
            id: Date.now(),
            name_uz: state.name_uz, name_ru: state.name_ru, name_en: state.name_en,
            price: state.price,
            duration_uz: state.duration_uz, duration_ru: state.duration_ru, duration_en: state.duration_en,
            description_uz: state.desc_uz, description_ru: state.desc_ru, description_en: state.desc_en,
            image: `/uploads/${filename}`
        };
        data.tours.push(newTour);
        writeData(data);
        delete userState[ctx.from.id];
        ctx.reply("✅ Yangi tur 3 ta tilda muvaffaqiyatli qo'shildi!");
        showAdminMenu(ctx);
    } catch (error) {
        ctx.reply("Xatolik! ❌");
    }
});

// 4. Launch & Error Handling
console.log('Telegram Bot: Ulanishga urinilmoqda...');
bot.launch()
    .then(() => console.log('Telegram Bot: Started ✅'))
    .catch((err) => console.error('Telegram Bot: Error ❌', err));

const PORT = process.env.PORT || 3000;
const server = app.listen(PORT, () => {
    console.log(`Web Server: http://localhost:${PORT} ✅`);
}).on('error', (err) => {
    if (err.code === 'EADDRINUSE') {
        console.error(`Xatolik: ${PORT}-port band! Iltimos, portni bo'shating.`);
    }
});

process.once('SIGINT', () => { bot.stop('SIGINT'); server.close(); });
process.once('SIGTERM', () => { bot.stop('SIGTERM'); server.close(); });
