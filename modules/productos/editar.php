<?php
// modules/productos/editar.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin();

$id      = (int)($_GET['id'] ?? 0);
$producto= db()->fetchOne("SELECT * FROM productos WHERE id=? AND activo=1", [$id]);
if (!$producto) { setFlash('error','Producto no encontrado.'); header('Location: index.php'); exit; }

define('PAGE_TITLE', 'Editar Producto');
$categorias = db()->fetchAll("SELECT * FROM categorias WHERE activo=1 ORDER BY nombre");
$errors = [];

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

        if (!$data['nombre']) $errors[] = 'El nombre es obligatorio.';
        if ($data['precio_venta'] <= 0) $errors[] = 'Precio de venta inválido.';

        $foto = $producto['foto'];
        if (!empty($_FILES['foto']['name'])) {
            $f = uploadFile($_FILES['foto'], 'productos');
            if ($f) {
                // Delete old
                if ($foto !== 'default_product.png') @unlink(UPLOAD_PATH . 'productos/' . $foto);
                $foto = $f;
            } else {
                $errors[] = 'Error al subir imagen.';
            }
        }

        if (!$errors) {
            db()->query(
                "UPDATE productos SET codigo=?,nombre=?,descripcion=?,categoria_id=?,existencia=?,
                 precio_compra=?,precio_venta=?,iva=?,unidad_medida=?,foto=? WHERE id=?",
                [...array_values($data), $foto, $id]
            );
            setFlash('success', 'Producto actualizado.');
            header('Location: index.php'); exit;
        }
        $producto = array_merge($producto, $data);
    }
}
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div style="max-width:800px">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Editar Producto</div>
      <a href="index.php" class="btn btn-outline btn-sm">← Volver</a>
    </div>
    <div class="card-body">
      <?php foreach ($errors as $e): ?><div class="alert alert-error"><span><?= sanitize($e) ?></span></div><?php endforeach; ?>
      <form method="POST" enctype="multipart/form-data" class="form-grid">
        <?= csrfField() ?>
        <div class="form-grid form-grid-2" style="gap:16px">
          <div class="form-group"><label>Código</label><input type="text" name="codigo" value="<?= sanitize($producto['codigo']) ?>"></div>
          <div class="form-group"><label>Unidad de Medida</label>
            <select name="unidad_medida">
              <?php foreach (['UNIDAD','KG','LITRO','METRO','CAJA','DOCENA','SERVICIO'] as $u): ?>
                <option value="<?= $u ?>" <?= $producto['unidad_medida']===$u?'selected':'' ?>><?= $u ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group"><label>Nombre *</label><input type="text" name="nombre" value="<?= sanitize($producto['nombre']) ?>" required></div>
        <div class="form-group"><label>Descripción</label><textarea name="descripcion"><?= sanitize($producto['descripcion']) ?></textarea></div>
        <div class="form-grid form-grid-3" style="gap:16px">
          <div class="form-group"><label>Categoría</label>
            <select name="categoria_id">
              <?php foreach ($categorias as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $producto['categoria_id']==$c['id']?'selected':'' ?>><?= sanitize($c['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>IVA %</label>
            <select name="iva">
              <?php foreach ([0,5,8,12,15] as $iv): ?>
                <option value="<?= $iv ?>" <?= $producto['iva']==$iv?'selected':'' ?>><?= $iv ?>%</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Existencia</label><input type="number" name="existencia" value="<?= $producto['existencia'] ?>" step="0.01" min="0"></div>
        </div>
        <div class="form-grid form-grid-2" style="gap:16px">
          <div class="form-group"><label>Precio Compra</label><div class="input-group"><span class="input-prefix">$</span><input type="number" name="precio_compra" value="<?= $producto['precio_compra'] ?>" step="0.0001" min="0"></div></div>
          <div class="form-group"><label>Precio Venta *</label><div class="input-group"><span class="input-prefix">$</span><input type="number" name="precio_venta" value="<?= $producto['precio_venta'] ?>" step="0.0001" min="0.01" required></div></div>
        </div>
        <div class="form-group">
          <label>Foto</label>
          <div style="display:flex;gap:16px;align-items:flex-start">
            <img src="<?= UPLOAD_URL ?>productos/<?= sanitize($producto['foto']) ?>"
                 id="fotoPreview" class="upload-preview" style="display:block;width:80px;height:80px"
                 onerror="this.src='<?= BASE_URL ?>assets/img/default_product.png'">
            <label class="upload-zone" for="fotoInput" style="flex:1;padding:16px">
              <div style="font-size:13px;color:var(--text-muted)">Click para cambiar imagen</div>
            </label>
          </div>
          <input type="file" id="fotoInput" name="foto" accept="image/*" data-upload-preview="fotoPreview" style="display:none">
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;border-top:1px solid var(--border-2);padding-top:18px">
          <a href="index.php" class="btn btn-outline">Cancelar</a>
          <button type="submit" class="btn btn-primary">Actualizar Producto</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
