const { Jimp } = require('jimp');

async function cropFavicon() {
    try {
        const image = await Jimp.read('Logo/logo.png');
        console.log(`Original dimensions: ${image.bitmap.width}x${image.bitmap.height}`);
        
        // Remove white background and make it transparent just in case
        image.scan(0, 0, image.bitmap.width, image.bitmap.height, function(x, y, idx) {
            const r = this.bitmap.data[idx + 0];
            const g = this.bitmap.data[idx + 1];
            const b = this.bitmap.data[idx + 2];
            
            if (r > 240 && g > 240 && b > 240) {
                this.bitmap.data[idx + 3] = 0; // set alpha to 0
            }
        });

        // Crop the transparent bounds
        try {
            image.autocrop();
            console.log(`Cropped dimensions: ${image.bitmap.width}x${image.bitmap.height}`);
        } catch (e) {
            console.log("Autocrop failed or not supported, skipping.", e);
        }
        
        // Save as favicon.png
        await image.write('Logo/favicon.png');
        console.log(`Processed favicon saved as Logo/favicon.png.`);
    } catch (e) {
        console.error(e);
    }
}
cropFavicon();
