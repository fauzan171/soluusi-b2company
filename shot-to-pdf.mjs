import puppeteer from 'puppeteer-core';
import { execSync } from 'child_process';

const OUT = 'storage/screenshots';
const PAGES = ['landing', 'klinik', 'sekolah', 'perusahaan'];

// 1. PIL slices each full screenshot into A4-ish chunks (no sharp needed)
execSync(`python3 - <<'EOF'
from PIL import Image
PAGE_H = 1754
for n in ['landing','klinik','sekolah','perusahaan']:
    im = Image.open(f'${OUT}/{n}.png')
    w, h = im.size
    for i, top in enumerate(range(0, h, PAGE_H)):
        im.crop((0, top, w, top + PAGE_H)).save(f'${OUT}/{n}_{i}.png')
    print(n, (h + PAGE_H - 1) // PAGE_H, 'slices')
EOF`, { stdio: 'inherit' });

// 2. Chrome print-to-pdf each slice (renders correctly)
const browser = await puppeteer.launch({
  executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  headless: 'new',
});
const slices = execSync(`ls ${OUT}/*_[0-9]*.png | sort`).toString().trim().split('\n');
for (const f of slices) {
  const base = f.replace(/.*\//, '').replace('.png', '');
  const page = await browser.newPage();
  await page.goto(`file://${process.cwd()}/${f}`, { waitUntil: 'networkidle0' });
  const dims = await page.evaluate(() => {
    const img = document.querySelector('img');
    return { w: img.naturalWidth, h: img.naturalHeight };
  });
  await page.addStyleTag({ content: '@page{margin:0}body{margin:0}img{display:block}' });
  await page.pdf({
    path: `${OUT}/${base}.pdf`,
    width: `${dims.w / 96}in`,
    height: `${dims.h / 96}in`,
    printBackground: true,
  });
  await page.close();
}
await browser.close();

// 3. Merge + cleanup
execSync(`python3 - <<'EOF'
from pypdf import PdfWriter
import glob, os
w = PdfWriter()
files = sorted(glob.glob('${OUT}/*_[0-9]*.pdf'),
               key=lambda f: (f.split('/')[-1].split('_')[0], int(f.split('/')[-1].split('_')[-1].split('.')[0])))
for f in files: w.append(f)
with open('docs/Landing-Pages-Review.pdf', 'wb') as out: w.write(out)
print(f'merged {len(w.pages)} pages')
for f in glob.glob('${OUT}/*_[0-9]*.png') + files: os.remove(f)
EOF`, { stdio: 'inherit' });
console.log('done');
