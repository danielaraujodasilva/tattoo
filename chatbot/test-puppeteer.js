import puppeteer from "puppeteer";

(async () => {
  try {
    const browser = await puppeteer.launch({
      headless: "new",
      args: [
        "--no-sandbox",
        "--disable-setuid-sandbox",
        "--disable-dev-shm-usage",
        "--disable-gpu",
      ],
    });
    const page = await browser.newPage();
    await page.goto("https://www.google.com");
    console.log("Google aberto com sucesso!");
    await browser.close();
  } catch (error) {
    console.error("Erro no Puppeteer:", error);
  }
})();
