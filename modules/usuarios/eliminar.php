<?php
// modules/usuarios/eliminar.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin(); requireRole('admin');
$id = (int)($_GET['id'] ?? 0);
if ($id === (int)$_SESSION['user_id']) { setFlash('error','No puedes eliminar tu propio usuario.'); }
else {
    $u = db()->fetchOne("SELECT id FROM usuarios WHERE id=? AND activo=1", [$id]);
    if ($u) { db()->query("UPDATE usuarios SET activo=0 WHERE id=?", [$id]); setFlash('success','Usuario eliminado.'); }
    else { setFlash('error','Usuario no encontrado.'); }
}
header('Location: index.php'); exit;
