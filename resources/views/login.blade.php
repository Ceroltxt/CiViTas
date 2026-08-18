<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — {{ config('app.name') }}</title>
    <style>
        :root {
            --bg: #f4f6f9;
            --card: #fff;
            --text: #1a1d23;
            --muted: #5c6573;
            --border: #e2e6ed;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --danger: #dc2626;
            --success: #059669;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .card {
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }
        .badge {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--muted);
        }
        h1 { margin: 0.25rem 0; font-size: 1.5rem; }
        .subtitle { color: var(--muted); font-size: 0.9rem; margin-bottom: 1.5rem; }
        label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.35rem; }
        input {
            width: 100%;
            padding: 0.65rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        button {
            width: 100%;
            padding: 0.7rem;
            border: none;
            border-radius: 8px;
            background: var(--primary);
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover { background: var(--primary-hover); }
        button:disabled { opacity: 0.6; }
        .btn-secondary {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--border);
            margin-top: 0.75rem;
        }
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .alert-error { background: #fef2f2; color: var(--danger); border: 1px solid #fecaca; }
        .alert-success { background: #ecfdf5; color: var(--success); border: 1px solid #a7f3d0; }
        .hidden { display: none; }
        .profile {
            margin-top: 1rem;
            padding: 1rem;
            background: var(--bg);
            border-radius: 8px;
            font-size: 0.875rem;
            line-height: 1.6;
        }
        .token-box {
            margin-top: 1rem;
            padding: 0.75rem;
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.75rem;
            word-break: break-all;
        }
        .hint {
            margin-top: 1rem;
            font-size: 0.8rem;
            color: var(--muted);
            background: var(--bg);
            padding: 0.75rem;
            border-radius: 8px;
        }
        a { color: var(--primary); }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">Dev / teste</span>
        <h1>{{ config('app.name') }}</h1>
        <p class="subtitle">Teste de <code>POST /api/auth/login</code></p>

        <div id="error" class="alert alert-error hidden"></div>
        <div id="success" class="alert alert-success hidden"></div>

        <form id="login-form">
            <label for="email">E-mail</label>
            <input id="email" type="email" required value="ana@civitas.test">

            <label for="password">Senha</label>
            <input id="password" type="password" required value="secret-password">

            <button type="submit" id="submit-btn">Entrar</button>
        </form>



        <div id="logged-in" class="hidden">
            <div class="profile" id="profile"></div>
            <div class="token-box" id="token-display"></div>
            <button type="button" class="btn-secondary" id="logout-btn">Logout</button>
            <button type="button" class="btn-secondary" id="me-btn">Testar GET /api/auth/me</button>
        </div>

        <p style="margin-top:1rem;font-size:0.8rem;color:var(--muted);text-align:center;">
            <a href="{{ url('/api/status') }}">/api/status</a>
        </p>
    </div>

    <script>
        const TOKEN_KEY = 'civitas_dev_token';
        const form = document.getElementById('login-form');
        const loggedIn = document.getElementById('logged-in');
        const errorEl = document.getElementById('error');
        const successEl = document.getElementById('success');
        const submitBtn = document.getElementById('submit-btn');
        const profileEl = document.getElementById('profile');
        const tokenEl = document.getElementById('token-display');

        function showError(msg) {
            errorEl.textContent = msg;
            errorEl.classList.remove('hidden');
            successEl.classList.add('hidden');
        }
        function showSuccess(msg) {
            successEl.textContent = msg;
            successEl.classList.remove('hidden');
            errorEl.classList.add('hidden');
        }
        function clearAlerts() {
            errorEl.classList.add('hidden');
            successEl.classList.add('hidden');
        }
        function renderProfile(f) {
            profileEl.innerHTML = `<strong>${f.nome} ${f.sobrenome}</strong><br>${f.email}<br>Dept: ${f.departamento?.nome ?? '—'}<br>Cargo: ${f.cargo?.nome ?? '—'}`;
        }
        function showLoggedIn(token, funcionario) {
            form.classList.add('hidden');
            loggedIn.classList.remove('hidden');
            tokenEl.textContent = token;
            renderProfile(funcionario);
            showSuccess('Login OK! Token salvo.');
        }
        function showLoggedOut() {
            form.classList.remove('hidden');
            loggedIn.classList.add('hidden');
            clearAlerts();
        }

        async function restoreSession() {
            const token = localStorage.getItem(TOKEN_KEY);
            if (!token) return;
            try {
                const r = await fetch('{{ url('/api/auth/me') }}', {
                    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
                });
                if (!r.ok) { localStorage.removeItem(TOKEN_KEY); return; }
                const data = await r.json();
                showLoggedIn(token, data.data ?? data);
            } catch { localStorage.removeItem(TOKEN_KEY); }
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearAlerts();
            submitBtn.disabled = true;
            try {
                const r = await fetch('{{ url('/api/auth/login') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({
                        email: document.getElementById('email').value,
                        password: document.getElementById('password').value,
                    }),
                });
                const data = await r.json();
                if (!r.ok) {
                    showError(data.message ?? data.errors?.email?.[0] ?? 'Credenciais inválidas.');
                    return;
                }
                localStorage.setItem(TOKEN_KEY, data.token);
                showLoggedIn(data.token, data.funcionario);
            } catch {
                showError('Erro de rede. API não respondeu.');
            } finally {
                submitBtn.disabled = false;
            }
        });

        document.getElementById('logout-btn').onclick = async () => {
            const token = localStorage.getItem(TOKEN_KEY);
            if (token) {
                await fetch('{{ url('/api/auth/logout') }}', {
                    method: 'POST',
                    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
                });
            }
            localStorage.removeItem(TOKEN_KEY);
            showLoggedOut();
            showSuccess('Logout OK.');
        };

        document.getElementById('me-btn').onclick = async () => {
            const token = localStorage.getItem(TOKEN_KEY);
            if (!token) return showError('Faça login primeiro.');
            const r = await fetch('{{ url('/api/auth/me') }}', {
                headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
            });
            const data = await r.json();
            if (!r.ok) return showError(data.message ?? 'Erro em /me');
            renderProfile(data.data ?? data);
            showSuccess('GET /api/auth/me OK.');
        };

        restoreSession();
    </script>
</body>
</html>
