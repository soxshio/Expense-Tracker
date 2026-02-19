// Simple interactivity for dashboard (placeholder)
document.addEventListener('DOMContentLoaded', function(){
    const addBtn = document.getElementById('addBtn');
    const form = document.getElementById('addForm');
    if(addBtn && form){
        addBtn.addEventListener('click', function(e){
            e.preventDefault();
            form.classList.toggle('hidden');
        });
    }

    // show/hide password toggle
    const toggle = document.getElementById('togglePassword');
    if (toggle) {
        toggle.addEventListener('click', function(){
            const pwd = document.getElementById('password');
            if (pwd.type === 'password') { pwd.type = 'text'; toggle.textContent = 'Hide'; }
            else { pwd.type = 'password'; toggle.textContent = 'Show'; }
        });
    }

    // (forget-device feature removed)
});
    // Inline edit for transactions
    document.querySelectorAll('.editBtn').forEach(function(btn){
        btn.addEventListener('click', function(){
            const row = btn.closest('tr');
            if (!row) return;
            const id = row.dataset.id;
            if (row.classList.contains('editing')) return;
            row.classList.add('editing');

            const cells = {
                date: row.querySelector('.cell-date'),
                type: row.querySelector('.cell-type'),
                category: row.querySelector('.cell-category'),
                note: row.querySelector('.cell-note'),
                amount: row.querySelector('.cell-amount'),
                actions: row.querySelector('.cell-actions')
            };

            const orig = {
                date: cells.date.textContent.trim(),
                type: cells.type.textContent.trim(),
                category: cells.category.textContent.trim(),
                note: cells.note.textContent.trim(),
                amount: cells.amount.textContent.trim()
            };

            cells.date.innerHTML = `<input type="date" value="${orig.date.split(' ')[0] || ''}">`;
            cells.type.innerHTML = `<select><option value="income">Income</option><option value="expense">Expense</option></select>`;
            cells.type.querySelector('select').value = orig.type;
            cells.category.innerHTML = `<input type="text" value="${orig.category}">`;
            cells.note.innerHTML = `<input type="text" value="${orig.note}">`;
            cells.amount.innerHTML = `<input type="number" step="0.01" value="${orig.amount.replace(/,/g,'')}">`;

            cells.actions.innerHTML = `<button class="btn saveBtn" style="background:#10b981">Save</button> <button class="btn cancelBtn" style="background:#ef4444">Cancel</button>`;

            const saveBtn = cells.actions.querySelector('.saveBtn');
            const cancelBtn = cells.actions.querySelector('.cancelBtn');

            cancelBtn.addEventListener('click', function(){
                cells.date.textContent = orig.date;
                cells.type.textContent = orig.type;
                cells.category.textContent = orig.category;
                cells.note.textContent = orig.note;
                cells.amount.textContent = orig.amount;
                cells.actions.innerHTML = `<button class="btn editBtn" data-id="${id}" style="background:#f97316;padding:6px 8px;font-size:13px">Edit</button>`;
                row.classList.remove('editing');
                cells.actions.querySelector('.editBtn').addEventListener('click', function(){ btn.click(); });
            });

            saveBtn.addEventListener('click', function(){
                const payload = new URLSearchParams();
                payload.append('id', id);
                payload.append('date', cells.date.querySelector('input').value);
                payload.append('type', cells.type.querySelector('select').value);
                payload.append('category', cells.category.querySelector('input').value);
                payload.append('note', cells.note.querySelector('input').value);
                payload.append('amount', cells.amount.querySelector('input').value);

                saveBtn.disabled = true;
                fetch('update_transaction.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: payload.toString(),
                    credentials: 'same-origin'
                }).then(r => r.json()).then(data => {
                    if (data.ok && data.transaction) {
                        const tr = data.transaction;
                        cells.date.textContent = tr.transaction_date;
                        cells.type.textContent = tr.type;
                        cells.category.textContent = tr.category;
                        cells.note.textContent = tr.note || '';
                        cells.amount.textContent = parseFloat(tr.amount).toFixed(2);
                        cells.actions.innerHTML = `<button class="btn editBtn" data-id="${id}" style="background:#f97316;padding:6px 8px;font-size:13px">Edit</button>`;
                    } else {
                        alert(data.error || 'Failed to update');
                    }
                }).catch(err => { alert('Network error'); }).finally(()=>{ row.classList.remove('editing'); });
            });
        });
    });

    // Confirm logout (header or sidebar)
    ['logoutBtn','sidebarLogout'].forEach(function(id){
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', function(e){ if (!confirm('Are you sure you want to log out?')) e.preventDefault(); });
    });

    // Sidebar toggle behavior
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarAdd = document.getElementById('sidebarAdd');
    if (sidebar && sidebarToggle) {
        // restore saved state
        try { if (localStorage.getItem('sidebarCollapsed') === 'false') sidebar.classList.remove('collapsed'); } catch(e){}
        sidebarToggle.addEventListener('click', function(){
            sidebar.classList.toggle('collapsed');
            try { localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed')); } catch(e){}
        });
    }
    if (sidebarAdd) {
        sidebarAdd.addEventListener('click', function(e){ e.preventDefault(); const addBtn = document.getElementById('addBtn'); if (addBtn) addBtn.click(); });
    }

    // Live update: fetch trashed count from server periodically
    function updateTrashCount(){
        fetch('get_trash_count.php', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                const badge = document.getElementById('trashCount');
                if (!badge) return;
                badge.textContent = (data && typeof data.count === 'number') ? data.count : badge.textContent;
            }).catch(()=>{});
    }
    // initial fetch
    updateTrashCount();
    // poll every 30s
    setInterval(updateTrashCount, 30000);

    // Delete (soft-delete) transaction: delegated handler
    document.addEventListener('click', function(e){
        const btn = e.target.closest && e.target.closest('.deleteBtn');
        if (!btn) return;
        e.preventDefault();
        if (!confirm('Move this transaction to Trash? It will be permanently deleted after 30 days.')) return;
        const row = btn.closest('tr');
        if (!row) return;
        const id = row.dataset.id;
        // capture type and amount before removing the row
        const typeCell = row.querySelector('.cell-type');
        const amountCell = row.querySelector('.cell-amount');
        const type = typeCell ? typeCell.textContent.trim().toLowerCase() : '';
        const amount = amountCell ? (parseFloat(amountCell.textContent.replace(/,/g,'')) || 0) : 0;

        btn.disabled = true;
        const payload = new URLSearchParams();
        payload.append('id', id);
        fetch('soft_delete_transaction.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: payload.toString(),
            credentials: 'same-origin'
        }).then(r => r.json()).then(data => {
            if (data.ok) {
                // update totals on the page
                function parseDisplayed(el) {
                    if (!el) return 0;
                    return parseFloat(el.textContent.replace(/,/g,'')) || 0;
                }
                function formatCurrency(n) {
                    return n.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
                }
                const incomeEl = document.getElementById('totalIncome');
                const expenseEl = document.getElementById('totalExpense');
                const balanceEl = document.getElementById('balance');
                let incomeVal = parseDisplayed(incomeEl);
                let expenseVal = parseDisplayed(expenseEl);
                if (type === 'income') incomeVal = Math.max(0, incomeVal - amount);
                else expenseVal = Math.max(0, expenseVal - amount);
                const balanceVal = incomeVal - expenseVal;
                if (incomeEl) incomeEl.textContent = formatCurrency(incomeVal);
                if (expenseEl) expenseEl.textContent = formatCurrency(expenseVal);
                if (balanceEl) balanceEl.textContent = formatCurrency(balanceVal);

                // decrement trash count badge if present
                const trashBadge = document.getElementById('trashCount');
                if (trashBadge) {
                    const cur = parseInt(trashBadge.textContent||'0') || 0;
                    trashBadge.textContent = Math.max(0, cur + 1);
                }

                // remove row from dashboard
                row.remove();
            } else {
                alert(data.error || 'Failed to delete');
            }
        }).catch(err => { alert('Network error'); }).finally(()=>{ btn.disabled = false; });
    });
