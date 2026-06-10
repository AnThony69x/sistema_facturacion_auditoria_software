<?php
// modules/productos/crear.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin();
define('PAGE_TITLE', 'Nuevo Producto');

$categorias = db()->fetchAll("SELECT * FROM categorias WHERE activo=1 ORDER BY nombre");
$errors = [];
$data   = ['codigo'=>'','nombre'=>'','descripcion'=>'','categoria_id'=>1,'existencia'=>0,
           'precio_compra'=>0,'precio_venta'=>0,'iva'=>15,'unidad_medida'=>'UNIDAD'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Token inválido.'; }
    else {
        $data = [
            'codigo'       => trim($_POST['codigo'] ?? ''),
            'nombre'       => trim($_POST['nombre'] ?? ''),
            'descripcion'  => trim($_POST['descripcion'] ?? ''),
            'categoria_id' => (int)($_POST['categoria_id'] ?? 1),
            'existencia'   => (float)($_POST['existencia'] ?? 0),
            'precio_compra'=> (float)($_POST['precio_compra'] ?? 0),
            'precio_venta' => (float)($_POST['precio_venta'] ?? 0),
            'iva'          => (float)($_POST['iva'] ?? 15),
            'unidad_medida'=> trim($_POST['unidad_medida'] ?? 'UNIDAD'),
        ];

        if (!$data['nombre']) $errors[] = 'El nombre del producto es obligatorio.';
        if ($data['precio_venta'] <= 0) $errors[] = 'El precio de venta debe ser mayor a cero.';
        if ($data['codigo'] && db()->fetchOne("SELECT id FROM productos WHERE codigo=?",[$data['codigo']]))
            $errors[] = 'Ya existe un producto con ese código.';

        $foto = 'default_product.png';
        if (!empty($_FILES['foto']['name'])) {
            $f = uploadFile($_FILES['foto'], 'productos');
            if ($f) $foto = $f;
            else $errors[] = 'Error al subir la imagen. Formatos permitidos: jpg, png, gif, webp (máx. 5MB).';
        }

        if (!$errors) {
            db()->query(
                "INSERT INTO productos (codigo,nombre,descripcion,categoria_id,existencia,precio_compra,precio_venta,iva,unidad_medida,foto)
                 VALUES (?,?,?,?,?,?,?,?,?,?)",
                [...array_values($data), $foto]
            );
            setFlash('success', 'Producto creado correctamente.');
            header('Location: index.php'); exit;
        }
    }
}
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div style="max-width:800px">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Nuevo Producto</div>
      <a href="index.php" class="btn btn-outline btn-sm">← Volver</a>
    </div>
    <div class="card-body">
      <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><span><?= sanitize($e) ?></span></div>
      <?php endforeach; ?>

      <form method="POST" enctype="multipart/form-data" class="form-grid">
        <?= csrfField() ?>

        <div class="form-grid form-grid-2" style="gap:16px">
          <div class="form-group">
            <label>Código (opcional)</label>
            <input type="text" name="codigo" value="<?= sanitize($data['codigo']) ?>" placeholder="PROD-001">
          </div>
          <div class="form-group">
            <label>Unidad de Medida</label>
            <select name="unidad_medida">
              <?php foreach (['UNIDAD','KG','LITRO','METRO','CAJA','DOCENA','SERVICIO'] as $u): ?>
                <option value="<?= $u ?>" <?= $data['unidad_medida']===$u?'selected':'' ?>><?= $u ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Nombre del Producto *</label>
          <input type="text" name="nombre" value="<?= sanitize($data['nombre']) ?>"
                 placeholder="Nombre completo del producto" required>
        </div>

        <div class="form-group">
          <label>Descripción</label>
          <textarea name="descripcion" placeholder="Descripción del producto"><?= sanitize($data['descripcion']) ?></textarea>
        </div>

        <div class="form-grid form-grid-3" style="gap:16px">
          <div class="form-group">
            <label>Categoría</label>
            <select name="categoria_id">
              <?php foreach ($categorias as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $data['categoria_id']==$c['id']?'selected':'' ?>><?= sanitize($c['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>IVA %</label>
            <select name="iva">
              <?php foreach ([0,5,8,12,15] as $iv): ?>
                <option value="<?= $iv ?>" <?= $data['iva']==$iv?'selected':'' ?>><?= $iv ?>%</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Existencia Inicial</label>
            <input type="number" name="existencia" value="<?= $data['existencia'] ?>" step="0.01" min="0">
          </div>
        </div>

        <div class="form-grid form-grid-2" style="gap:16px">
          <div class="form-group">
            <label>Precio de Compra</label>
            <div class="input-group">
              <span class="input-prefix">$</span>
              <input type="number" name="precio_compra" value="<?= $data['precio_compra'] ?>" step="0.0001" min="0">
            </div>
          </div>
          <div class="form-group">
            <label>Precio de Venta *</label>
            <div class="input-group">
              <span class="input-prefix">$</span>
              <input type="number" name="precio_venta" value="<?= $data['precio_venta'] ?>" step="0.0001" min="0.01" required>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label>Foto del Producto</label>
          <label class="upload-zone" for="fotoInput" style="cursor:pointer">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:.5;margin-bottom:8px"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            <div style="color:var(--text-muted);font-size:13px">Click para subir imagen</div>
            <div style="color:var(--text-dim);font-size:11px;margin-top:4px">JPG, PNG, WEBP — máx. 5MB</div>
            <img id="fotoPreview" class="upload-preview" style="display:none">
          </label>
          <input type="file" id="fotoInput" name="foto" accept="image/*"
                 data-upload-preview="fotoPreview" style="display:none">
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;border-top:1px solid var(--border-2);padding-top:18px">
          <a href="index.php" class="btn btn-outline">Cancelar</a>
          <button type="submit" class="btn btn-primary">Guardar Producto</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
