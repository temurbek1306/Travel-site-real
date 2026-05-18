const { Jimp } = require('jimp');
const fs = require('fs');
const path = require('path');

async function processIFavicon() {
    try {
        const sourcePath = 'C:\\Users\\Temurbek\\.gemini\\antigravity\\brain\\04a9f78c-102e-41b5-9d61-512c13806bff\\green_letter_i_favicon_1778943692470.png';
        const targetPath = 'Logo/favicon.png';

        const image = await Jimp.read(sourcePath);
        
        // Remove white/light-gray background and make it transparent
        image.scan(0, 0, image.bitmap.width, image.bitmap.height, function(x, y, idx) {
            const r = this.bitmap.data[idx + 0];
            const g = this.bitmap.data[idx + 1];
            const b = this.bitmap.data[idx + 2];
            const a = this.bitmap.data[idx + 3];
            
            // If it's nearly white, make it transparent
            if (r > 245 && g > 245 && b > 245) {
                this.bitmap.data[idx + 3] = 0;
            }
        });

        // Crop the transparent bounds tightly
        try {
            image.autocrop();
            console.log(`Cropped dimensions: ${image.bitmap.width}x${image.bitmap.height}`);
        } catch (e) {
            console.log("Autocrop failed, skipping.");
        }
        
        // Save to Logo/favicon.png
        await image.write(targetPath);
        console.log(`Green 'I' favicon processed and saved to ${targetPath}`);
    } catch (e) {
        console.error("Error processing favicon:", e);
    }
}

processIFavicon();
