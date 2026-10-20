import puppeteer from 'puppeteer-core';

const BASE = 'http://127.0.0.1:8000';
const OUT = 'storage/screenshots';
const PAGES = [
  ['landing', '/'],
  ['klinik', '/klinik'],
  ['sekolah', '/sekolah'],
  ['perusahaan', '/perusahaan'],
];

import { mkdir } from 'fs/promises';
await mkdir(OUT, { recursive: true });

const browser = await puppeteer.launch({
  executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  headless: 'new',
});
const page = await browser.newPage();
await page.setViewport({ width: 1440, height: 900 });

for (const [name, path] of PAGES) {
  await page.goto(BASE + path, { waitUntil: 'networkidle0', timeout: 60000 });

  // Full-page screenshots do not naturally scroll through the document, so
  // IntersectionObserver-powered sections would otherwise stay transparent.
  // Wait for visual assets, then capture the fully revealed final state.
  await page.evaluate(async () => {
    await document.fonts.ready;
    await Promise.all(
      [...document.images]
        .filter((image) => !image.complete)
        .map((image) => new Promise((resolve) => {
          image.addEventListener('load', resolve, { once: true });
          image.addEventListener('error', resolve, { once: true });
        })),
    );

    document.documentElement.style.scrollBehavior = 'auto';
    document.querySelectorAll('.reveal').forEach((element) => {
      element.classList.add('in');
      element.style.transition = 'none';
    });
    window.scrollTo(0, 0);
  });

  await new Promise((resolve) => setTimeout(resolve, 250));
  await page.screenshot({ path: `${OUT}/${name}.png`, fullPage: true });
  console.log(`OK ${name}`);
}
await browser.close();
