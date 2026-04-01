<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BSC Temis Lostalo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 50%, #1a365d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
            border: none;
        }
        .login-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 2rem 2rem 1.5rem;
            text-align: center;
            border-radius: 12px 12px 0 0;
        }
        .login-header h4 {
            color: #1e3a5f;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        .login-header small {
            color: #64748b;
        }
        .login-body {
            padding: 2rem;
        }
        .btn-login {
            background: #1e3a5f;
            border: none;
            padding: 0.6rem;
            font-weight: 600;
        }
        .btn-login:hover {
            background: #2c5282;
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="login-header">
            <h4><i class="bi bi-bullseye me-2"></i>BSC</h4>
            <small>Temis Lostalo - Gesti&oacute;n Estrat&eacute;gica</small>
        </div>
        <div class="login-body">
            <?php if ($loginError ?? false): ?>
                <div class="alert alert-danger py-2 small">
                    <i class="bi bi-exclamation-triangle me-1"></i><?= sanitize($loginError) ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="<?= BASE_URL ?>index.php?page=login">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" name="email"
                               value="<?= sanitize($loginEmail ?? '') ?>" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Contrase&ntilde;a</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar sesi&oacute;n
                </button>
            </form>
        </div>
    </div>
</body>
</html>
