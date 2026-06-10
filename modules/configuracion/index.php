<?php
// modules/configuracion/index.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin(); requireRole('admin');
define('PAGE_TITLE', 'Configuración');

$config = getConfig();
$errors = $success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Token inválido.'; }
    else {
        $data = [
            'razon_social'    => trim($_POST['razon_social'] ?? ''),
            'nombre_comercial'=> trim($_POST['nombre_comercial'] ?? ''),
            'ruc'             => trim($_POST['ruc'] ?? ''),
            'direccion_matriz'=> trim($_POST['direccion_matriz'] ?? ''),
            'telefono'        => trim($_POST['telefono'] ?? ''),
            'correo'          => trim($_POST['correo'] ?? ''),
            'ambiente'        => $_POST['ambiente'] ?? '1',
            'establecimiento' => str_pad(trim($_POST['establecimiento'] ?? '001'), 3, '0', STR_PAD_LEFT),
            'punto_emision'   => str_pad(trim($_POST['punto_emision'] ?? '001'), 3, '0', STR_PAD_LEFT),
            'smtp_host'       => trim($_POST['smtp_host'] ?? 'smtp.gmail.com'),
            'smtp_port'       => (int)($_POST['smtp_port'] ?? 587),
            'smtp_user'       => trim($_POST['smtp_user'] ?? ''),
            'smtp_pass'       => trim($_POST['smtp_pass'] ?? ''),
            'smtp_from_name'  => trim($_POST['smtp_from_name'] ?? ''),
            'clave_certificado'=> trim($_POST['clave_certificado'] ?? ''),
        ];

        if (!$data['razon_social']) $errors[] = 'La razón social es obligatoria.';
        if (!$data['ruc'])          $errors[] = 'El RUC es obligatorio.';

        // Upload certificado
        if (!empty($_FILES['certificado_p12']['name'])) {
            $ext = strtolower(pathinfo($_FILES['certificado_p12']['name'], PATHINFO_EXTENSION));
            if ($ext === 'p12') {
                $dest = UPLOAD_PATH . 'certificados/';
                if (!is_dir($dest)) mkdir($dest, 0755, true);
                $fname = 'cert_' . time() . '.p12';
                if (move_uploaded_file($_FILES['certificado_p12']['tmp_name'], $dest . $fname)) {
                    $data['certificado_p12'] = $fname;
                } else {
                    $errors[] = 'Error al subir el certificado.';
                }
            } else {
                $errors[] = 'El certificado debe ser un archivo .p12';
            }
        }

        if (!$errors) {
            if (empty($data['smtp_pass']) && $config['smtp_pass']) {
                unset($data['smtp_pass']); // Mantener contraseña anterior
            }
            if (empty($data['certificado_p12'] ?? '') && $config['certificado_p12']) {
                $data['certificado_p12'] = $config['certificado_p12'];
            }

            $sets   = implode('=?,', array_keys($data)) . '=?';
            $values = array_values($data);
            db()->query("UPDATE configuracion SET $sets WHERE id=1", $values);
            setFlash('success', 'Configuración guardada correctamente.');
            header('Location: index.php'); exit;
        }
    }
}
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div style="max-width:860px">
  <?php foreach ($errors as $e): ?><div class="alert alert-error"><span><?= sanitize($e) ?></span></div><?php endforeach; ?>

  <form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>

    <!-- Datos empresa -->
    <div class="card" style="margin-bottom:20px">
      <div class="card-header"><div class="card-title">Datos de la Empresa</div></div>
      <div class="card-body">
        <div class="form-grid form-grid-2" style="gap:16px">
          <div class="form-group"><label>Razón Social *</label><input type="text" name="razon_social" value="<?= sanitize($config['razon_social'] ?? '') ?>" required></div>
          <div class="form-group"><label>Nombre Comercial</label><input type="text" name="nombre_comercial" value="<?= sanitize($config['nombre_comercial'] ?? '') ?>"></div>
          <div class="form-group"><label>RUC *</label><input type="text" name="ruc" value="<?= sanitize($config['ruc'] ?? '') ?>" maxlength="13" required></div>
          <div class="form-group"><label>Teléfono</label><input type="text" name="telefono" value="<?= sanitize($config['telefono'] ?? '') ?>"></div>
          <div class="form-group"><label>Correo</label><input type="email" name="correo" value="<?= sanitize($config['correo'] ?? '') ?>"></div>
        </div>
        <div class="form-group" style="margin-top:16px"><label>Dirección Matriz</label><textarea name="direccion_matriz"><?= sanitize($config['direccion_matriz'] ?? '') ?></textarea></div>
      </div>
    </div>

    <!-- Config SRI -->
    <div class="card" style="margin-bottom:20px">
      <div class="card-header"><div class="card-title">Configuración SRI</div></div>
      <div class="card-body">
        <div class="form-grid form-grid-3" style="gap:16px">
          <div class="form-group">
            <label>Ambiente</label>
            <select name="ambiente">
              <option value="1" <?= ($config['ambiente']??'1')==='1'?'selected':'' ?>>🧪 Pruebas</option>
              <option value="2" <?= ($config['ambiente']??'1')==='2'?'selected':'' ?>>🚀 Producción</option>
            </select>
          </div>
          <div class="form-group"><label>Establecimiento</label><input type="text" name="establecimiento" value="<?= sanitize($config['establecimiento'] ?? '001') ?>" maxlength="3"></div>
          <div class="form-group"><label>Punto de Emisión</label><input type="text" name="punto_emision" value="<?= sanitize($config['punto_emision'] ?? '001') ?>" maxlength="3"></div>
        </div>

        <div class="divider"></div>
        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:12px">Firma Electrónica</div>
        <div class="form-grid form-grid-2" style="gap:16px">
          <div class="form-group">
            <label>Certificado .p12</label>
            <input type="file" name="certificado_p12" accept=".p12">
            <?php if ($config['certificado_p12'] ?? ''): ?>
            <div class="form-hint" style="color:var(--success)">✓ Certificado actual: <?= sanitize($config['certificado_p12']) ?></div>
            <?php else: ?>
            <div class="form-hint">Sube tu certificado de firma electrónica del Banco Central del Ecuador</div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label>Clave del Certificado</label>
            <input type="password" name="clave_certificado" placeholder="Contraseña del .p12" autocomplete="off">
          </div>
        </div>
      </div>
    </div>

    <!-- SMTP -->
    <div class="card" style="margin-bottom:20px">
      <div class="card-header">
        <div class="card-title">Configuración de Correo (SMTP)</div>
        <span class="badge badge-info">Para envío automático de facturas</span>
      </div>
      <div class="card-body">
        <div style="background:rgba(31,111,235,.08);border:1px solid rgba(31,111,235,.2);border-radius:var(--radius-sm);padding:12px 16px;margin-bottom:16px;font-size:12px;color:var(--text-muted)">
          💡 <strong>Gmail:</strong> Usa SMTP: smtp.gmail.com, Puerto: 587, y genera una <em>contraseña de aplicación</em> en tu cuenta Google.
        </div>
        <div class="form-grid form-grid-2" style="gap:16px">
          <div class="form-group"><label>Servidor SMTP</label><input type="text" name="smtp_host" value="<?= sanitize($config['smtp_host'] ?? 'smtp.gmail.com') ?>"></div>
          <div class="form-group"><label>Puerto</label><input type="number" name="smtp_port" value="<?= $config['smtp_port'] ?? 587 ?>"></div>
          <div class="form-group"><label>Usuario/Email SMTP</label><input type="email" name="smtp_user" value="<?= sanitize($config['smtp_user'] ?? '') ?>"></div>
          <div class="form-group"><label>Contraseña SMTP</label><input type="password" name="smtp_pass" placeholder="Dejar vacío para mantener actual" autocomplete="off"></div>
          <div class="form-group"><label>Nombre del Remitente</label><input type="text" name="smtp_from_name" value="<?= sanitize($config['smtp_from_name'] ?? '') ?>" placeholder="Mi Empresa S.A."></div>
        </div>
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end">
      <button type="submit" class="btn btn-primary btn-lg">💾 Guardar Configuración</button>
    </div>
  </form>
</div>
<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
