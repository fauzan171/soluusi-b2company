import puppeteer from 'puppeteer-core';
import { mkdir } from 'fs/promises';

const BASE = 'http://127.0.0.1:8000';
const OUT = 'storage/screenshots/readable';
const PAGES = [
  ['01-landing-utama', '/'],
  ['02-klinik', '/klinik'],
  ['03-sekolah', '/sekolah'],
  ['04-perusahaan', '/perusahaan'],
];

await mkdir(OUT, { recursive: true });

const browser = await puppeteer.launch({
  executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  headless: 'new',
});

for (const [name, path] of PAGES) {
  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 1000, deviceScaleFactor: 1 });
  await page.goto(BASE + path, { waitUntil: 'networkidle0', timeout: 60000 });
  await page.emulateMediaType('screen');

  await page.evaluate(async () => {
    await document.fonts.ready;
    await Promise.all([...document.images].map(async (image) => {
      if (!image.complete) {
        await new Promise((resolve) => {
          image.addEventListener('load', resolve, { once: true });
          image.addEventListener('error', resolve, { once: true });
        });
      }
      if (image.decode) await image.decode().catch(() => {});
    }));

    document.documentElement.style.scrollBehavior = 'auto';
    document.querySelectorAll('.reveal').forEach((element) => {
      element.classList.add('in');
      element.style.transition = 'none';
      element.style.animation = 'none';
    });

    const nav = document.querySelector('body > nav');
    if (nav) nav.style.position = 'absolute';
  });

  const pageOut = `${OUT}/${name}`;
  await mkdir(pageOut, { recursive: true });
  const sections = await page.$$('body > header, body > section, body > footer');

  for (let index = 0; index < sections.length; index += 1) {
    const number = String(index + 1).padStart(2, '0');
    const kind = index === 0 ? 'hero' : index === sections.length - 1 ? 'footer' : 'section';
    await sections[index].screenshot({ path: `${pageOut}/${number}-${kind}.png` });
  }

  await page.pdf({
    path: `${OUT}/${name}.pdf`,
    format: 'A4',
    printBackground: true,
    margin: { top: '8mm', right: '6mm', bottom: '8mm', left: '6mm' },
  });

  console.log(`OK ${name}: ${sections.length} PNG sections + PDF`);
  await page.close();
}

await browser.close();
