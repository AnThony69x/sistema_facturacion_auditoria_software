<?php
// auth/login.php
require_once dirname(__DIR__) . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $user = db()->fetchOne("SELECT * FROM usuarios WHERE username = ? AND activo = 1", [$username]);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['user_rol'] = $user['rol'];
            $_SESSION['user_name']= $user['nombres'];
            header('Location: ' . BASE_URL . 'index.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    } else {
        $error = 'Por favor completa todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión — FacturaSRI</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">

  <!-- Brand Panel -->
  <div class="auth-brand">
    <div class="decorative-grid"></div>
    <div style="position:relative;text-align:center">
      <div class="brand-logo">Factura<span>SRI</span></div>
      <p>Sistema de facturación electrónica validado por el SRI Ecuador</p>
      <div style="margin-top:48px;display:flex;flex-direction:column;gap:14px">
        <?php
        $features = [
            ['📄','Emisión de comprobantes electrónicos XML'],
            ['✍️','Firma electrónica con certificado .p12'],
            ['📧','Envío automático al correo del cliente'],
            ['✅','Validación en tiempo real con el SRI'],
        ];
        foreach ($features as $f): ?>
        <div style="display:flex;align-items:center;gap:12px;background:rgba(255,255,255,.04);border:1px solid var(--border-2);border-radius:8px;padding:12px 16px;text-align:left">
          <span style="font-size:1.2rem"><?= $f[0] ?></span>
          <span style="font-size:13px;color:var(--text-muted)"><?= $f[1] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Form Panel -->
  <div class="auth-form-side">
    <div class="auth-card">
      <h2>Bienvenido</h2>
      <p>Ingresa tus credenciales para continuar</p>

      <?php if ($error): ?>
      <div class="alert alert-error">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <?= sanitize($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" style="display:flex;flex-direction:column;gap:18px">
        <?= csrfField() ?>
        <div class="form-group">
          <label>Usuario</label>
          <div class="input-group">
            <span class="input-prefix">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <input type="text" name="username" placeholder="Ingresa tu usuario"
                   value="<?= sanitize($_POST['username'] ?? '') ?>" required autofocus>
          </div>
        </div>
        <div class="form-group">
          <label>Contraseña</label>
          <div class="input-group">
            <span class="input-prefix">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            </span>
            <input type="password" name="password" placeholder="••••••••" required>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;margin-top:4px">
          Iniciar Sesión
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </button>
      </form>
    </div>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js"></script>
<script>feather.replace({'stroke-width':1.8,width:16,height:16});</script>
</body>
</html>
