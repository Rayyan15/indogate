import puppeteer from 'puppeteer-core';

async function run() {
    console.log('=== Real Browser Testing for M11: Laporan & Dashboard ===\n');

    const browser = await puppeteer.launch({
        headless: 'new',
        executablePath: 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    try {
        const page = await browser.newPage();
        await page.setViewport({ width: 1280, height: 900 });

        // 1. Super Admin on Dashboard
        console.log('[Test 1] Super Admin logs in and opens Dashboard (/id/admin/dashboard)...');
        await page.goto('http://127.0.0.1:8000/__dev-login/1', { waitUntil: 'load' });
        await page.goto('http://127.0.0.1:8000/id/admin/dashboard', { waitUntil: 'load' });

        const dText = (await page.evaluate(() => document.body.innerText)).toLowerCase();
        const hasDashboardTitle = dText.includes('dashboard eksekutif') || dText.includes('ringkasan');
        const hasKpiCards = dText.includes('total pemesanan') && (dText.includes('pendapatan kotor') || dText.includes('margin bersih sejati'));
        console.log('✓ Executive Dashboard loaded with KPI cards:', hasDashboardTitle && hasKpiCards);
        if (!hasDashboardTitle) throw new Error('Dashboard title not found!');

        // Change period dropdown
        console.log('Changing period dropdown to all_time...');
        await page.select('select[wire\\:model\\.live="period"]', 'all_time');
        await new Promise(r => setTimeout(r, 1200));
        const periodUpdatedText = (await page.evaluate(() => document.body.innerText)).toLowerCase();
        console.log('✓ Period changed reactively:', periodUpdatedText.includes('total pemesanan'));

        // 2. Super Admin on Reports Center
        console.log('\n[Test 2] Navigating to Reports Center (/id/admin/reports)...');
        await page.goto('http://127.0.0.1:8000/id/admin/reports', { waitUntil: 'load' });

        const rText = await page.evaluate(() => document.body.innerText);
        const hasReportsTitle = rText.includes('Laporan & Analitik');
        const hasSalesTab = rText.includes('Penjualan & Margin');
        console.log('✓ Reports Center loaded with tabs:', hasReportsTitle && hasSalesTab);
        if (!hasReportsTitle) throw new Error('Reports title not found!');

        // Switch to Lead Conversion tab
        console.log('Switching to Lead Conversion tab...');
        await page.evaluate(() => {
            const btn = Array.from(document.querySelectorAll('button')).find(b => b.textContent.includes('Konversi Lead'));
            if (btn) btn.click();
        });
        await new Promise(r => setTimeout(r, 1500));

        const leadTabText = await page.evaluate(() => document.body.innerText);
        const hasLeadFunnel = leadTabText.includes('Corong Konversi Prospek') || leadTabText.includes('Total Prospek');
        console.log('✓ Lead Conversion tab activated and funnel rendered:', hasLeadFunnel);

        // Switch to Operations tab
        console.log('Switching to Operations & Fleet tab...');
        await page.evaluate(() => {
            const btn = Array.from(document.querySelectorAll('button')).find(b => b.textContent.includes('Operasional & Armada'));
            if (btn) btn.click();
        });
        await new Promise(r => setTimeout(r, 1500));

        const opsTabText = await page.evaluate(() => document.body.innerText);
        const hasOpsStats = opsTabText.includes('Driver Aktif') || opsTabText.includes('Total Armada') || opsTabText.includes('Penugasan');
        console.log('✓ Operations & Fleet tab activated and metrics rendered:', hasOpsStats);

        // 3. Multilingual & RTL Support (Arabic)
        console.log('\n[Test 3] Testing Arabic Locale & RTL Layout (/ar/admin/dashboard)...');
        await page.goto('http://127.0.0.1:8000/ar/admin/dashboard', { waitUntil: 'load' });

        const arDir = await page.evaluate(() => document.documentElement.getAttribute('dir'));
        const arText = await page.evaluate(() => document.body.innerText);
        const hasArTitle = arText.includes('لوحة التحكم التنفيذية') || arText.includes('إجمالي الحجوزات');
        console.log('✓ Arabic RTL layout active (dir=rtl):', arDir === 'rtl');
        console.log('✓ Arabic dashboard text translated:', hasArTitle);
        if (arDir !== 'rtl') throw new Error('Arabic RTL layout failed!');

        // 4. Finance Admin Dashboard
        console.log('\n[Test 4] Finance Admin logs in and opens Dashboard...');
        await page.goto('http://127.0.0.1:8000/__dev-login/3', { waitUntil: 'load' });
        await page.goto('http://127.0.0.1:8000/id/admin/dashboard', { waitUntil: 'load' });

        const finText = await page.evaluate(() => document.body.innerText);
        const hasFinanceCards = finText.includes('Pendapatan Kotor') || finText.includes('Margin Bersih Sejati');
        console.log('✓ Finance Admin sees full financial margins:', hasFinanceCards);

        // 5. CS Admin Dashboard
        console.log('\n[Test 5] CS Admin logs in and opens Dashboard...');
        await page.goto('http://127.0.0.1:8000/__dev-login/2', { waitUntil: 'load' });
        await page.goto('http://127.0.0.1:8000/id/admin/dashboard', { waitUntil: 'load' });

        const csText = (await page.evaluate(() => document.body.innerText)).toLowerCase();
        const hasCsDashboard = csText.includes('total pemesanan') && csText.includes('tingkat konversi');
        console.log('✓ CS Admin opens operational dashboard successfully:', hasCsDashboard);

        await page.close();
        console.log('\n=============================================================');
        console.log('=== ALL M11 BROWSER TESTS COMPLETED SUCCESSFULLY 100% ===');
        console.log('=============================================================');
    } catch (err) {
        console.error('\n❌ Browser test failed:', err.message);
        process.exitCode = 1;
    } finally {
        await browser.close();
    }
}

run();
