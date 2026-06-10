<?php
// modules/clientes/crear.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin();
define('PAGE_TITLE', 'Nuevo Cliente');

$errors = [];
$data   = ['cedula'=>'','tipo_identificacion'=>'cedula','nombres'=>'','direccion'=>'','telefono'=>'','correo'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido.';
    } else {
        $data = array_map('trim', [
            'cedula'             => $_POST['cedula'] ?? '',
            'tipo_identificacion'=> $_POST['tipo_identificacion'] ?? 'cedula',
            'nombres'            => $_POST['nombres'] ?? '',
            'direccion'          => $_POST['direccion'] ?? '',
            'telefono'           => $_POST['telefono'] ?? '',
            'correo'             => $_POST['correo'] ?? '',
        ]);

        if (!$data['cedula'])  $errors[] = 'La cédula/RUC es obligatoria.';
        if (!$data['nombres']) $errors[] = 'El nombre es obligatorio.';
        if ($data['correo'] && !filter_var($data['correo'], FILTER_VALIDATE_EMAIL))
            $errors[] = 'El correo no es válido.';

        // Check duplicate
        if (!$errors && db()->fetchOne("SELECT id FROM clientes WHERE cedula = ?", [$data['cedula']]))
            $errors[] = 'Ya existe un cliente con esa cédula/RUC.';

        if (!$errors) {
            db()->query(
                "INSERT INTO clientes (cedula,tipo_identificacion,nombres,direccion,telefono,correo)
                 VALUES (?,?,?,?,?,?)",
                array_values($data)
            );
            setFlash('success', 'Cliente creado correctamente.');
            header('Location: index.php');
            exit;
        }
    }
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div style="max-width:700px">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Datos del Cliente</div>
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
                <option value="<?= $v ?>" <?= $data['tipo_identificacion']===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Cédula / RUC / Pasaporte <span style="color:var(--danger)">*</span></label>
            <input type="text" name="cedula" value="<?= sanitize($data['cedula']) ?>"
                   placeholder="0999999999001" maxlength="20" required>
          </div>
        </div>

        <div class="form-group">
          <label>Nombre / Razón Social <span style="color:var(--danger)">*</span></label>
          <input type="text" name="nombres" value="<?= sanitize($data['nombres']) ?>"
                 placeholder="Nombre completo o razón social" required>
        </div>

        <div class="form-grid form-grid-2" style="gap:16px">
          <div class="form-group">
            <label>Teléfono</label>
            <input type="text" name="telefono" value="<?= sanitize($data['telefono']) ?>"
                   placeholder="0999999999">
          </div>
          <div class="form-group">
            <label>Correo Electrónico</label>
            <input type="email" name="correo" value="<?= sanitize($data['correo']) ?>"
                   placeholder="cliente@correo.com">
          </div>
        </div>

        <div class="form-group">
          <label>Dirección</label>
          <textarea name="direccion" placeholder="Dirección completa del cliente"><?= sanitize($data['direccion']) ?></textarea>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;border-top:1px solid var(--border-2);padding-top:18px">
          <a href="index.php" class="btn btn-outline">Cancelar</a>
          <button type="submit" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Guardar Cliente
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
