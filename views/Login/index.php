<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/png" href="../../assets/favicon.ico">

    <title>Acceso — Sistema de Incidentes</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/main.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef2f7;
            font-family: Arial, sans-serif;
        }

        .login-card {
            background: #fff;
            width: 360px;
            box-shadow: 0 4px 24px rgba(0,0,0,.13);
            border-top: 4px solid #1a4d6d;
        }

        .login-card-header {
            padding: 28px 32px 16px;
            text-align: center;
        }
        .login-card-header h2 {
            margin: 0 0 4px;
            font-size: 17px;
            font-weight: 600;
            color: #1a4d6d;
        }
        .login-card-header p {
            margin: 0;
            font-size: 11px;
            color: #888;
        }

        .login-card-body { padding: 16px 32px 28px; }

        .form-group { margin-bottom: 14px; }
        .form-group label {
            display: block;
            font-size: 11px;
            color: #555;
            margin-bottom: 5px;
            font-weight: bold;
            letter-spacing: .3px;
        }
        .form-group input {
            width: 100%;
            padding: 9px 11px;
            border: 1px solid #ccc;
            font-size: 13px;
            outline: none;
            transition: border-color .15s;
        }
        .form-group input:focus { border-color: #1a4d6d; }

        /* campo contraseña con botón mostrar/ocultar */
        .pw-wrap { position: relative; }
        .pw-wrap input { padding-right: 38px; }
        .pw-toggle {
            position: absolute;
            right: 10px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer; font-size: 14px;
            color: #888; padding: 0; line-height: 1;
        }
        .pw-toggle:hover { color: #1a4d6d; }

        .btn-submit {
            width: 100%;
            padding: 10px;
            background: #1a4d6d;
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: .3px;
            transition: background .15s;
            margin-top: 4px;
        }
        .btn-submit:hover:not(:disabled) { background: #245f85; }
        .btn-submit:disabled { background: #8eafc2; cursor: not-allowed; }

        /* ── Alertas ─────────────────────────────────────────────── */
        .alert {
            font-size: 12px;
            padding: 9px 12px;
            margin-bottom: 14px;
            border-left: 3px solid;
            display: flex;
            align-items: flex-start;
            gap: 7px;
        }
        .alert-error   { background: #fdf0f0; border-color: #d9534f; color: #7a1f1f; }
        .alert-success { background: #f0fdf4; border-color: #3a9e5f; color: #1a4a2e; }
        .alert-info    { background: #f0f6ff; border-color: #2e75b6; color: #1a3a6d; }
        .alert-icon    { font-size: 14px; flex-shrink: 0; line-height: 1.3; }

        /* ── Medidor de intentos ─────────────────────────────────── */
        .attempts-bar {
            height: 3px;
            background: #e0e0e0;
            margin-top: 10px;
            border-radius: 2px;
            overflow: hidden;
        }
        .attempts-bar-fill {
            height: 100%;
            border-radius: 2px;
            transition: width .3s, background .3s;
        }

        .login-card-footer {
            background: #f8f9fa;
            padding: 10px 32px;
            border-top: 1px solid #e8e8e8;
            font-size: 10px;
            color: #aaa;
            text-align: center;
        }
    </style>
</head>
<body>
<?php
// Mensajes de estado por parámetro GET
$msgParam = $_GET['msg'] ?? '';
$infoMsg  = match($msgParam) {
    'logout'  => '✓ Sesión cerrada correctamente.',
    'expired' => 'Tu sesión expiró por inactividad. Ingresa de nuevo.',
    default   => null,
};

// Intentos restantes para mostrar barra
$ip          = Security::clientIp();
$attempts    = Security::getAttempts($ip);
$maxAttempts = 5;
$pct         = min(100, ($attempts / $maxAttempts) * 100);
$barColor    = $pct >= 80 ? '#d9534f' : ($pct >= 60 ? '#f0ad4e' : '#3a9e5f');
?>

<div class="login-card">
    <div class="login-card-header">
        <h2>Sistema de Incidentes</h2>
        <p>Clientes Residenciales — Fast-Net</p>
    </div>

    <div class="login-card-body">

        <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <span class="alert-icon">⚠</span>
            <span><?= Security::e($error) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($infoMsg): ?>
        <div class="alert <?= $msgParam === 'logout' ? 'alert-success' : 'alert-info' ?>">
            <span class="alert-icon"><?= $msgParam === 'logout' ? '✓' : 'ℹ' ?></span>
            <span><?= Security::e($infoMsg) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST"
              action="<?= Security::e(BASE_URL) ?>?action=login"
              autocomplete="on"
              id="loginForm">

            <!-- Token CSRF oculto -->
            <?= Security::csrfField() ?>

            <div class="form-group">
                <label for="usuario">Correo / Usuario</label>
                <input type="text"
                       id="usuario"
                       name="usuario"
                       required
                       autocomplete="username"
                       maxlength="255"
                       value="<?= Security::e($_POST['usuario'] ?? '') ?>"
                       spellcheck="false">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="pw-wrap">
                    <input type="password"
                           id="password"
                           name="password"
                           required
                           autocomplete="current-password"
                           maxlength="72"
                           id="pwInput">
                    <button type="button" class="pw-toggle" id="pwToggle"
                            title="Mostrar / Ocultar contraseña"
                            aria-label="Mostrar contraseña">👁</button>
                </div>
            </div>

            <!-- Barra de intentos fallidos (solo si hay intentos) -->
            <?php if ($attempts > 0): ?>
            <div class="attempts-bar" title="Intentos fallidos: <?= $attempts ?>/<?= $maxAttempts ?>">
                <div class="attempts-bar-fill"
                     style="width:<?= $pct ?>%; background:<?= $barColor ?>">
                </div>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn-submit" id="btnSubmit">
                Ingresar
            </button>
        </form>
    </div>

    <div class="login-card-footer">
        Acceso restringido — solo personal autorizado
    </div>
</div>

<script>
// ── Mostrar / Ocultar contraseña ──────────────────────────────────
const pwInput  = document.getElementById('password');
const pwToggle = document.getElementById('pwToggle');

pwToggle.addEventListener('click', () => {
    const isPassword = pwInput.type === 'password';
    pwInput.type     = isPassword ? 'text' : 'password';
    pwToggle.textContent = isPassword ? '︶' : '👁';
    pwToggle.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
});

// ── Prevenir doble submit ─────────────────────────────────────────
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnSubmit');
    btn.disabled     = true;
    btn.textContent  = 'Verificando...';
    // Re-habilitar después de 6 s por si el servidor tarda
    setTimeout(() => {
        btn.disabled    = false;
        btn.textContent = 'Ingresar';
    }, 6000);
});
</script>
</body>
</html>
