<?php
// modules/usuarios/editar.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin();
requireRole('admin');

$id      = (int)($_GET['id'] ?? 0);
$usuario = db()->fetchOne("SELECT * FROM usuarios WHERE id=? AND activo=1", [$id]);
if (!$usuario) { setFlash('error','Usuario no encontrado.'); header('Location: index.php'); exit; }

define('PAGE_TITLE', 'Editar Usuario');
$errors = [];

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
        if ($password && strlen($password) < 8) $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        if ($password && $password !== $password2) $errors[] = 'Las contraseñas no coinciden.';
        $dup = db()->fetchOne("SELECT id FROM usuarios WHERE username=? AND id!=?", [$data['username'], $id]);
        if ($dup) $errors[] = 'Ese nombre de usuario ya está en uso.';

        $foto = $usuario['foto'];
        if (!empty($_FILES['foto']['name'])) {
            $f = uploadFile($_FILES['foto'], 'usuarios');
            if ($f) {
                if ($foto !== 'default_user.png') @unlink(UPLOAD_PATH . 'usuarios/' . $foto);
                $foto = $f;
            } else {
                $errors[] = 'Error al subir imagen.';
            }
        }

        if (!$errors) {
            if ($password) {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                db()->query("UPDATE usuarios SET nombres=?,username=?,password=?,rol=?,foto=? WHERE id=?",
                    [$data['nombres'],$data['username'],$hash,$data['rol'],$foto,$id]);
            } else {
                db()->query("UPDATE usuarios SET nombres=?,username=?,rol=?,foto=? WHERE id=?",
                    [$data['nombres'],$data['username'],$data['rol'],$foto,$id]);
            }
            setFlash('success', 'Usuario actualizado.');
            header('Location: index.php'); exit;
        }
        $usuario = array_merge($usuario, $data);
    }
}
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div style="max-width:600px">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Editar Usuario</div>
      <a href="index.php" class="btn btn-outline btn-sm">← Volver</a>
    </div>
    <div class="card-body">
      <?php foreach ($errors as $e): ?><div class="alert alert-error"><span><?= sanitize($e) ?></span></div><?php endforeach; ?>
      <form method="POST" enctype="multipart/form-data" class="form-grid">
        <?= csrfField() ?>

        <div class="form-group">
          <label>Nombre Completo *</label>
          <input type="text" name="nombres" value="<?= sanitize($usuario['nombres']) ?>" required>
        </div>

        <div class="form-grid form-grid-2" style="gap:16px">
          <div class="form-group">
            <label>Usuario *</label>
            <input type="text" name="username" value="<?= sanitize($usuario['username']) ?>" required>
          </div>
          <div class="form-group">
            <label>Rol</label>
            <select name="rol">
              <option value="admin"     <?= $usuario['rol']==='admin'?'selected':'' ?>>Administrador</option>
              <option value="cajero"    <?= $usuario['rol']==='cajero'?'selected':'' ?>>Cajero</option>
              <option value="bodeguero" <?= $usuario['rol']==='bodeguero'?'selected':'' ?>>Bodeguero</option>
            </select>
          </div>
        </div>

        <div style="background:var(--bg-3);border-radius:var(--radius-sm);padding:14px;border:1px solid var(--border-2)">
          <div style="font-size:12px;color:var(--text-muted);margin-bottom:12px">⚠️ Dejar en blanco para mantener la contraseña actual</div>
          <div class="form-grid form-grid-2" style="gap:16px">
            <div class="form-group">
              <label>Nueva Contraseña</label>
              <input type="password" name="password" placeholder="Mín. 8 caracteres" autocomplete="new-password">
            </div>
            <div class="form-group">
              <label>Confirmar Contraseña</label>
              <input type="password" name="password2" placeholder="Repite la contraseña">
            </div>
          </div>
        </div>

        <div class="form-group">
          <label>Foto de Perfil</label>
          <div style="display:flex;gap:16px;align-items:center">
            <img src="<?= UPLOAD_URL ?>usuarios/<?= sanitize($usuario['foto']) ?>"
                 id="fotoPreview"
                 style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--border)"
                 onerror="this.src='<?= BASE_URL ?>assets/img/default_user.png'">
            <label class="upload-zone" for="fotoInput" style="flex:1;padding:14px">
              <div style="font-size:13px;color:var(--text-muted)">Click para cambiar foto</div>
            </label>
          </div>
          <input type="file" id="fotoInput" name="foto" accept="image/*"
                 data-upload-preview="fotoPreview" style="display:none">
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;border-top:1px solid var(--border-2);padding-top:18px">
          <a href="index.php" class="btn btn-outline">Cancelar</a>
          <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
