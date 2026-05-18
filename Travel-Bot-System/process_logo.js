const { Jimp } = require('jimp');

async function processLogo() {
    try {
        const image = await Jimp.read('Logo/logo.png');
        console.log(`Original dimensions: ${image.bitmap.width}x${image.bitmap.height}`);
        
        // Replace all near-white pixels with transparent
        image.scan(0, 0, image.bitmap.width, image.bitmap.height, function(x, y, idx) {
            const r = this.bitmap.data[idx + 0];
            const g = this.bitmap.data[idx + 1];
            const b = this.bitmap.data[idx + 2];
            
            if (r > 240 && g > 240 && b > 240) {
                this.bitmap.data[idx + 3] = 0;
            }
        });

        const newHeight = Math.floor(image.bitmap.height * 0.75);
        image.crop({ x: 0, y: 0, w: image.bitmap.width, h: newHeight });
        
        try {
            image.autocrop();
        } catch (e) {
            console.log("Autocrop failed or not supported, skipping.");
        }
        
        await image.write('Logo/logo_clean.png');
        console.log(`Processed logo saved as logo_clean.png.`);
    } catch (e) {
        console.error(e);
    }
}
processLogo();
