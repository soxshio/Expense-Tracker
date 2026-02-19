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
});
