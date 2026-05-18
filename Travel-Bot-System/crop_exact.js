const { Jimp } = require('jimp');

async function cropExact() {
    try {
        const image = await Jimp.read('Logo/logo.png');
        
        let minX = image.bitmap.width;
        let minY = image.bitmap.height;
        let maxX = 0;
        let maxY = 0;

        for (let y = 0; y < image.bitmap.height; y++) {
            for (let x = 0; x < image.bitmap.width; x++) {
                const color = image.getPixelColor(x, y);
                const r = (color >> 24) & 255;
                const g = (color >> 16) & 255;
                const b = (color >> 8) & 255;
                const a = color & 255;

                // consider pixel valid if alpha is high and it's not white/light-gray
                if (a > 50 && !(r > 240 && g > 240 && b > 240)) {
                    if (x < minX) minX = x;
                    if (x > maxX) maxX = x;
                    if (y < minY) minY = y;
                    if (y > maxY) maxY = y;
                }
            }
        }
        
        console.log(`Bounding box: x=${minX}, y=${minY}, w=${maxX - minX + 1}, h=${maxY - minY + 1}`);

        // if bounding box is valid
        if (minX <= maxX && minY <= maxY) {
            const width = maxX - minX + 1;
            const height = maxY - minY + 1;
            
            // if we want a square, we can take the max dimension
            const size = Math.max(width, height);
            
            // let's just crop exactly to the globe assuming the globe is the left part.
            // wait, what if the text is also part of the bounding box?
            // The globe is likely a square on the left. So we just crop a square starting from minX, minY, with side length = height.
            const globeSize = height;
            image.crop({ x: minX, y: minY, w: globeSize, h: globeSize });
            console.log(`Cropped to globe: ${globeSize}x${globeSize}`);
            
            await image.write('Logo/favicon.png');
            console.log(`Saved exact cropped favicon.`);
        } else {
            console.log("Could not find bounds.");
        }
    } catch (e) {
        console.error(e);
    }
}
cropExact();
