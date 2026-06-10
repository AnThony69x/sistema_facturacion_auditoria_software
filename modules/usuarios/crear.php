<?php
// modules/usuarios/crear.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin();
requireRole('admin');
define('PAGE_TITLE', 'Nuevo Usuario');

$errors = [];
$data   = ['nombres'=>'','username'=>'','rol'=>'cajero'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Token inválido.'; }
    else {
        $data = [
            'nombres'  => trim($_POST['nombres'] ?? ''),
            'username' => strtolower(trim($_POST['username'] ?? '')),
            'rol'      => $_POST['rol'] ?? 'cajero',
        ];
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        if (!$data['nombres'])  $errors[] = 'El nombre es obligatorio.';
        if (!$data['username']) $errors[] = 'El usuario es obligatorio.';
        if (strlen($password) < 8) $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        if ($password !== $password2) $errors[] = 'Las contraseñas no coinciden.';
        if (db()->fetchOne("SELECT id FROM usuarios WHERE username=?", [$data['username']]))
            $errors[] = 'Ese nombre de usuario ya está en uso.';

        $foto = 'default_user.png';
        if (!empty($_FILES['foto']['name'])) {
            $f = uploadFile($_FILES['foto'], 'usuarios');
            if ($f) $foto = $f;
            else $errors[] = 'Error al subir imagen.';
        }

        if (!$errors) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            db()->query(
                "INSERT INTO usuarios (nombres, username, password, rol, foto) VALUES (?,?,?,?,?)",
                [$data['nombres'], $data['username'], $hash, $data['rol'], $foto]
            );
            setFlash('success', 'Usuario creado correctamente.');
            header('Location: index.php'); exit;
        }
    }
}
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div style="max-width:600px">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Nuevo Usuario</div>
      <a href="index.php" class="btn btn-outline btn-sm">← Volver</a>
    </div>
    <div class="card-body">
      <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><span><?= sanitize($e) ?></span></div>
      <?php endforeach; ?>

      <form method="POST" enctype="multipart/form-data" class="form-grid">
        <?= csrfField() ?>

        <div class="form-group">
          <label>Nombre Completo *</label>
          <input type="text" name="nombres" value="<?= sanitize($data['nombres']) ?>"
                 placeholder="Nombre y apellido" required>
        </div>

        <div class="form-grid form-grid-2" style="gap:16px">
          <div class="form-group">
            <label>Usuario *</label>
            <input type="text" name="username" value="<?= sanitize($data['username']) ?>"
                   placeholder="usuario123" autocomplete="off" required>
          </div>
          <div class="form-group">
            <label>Rol</label>
            <select name="rol">
              <option value="admin"     <?= $data['rol']==='admin'?'selected':'' ?>>Administrador</option>
              <option value="cajero"    <?= $data['rol']==='cajero'?'selected':'' ?>>Cajero</option>
              <option value="bodeguero" <?= $data['rol']==='bodeguero'?'selected':'' ?>>Bodeguero</option>
            </select>
          </div>
        </div>

        <div class="form-grid form-grid-2" style="gap:16px">
          <div class="form-group">
            <label>Contraseña *</label>
            <input type="password" name="password" placeholder="Mín. 8 caracteres" autocomplete="new-password" required>
          </div>
          <div class="form-group">
            <label>Confirmar Contraseña *</label>
            <input type="password" name="password2" placeholder="Repite la contraseña" required>
          </div>
        </div>

        <div class="form-group">
          <label>Foto de Perfil</label>
          <label class="upload-zone" for="fotoInput" style="cursor:pointer;padding:20px">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:.5;margin-bottom:6px"><circle cx="12" cy="8" r="4"/><path d="M6 20v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>
            <div style="font-size:13px;color:var(--text-muted)">Click para subir foto de perfil</div>
            <img id="fotoPreview" class="upload-preview" style="display:none;border-radius:50%">
          </label>
          <input type="file" id="fotoInput" name="foto" accept="image/*"
                 data-upload-preview="fotoPreview" style="display:none">
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;border-top:1px solid var(--border-2);padding-top:18px">
          <a href="index.php" class="btn btn-outline">Cancelar</a>
          <button type="submit" class="btn btn-primary">Crear Usuario</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
