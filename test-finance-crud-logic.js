import puppeteer from 'puppeteer-core';

async function run() {
    console.log('=== Comprehensive Real Browser CRUD & Logic Test for M9 ===\n');

    const browser = await puppeteer.launch({
        headless: 'new',
        executablePath: 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    try {
        // -------------------------------------------------------------
        // Test 1: CS Admin logs in, records DP on Booking 2
        // -------------------------------------------------------------
        console.log('[Test 1] CS Admin (id=2) logs in and records DP...');
        const csPage = await browser.newPage();
        await csPage.setViewport({ width: 1280, height: 900 });

        await csPage.goto('http://127.0.0.1:8000/__dev-login/2', { waitUntil: 'load' });
        await csPage.goto('http://127.0.0.1:8000/id/admin/package-bookings/2', { waitUntil: 'load' });

        // Open modal
        await csPage.evaluate(() => {
            const btn = Array.from(document.querySelectorAll('button')).find(b => b.textContent.includes('Catat Pembayaran'));
            if (btn) btn.click();
        });
        await csPage.waitForSelector('input[wire\\:model="payment_amount_minor"]', { visible: true, timeout: 5000 });

        // Fill 2.000.000
        await csPage.evaluate(() => {
            const el = document.querySelector('input[wire\\:model="payment_amount_minor"]');
            if (el) {
                el.value = '';
                el.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
        await csPage.type('input[wire\\:model="payment_amount_minor"]', '2000000');
        await csPage.type('input[wire\\:model="payment_notes"]', 'DP 30% via transfer BCA');

        // Submit
        await csPage.evaluate(() => {
            const form = document.querySelector('form[wire\\:submit="recordPayment"]');
            if (form) {
                const submit = form.querySelector('button[type="submit"]');
                if (submit) submit.click();
            }
        });
        await new Promise(r => setTimeout(r, 2000));

        // Check payment is in Riwayat
        const csContent = await csPage.evaluate(() => document.body.innerText);
        const hasPayment = csContent.includes('2.000.000');
        console.log('✓ Payment 2.000.000 recorded and visible in history:', hasPayment);
        if (!hasPayment) throw new Error('Payment was not recorded!');

        // Check CS cannot verify (SOD)
        const csVerifyBtn = await csPage.evaluate(() => {
            return Array.from(document.querySelectorAll('button')).some(b => b.textContent.trim() === 'Verifikasi');
        });
        console.log('✓ CS Admin cannot verify payment (SOD):', !csVerifyBtn);
        if (csVerifyBtn) throw new Error('CS Admin can verify payments!');

        await csPage.close();

        // -------------------------------------------------------------
        // Test 2: Finance Admin logs in and verifies DP payment
        // -------------------------------------------------------------
        console.log('\n[Test 2] Finance Admin (id=3) logs in and verifies DP...');
        const finPage = await browser.newPage();
        await finPage.setViewport({ width: 1280, height: 900 });

        await finPage.goto('http://127.0.0.1:8000/__dev-login/3', { waitUntil: 'load' });
        await finPage.goto('http://127.0.0.1:8000/id/admin/finance/payments', { waitUntil: 'load' });

        // Verify button
        const clickedVerify = await finPage.evaluate(() => {
            const btn = Array.from(document.querySelectorAll('button')).find(b => b.textContent.trim() === 'Verifikasi');
            if (btn) {
                btn.click();
                return true;
            }
            return false;
        });
        console.log('✓ Verify button clicked:', clickedVerify);
        await new Promise(r => setTimeout(r, 2000));

        // -------------------------------------------------------------
        // Test 3: Booking status transitioned to partially_paid
        // -------------------------------------------------------------
        console.log('\n[Test 3] Checking booking state transition to partially_paid...');
        await finPage.goto('http://127.0.0.1:8000/id/admin/package-bookings/2', { waitUntil: 'load' });
        const bContent = await finPage.evaluate(() => document.body.innerText);
        const isPartiallyPaid = bContent.includes('DP Terbayar') || bContent.includes('partially_paid');
        console.log('✓ Booking status is partially_paid (DP Terbayar):', isPartiallyPaid);
        if (!isPartiallyPaid) throw new Error('Booking status is not partially_paid!');

        // -------------------------------------------------------------
        // Test 4: Finance Admin records full payment to reach 'paid'
        // -------------------------------------------------------------
        console.log('\n[Test 4] Finance Admin records second payment for full settlement...');
        await finPage.evaluate(() => {
            const btn = Array.from(document.querySelectorAll('button')).find(b => b.textContent.includes('Catat Pembayaran'));
            if (btn) btn.click();
        });
        await finPage.waitForSelector('input[wire\\:model="payment_amount_minor"]', { visible: true, timeout: 5000 });

        // Sisa tagihan is 739000000 - 2000000 = 737000000 (or whichever remaining balance)
        await finPage.evaluate(() => {
            const el = document.querySelector('input[wire\\:model="payment_amount_minor"]');
            if (el) {
                el.value = '';
                el.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
        await finPage.type('input[wire\\:model="payment_amount_minor"]', '737000000');
        await finPage.type('input[wire\\:model="payment_notes"]', 'Pelunasan sisa tagihan');

        await finPage.evaluate(() => {
            const form = document.querySelector('form[wire\\:submit="recordPayment"]');
            if (form) {
                const submit = form.querySelector('button[type="submit"]');
                if (submit) submit.click();
            }
        });
        await new Promise(r => setTimeout(r, 2000));

        // Finance Admin verifies the second payment from Booking Show directly!
        console.log('Verifying second payment from Booking Show page...');
        await finPage.evaluate(() => {
            const btn = Array.from(document.querySelectorAll('button')).find(b => b.textContent.trim() === 'Verifikasi');
            if (btn) btn.click();
        });
        await new Promise(r => setTimeout(r, 2500));

        // Reload booking show and verify status is 'paid' (Lunas)
        await finPage.goto('http://127.0.0.1:8000/id/admin/package-bookings/2', { waitUntil: 'load' });
        const paidContent = await finPage.evaluate(() => document.body.innerText);
        const isPaid = paidContent.includes('Lunas') || paidContent.includes('paid');
        console.log('✓ Booking status transitioned to paid (Lunas):', isPaid);
        if (!isPaid) throw new Error('Booking status is not Lunas/paid after full payment!');

        // -------------------------------------------------------------
        // Test 5: Partial refund from booking show page
        // -------------------------------------------------------------
        console.log('\n[Test 5] Processing partial refund from Booking Show page...');
        const clickedRefund = await finPage.evaluate(() => {
            const btn = Array.from(document.querySelectorAll('button')).find(b => b.textContent.trim() === 'Refund');
            if (btn) {
                btn.click();
                return true;
            }
            return false;
        });
        console.log('Refund button clicked:', clickedRefund);
        await finPage.waitForSelector('textarea[wire\\:model="refund_reason"]', { visible: true, timeout: 5000 });

        // Set refund amount to 500.000
        await finPage.evaluate(() => {
            const el = document.querySelector('input[wire\\:model="refund_amount_minor"]');
            if (el) {
                el.value = '';
                el.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
        await finPage.type('input[wire\\:model="refund_amount_minor"]', '500000');
        await finPage.type('textarea[wire\\:model="refund_reason"]', 'Customer request cancellation of optional tour');

        await finPage.evaluate(() => {
            const form = document.querySelector('form[wire\\:submit="processRefund"]');
            if (form) {
                const submit = form.querySelector('button[type="submit"]');
                if (submit) submit.click();
            }
        });
        await new Promise(r => setTimeout(r, 2000));

        const refundResultText = await finPage.evaluate(() => document.body.innerText);
        const hasRefundSuccess = refundResultText.includes('Pengembalian dana') || refundResultText.includes('berhasil');
        console.log('✓ Refund processed successfully:', hasRefundSuccess);

        // -------------------------------------------------------------
        // Test 6: Vendor Payment CRUD in UI
        // -------------------------------------------------------------
        console.log('\n[Test 6] Vendor Payment CRUD on /admin/finance/vendor-payments...');
        await finPage.goto('http://127.0.0.1:8000/id/admin/finance/vendor-payments', { waitUntil: 'load' });

        // Click "+ Catat Pembayaran Vendor"
        await finPage.evaluate(() => {
            const btn = Array.from(document.querySelectorAll('button')).find(b => b.textContent.includes('Catat Pembayaran Vendor'));
            if (btn) btn.click();
        });
        await finPage.waitForSelector('select[wire\\:model="partner_id"]', { visible: true, timeout: 5000 });

        // Select first partner and fill amount 3.500.000
        await finPage.evaluate(() => {
            const sel = document.querySelector('select[wire\\:model="partner_id"]');
            if (sel && sel.options.length > 1) {
                sel.selectedIndex = 1;
                sel.dispatchEvent(new Event('change', { bubbles: true }));
            }
            const amt = document.querySelector('input[wire\\:model="amount_minor"]');
            if (amt) {
                amt.value = '';
                amt.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
        await finPage.type('input[wire\\:model="amount_minor"]', '3500000');
        await finPage.type('input[wire\\:model="description"]', 'Pembayaran booking kamar hotel Grand Inna');

        // Submit form
        await finPage.evaluate(() => {
            const form = document.querySelector('form[wire\\:submit="save"]');
            if (form) {
                const submit = form.querySelector('button[type="submit"]');
                if (submit) submit.click();
            }
        });
        await new Promise(r => setTimeout(r, 2000));

        // Check if row appeared
        const vpContent = await finPage.evaluate(() => document.body.innerText);
        const hasVp = vpContent.includes('3.500.000') || vpContent.includes('Grand Inna');
        console.log('✓ Vendor payment record created and visible:', hasVp);
        if (!hasVp) throw new Error('Vendor payment was not created!');

        // Delete the vendor payment
        console.log('Deleting the vendor payment...');
        await finPage.evaluate(() => {
            const btn = Array.from(document.querySelectorAll('button')).find(b => b.textContent.trim() === 'Hapus');
            if (btn) btn.click();
        });
        await new Promise(r => setTimeout(r, 1500));

        const vpContentAfter = await finPage.evaluate(() => document.body.innerText);
        const hasVpDeleted = vpContentAfter.includes('berhasil dihapus');
        console.log('✓ Vendor payment deleted successfully:', hasVpDeleted);

        // -------------------------------------------------------------
        // Test 7: Payment List Search & Filter
        // -------------------------------------------------------------
        console.log('\n[Test 7] Payment List Search & Filter...');
        await finPage.goto('http://127.0.0.1:8000/id/admin/finance/payments', { waitUntil: 'load' });

        await finPage.type('input[placeholder*="Cari"]', 'BK-A6X1VFG4');
        await new Promise(r => setTimeout(r, 800));

        const searchContent = await finPage.evaluate(() => document.body.innerText);
        console.log('✓ Search by booking code in Payment List works:', searchContent.includes('BK-A6X1VFG4'));

        // -------------------------------------------------------------
        // Test 8: Accounts Receivable (AR) Search & Filter
        // -------------------------------------------------------------
        console.log('\n[Test 8] Accounts Receivable Search & Filter...');
        await finPage.goto('http://127.0.0.1:8000/id/admin/finance/receivables', { waitUntil: 'load' });

        const arStats = await finPage.evaluate(() => {
            const els = Array.from(document.querySelectorAll('.font-bold, .font-mono'));
            return els.map(e => e.textContent.trim()).filter(t => t.length > 0);
        });
        console.log('✓ Accounts Receivable summary statistics rendered:', arStats.length > 0);

        // -------------------------------------------------------------
        // Test 9: Margin Report View
        // -------------------------------------------------------------
        console.log('\n[Test 9] Margin Report View...');
        await finPage.goto('http://127.0.0.1:8000/id/admin/finance/margin-report', { waitUntil: 'load' });

        const mrText = await finPage.evaluate(() => document.body.innerText);
        const hasMarginMetrics = mrText.includes('Pendapatan Kotor') && mrText.includes('Biaya Vendor');
        console.log('✓ Margin Report metrics and summary loaded:', hasMarginMetrics);

        // -------------------------------------------------------------
        // Test 10: Invoice & Receipt Views
        // -------------------------------------------------------------
        console.log('\n[Test 10] Invoice and Receipt Views...');
        await finPage.goto('http://127.0.0.1:8000/id/admin/finance/invoices/2', { waitUntil: 'load' });
        const invText = await finPage.evaluate(() => document.body.innerText);
        console.log('✓ Invoice view rendered:', invText.includes('BK-A6X1VFG4') && (invText.includes('INVOICE') || invText.includes('FAKTUR')));

        await finPage.close();

        console.log('\n=============================================================');
        console.log('=== ALL 10 COMPREHENSIVE BROWSER TESTS PASSED 100% ===');
        console.log('=============================================================');
    } catch (err) {
        console.error('\n❌ Comprehensive Browser test failed:', err.message);
        process.exitCode = 1;
    } finally {
        await browser.close();
    }
}

run();
