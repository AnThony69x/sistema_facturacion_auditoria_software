<?php
// reset_admin.php
// IMPORTANTE: Elimina este archivo después de usarlo

require_once 'config/database.php';

$nuevaPassword = 'Admin123!';
$hash = password_hash($nuevaPassword, PASSWORD_BCRYPT, ['cost' => 12]);

// Actualizar o crear el admin
$existente = db()->fetchOne("SELECT id FROM usuarios WHERE username = 'admin'");

if ($existente) {
    db()->query("UPDATE usuarios SET password = ?, activo = 1 WHERE username = 'admin'", [$hash]);
    $msg = '✅ Contraseña del usuario <strong>admin</strong> actualizada correctamente.';
} else {
    db()->query(
        "INSERT INTO usuarios (nombres, username, password, rol, activo) VALUES (?,?,?,?,1)",
        ['Administrador Sistema', 'admin', $hash, 'admin']
    );
    $msg = '✅ Usuario <strong>admin</strong> creado correctamente.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reset Admin</title>
  <style>
    body { font-family: Arial, sans-serif; background: #0d1117; color: #e6edf3;
           display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
    .box { background: #161b22; border: 1px solid #30363d; border-radius: 10px;
           padding: 36px 40px; max-width: 480px; text-align: center; }
    h2 { color: #e8562a; margin-bottom: 16px; }
    p  { line-height: 1.7; color: #7d8590; margin: 8px 0; }
    .cred { background: #1c2333; border-radius: 6px; padding: 14px 20px; margin: 20px 0;
            font-family: monospace; font-size: 15px; border: 1px solid #30363d; }
    .cred span { color: #f5863d; }
    a  { display: inline-block; margin-top: 16px; background: #e8562a; color: #fff;
         padding: 10px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; }
    a:hover { background: #f5863d; }
    .warn { color: #d29922; font-size: 13px; margin-top: 20px;
            border: 1px solid rgba(210,153,34,.3); border-radius: 6px; padding: 10px; }
  </style>
</head>
<body>
<div class="box">
  <h2>FacturaSRI</h2>
  <p><?= $msg ?></p>
  <div class="cred">
    Usuario: <span>admin</span><br>
    Contraseña: <span>Admin123!</span>
  </div>
  <a href="auth/login.php">Ir al Login →</a>
  <div class="warn">
    ⚠️ <strong>Elimina este archivo</strong> del servidor después de ingresar.<br>
    <code>rm reset_admin.php</code>
  </div>
</div>
</body>
</html>