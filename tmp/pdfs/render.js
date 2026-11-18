import { chromium } from 'file:///C:/Program%20Files/WindowsApps/OpenAI.CodexPrimaryRuntime.v26-1110-973-0_26.1110.973.0_x64__3k8sg7r9htsxt/dependencies/node/node_modules/playwright/index.mjs';
import path from 'path';

(async () => {
  const browser = await chromium.launch({ headless: true, channel: 'msedge' });
  const page = await browser.newPage({ viewport: { width: 1280, height: 1800 }, deviceScaleFactor: 1 });
  await page.goto('file://' + path.resolve('tmp/pdfs/system-summary.html').replace(/\\/g, '/'));
  await page.pdf({
    path: path.resolve('output/pdf/system-summary-ar.pdf'),
    format: 'A4',
    printBackground: true,
    preferCSSPageSize: true,
    displayHeaderFooter: false,
  });
  await browser.close();
})();
