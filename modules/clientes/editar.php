<?php
// modules/clientes/editar.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin();

$id     = (int)($_GET['id'] ?? 0);
$cliente= db()->fetchOne("SELECT * FROM clientes WHERE id=? AND activo=1", [$id]);
if (!$cliente) { setFlash('error','Cliente no encontrado.'); header('Location: index.php'); exit; }

define('PAGE_TITLE', 'Editar Cliente');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Token inválido.'; }
    else {
        $data = array_map('trim', [
            'cedula'             => $_POST['cedula'] ?? '',
            'tipo_identificacion'=> $_POST['tipo_identificacion'] ?? 'cedula',
            'nombres'            => $_POST['nombres'] ?? '',
            'direccion'          => $_POST['direccion'] ?? '',
            'telefono'           => $_POST['telefono'] ?? '',
            'correo'             => $_POST['correo'] ?? '',
        ]);
        if (!$data['cedula'])  $errors[] = 'La cédula es obligatoria.';
        if (!$data['nombres']) $errors[] = 'El nombre es obligatorio.';
        if ($data['correo'] && !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Correo inválido.';
        $dup = db()->fetchOne("SELECT id FROM clientes WHERE cedula=? AND id!=?",[$data['cedula'],$id]);
        if ($dup) $errors[] = 'Ya existe otro cliente con esa cédula.';

        if (!$errors) {
            db()->query("UPDATE clientes SET cedula=?,tipo_identificacion=?,nombres=?,direccion=?,telefono=?,correo=? WHERE id=?",
                [...array_values($data), $id]);
            setFlash('success','Cliente actualizado.');
            header('Location: index.php'); exit;
        }
        $cliente = array_merge($cliente, $data);
    }
}
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div style="max-width:700px">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Editar Cliente</div>
      <a href="index.php" class="btn btn-outline btn-sm">← Volver</a>
    </div>
    <div class="card-body">
      <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><span><?= sanitize($e) ?></span></div>
      <?php endforeach; ?>
      <form method="POST" class="form-grid">
        <?= csrfField() ?>
        <div class="form-grid form-grid-2" style="gap:16px">
          <div class="form-group">
            <label>Tipo de Identificación</label>
            <select name="tipo_identificacion">
              <?php foreach (['cedula'=>'Cédula','ruc'=>'RUC','pasaporte'=>'Pasaporte'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $cliente['tipo_identificacion']===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Cédula / RUC *</label>
            <input type="text" name="cedula" value="<?= sanitize($cliente['cedula']) ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label>Nombre / Razón Social *</label>
          <input type="text" name="nombres" value="<?= sanitize($cliente['nombres']) ?>" required>
        </div>
        <div class="form-grid form-grid-2" style="gap:16px">
          <div class="form-group">
            <label>Teléfono</label>
            <input type="text" name="telefono" value="<?= sanitize($cliente['telefono']) ?>">
          </div>
          <div class="form-group">
            <label>Correo</label>
            <input type="email" name="correo" value="<?= sanitize($cliente['correo']) ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Dirección</label>
          <textarea name="direccion"><?= sanitize($cliente['direccion']) ?></textarea>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;border-top:1px solid var(--border-2);padding-top:18px">
          <a href="index.php" class="btn btn-outline">Cancelar</a>
          <button type="submit" class="btn btn-primary">Actualizar Cliente</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
