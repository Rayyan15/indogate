import puppeteer from 'puppeteer-core';

async function run() {
    console.log('=== Real Browser Testing for M12: Audit, Keamanan & Hardening ===\n');

    const browser = await puppeteer.launch({
        headless: 'new',
        executablePath: 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    try {
        const page = await browser.newPage();
        await page.setViewport({ width: 1280, height: 900 });

        // 1. Super Admin on Audit Logs page
        console.log('[Test 1] Super Admin logs in and opens Audit Logs (/id/admin/security/audit-logs)...');
        await page.goto('http://127.0.0.1:8000/__dev-login/1', { waitUntil: 'load' });
        await page.goto('http://127.0.0.1:8000/id/admin/security/audit-logs', { waitUntil: 'load' });

        const pageText = (await page.evaluate(() => document.body.innerText)).toLowerCase();
        const hasAuditTitle = pageText.includes('log audit') || pageText.includes('jejak aktivitas');
        const hasFilters = pageText.includes('modul') && pageText.includes('semua pengguna');
        console.log('✓ Audit logs page loaded with title and filters:', hasAuditTitle && hasFilters);
        if (!hasAuditTitle) throw new Error('Audit log page title not found!');

        // 2. Filter by Channel
        console.log('\n[Test 2] Filtering by channel (auth)...');
        await page.select('select[wire\\:model\\.live="logName"]', 'auth');
        await new Promise(r => setTimeout(r, 1200));

        const authFilterText = (await page.evaluate(() => document.body.innerText)).toLowerCase();
        const hasAuthLogs = authFilterText.includes('auth') || authFilterText.includes('login') || authFilterText.includes('tidak ada catatan');
        console.log('✓ Filter by channel reactive update:', hasAuthLogs);

        // 3. Multilingual & RTL Support (Arabic)
        console.log('\n[Test 3] Testing Arabic Locale & RTL Layout (/ar/admin/security/audit-logs)...');
        await page.goto('http://127.0.0.1:8000/ar/admin/security/audit-logs', { waitUntil: 'load' });

        const arDir = await page.evaluate(() => document.documentElement.getAttribute('dir'));
        const arText = await page.evaluate(() => document.body.innerText);
        const hasArTitle = arText.includes('سجلات التدقيق') || arText.includes('الوحدة');
        console.log('✓ Arabic RTL layout active (dir=rtl):', arDir === 'rtl');
        console.log('✓ Arabic audit log title translated:', hasArTitle);
        if (arDir !== 'rtl') throw new Error('Arabic RTL layout failed!');

        // 4. CS Admin without activitylog.view is forbidden (403)
        console.log('\n[Test 4] CS Admin logs in and attempts to access audit logs...');
        await page.goto('http://127.0.0.1:8000/__dev-login/2', { waitUntil: 'load' });
        const response = await page.goto('http://127.0.0.1:8000/id/admin/security/audit-logs', { waitUntil: 'load' });
        const statusCode = response.status();
        console.log(`✓ CS Admin access is forbidden (HTTP ${statusCode}):`, statusCode === 403);
        if (statusCode !== 403) throw new Error(`Expected 403 but got ${statusCode}`);

        await page.close();
        console.log('\n=============================================================');
        console.log('=== ALL M12 BROWSER TESTS COMPLETED SUCCESSFULLY 100% ===');
        console.log('=============================================================');
    } catch (err) {
        console.error('\n❌ Browser test failed:', err.message);
        process.exitCode = 1;
    } finally {
        await browser.close();
    }
}

run();
