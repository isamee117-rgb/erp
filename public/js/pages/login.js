(function () {
    var baseMeta = document.querySelector('meta[name="base-url"]');
    var BASE_URL = baseMeta ? baseMeta.getAttribute('content') : '';
    var form = document.getElementById('login-form');
    var errorEl = document.getElementById('login-error');
    var errorText = document.getElementById('login-error-text');
    var btn = document.getElementById('login-btn');
    var btnText = document.getElementById('login-btn-text');
    var btnSpinner = document.getElementById('login-btn-spinner');
    var toggleBtn = document.getElementById('toggle-password');
    var passInput = document.getElementById('login-password');
    var toggleIcon = document.getElementById('toggle-password-icon');

    toggleBtn.addEventListener('click', function () {
        if (passInput.type === 'password') {
            passInput.type = 'text';
            toggleIcon.className = 'ti ti-eye-off';
        } else {
            passInput.type = 'password';
            toggleIcon.className = 'ti ti-eye';
        }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        errorEl.classList.add('d-none');
        btn.disabled = true;
        btnText.classList.add('d-none');
        btnSpinner.classList.remove('d-none');

        var username = document.getElementById('login-username').value.trim();
        var password = passInput.value;

        fetch(BASE_URL + '/api/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ username: username, password: password })
        })
        .then(function (res) {
            return res.json().then(function (data) { return { ok: res.ok, data: data }; });
        })
        .then(function (result) {
            if (result.ok && result.data.token) {
                localStorage.setItem('leanerp_token', result.data.token);
                localStorage.setItem('leanerp_user', JSON.stringify(result.data.user));
                var expires = new Date(Date.now() + 8 * 60 * 60 * 1000).toUTCString();
                var cookiePath = BASE_URL ? (new URL(BASE_URL)).pathname : '/';
                if (!cookiePath.endsWith('/')) cookiePath += '/';
                document.cookie = 'leanerp_token=' + encodeURIComponent(result.data.token) + '; expires=' + expires + '; path=' + cookiePath + '; SameSite=Lax';
                window.location.href = BASE_URL + '/dashboard';
            } else {
                throw new Error(result.data.message || result.data.error || 'Authentication failed');
            }
        })
        .catch(function (err) {
            errorText.textContent = err.message || 'Authentication failed';
            errorEl.classList.remove('d-none');
        })
        .finally(function () {
            btn.disabled = false;
            btnText.classList.remove('d-none');
            btnSpinner.classList.add('d-none');
        });
    });

    if (localStorage.getItem('leanerp_token')) {
        var existingToken = localStorage.getItem('leanerp_token');
        var cookiePath = BASE_URL ? (new URL(BASE_URL)).pathname : '/';
        if (!cookiePath.endsWith('/')) cookiePath += '/';
        var expires = new Date(Date.now() + 8 * 60 * 60 * 1000).toUTCString();
        document.cookie = 'leanerp_token=' + encodeURIComponent(existingToken) + '; expires=' + expires + '; path=' + cookiePath + '; SameSite=Lax';
        window.location.href = BASE_URL + '/dashboard';
    }
})();
