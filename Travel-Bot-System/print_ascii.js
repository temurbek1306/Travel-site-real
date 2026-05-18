const { Jimp } = require('jimp');

async function printAscii() {
    try {
        const image = await Jimp.read('Logo/favicon.png');
        image.resize({ w: 40, h: 40 });
        
        let ascii = '';
        for (let y = 0; y < image.bitmap.height; y++) {
            let row = '';
            for (let x = 0; x < image.bitmap.width; x++) {
                const color = image.getPixelColor(x, y);
                const r = (color >> 24) & 255;
                const g = (color >> 16) & 255;
                const b = (color >> 8) & 255;
                const a = color & 255;
                
                if (a < 128 || (r > 240 && g > 240 && b > 240)) {
                    row += ' ';
                } else {
                    row += '#';
                }
            }
            ascii += row + '\n';
        }
        console.log(ascii);
    } catch (e) {
        console.error(e);
    }
}
printAscii();
