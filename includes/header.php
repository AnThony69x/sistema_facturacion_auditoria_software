<?php
// includes/header.php
if (!defined('PAGE_TITLE')) define('PAGE_TITLE', 'Sistema');
$currentUser = currentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= PAGE_TITLE ?> — FacturaSRI</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
  <script>
    const BASE_URL  = '<?= BASE_URL ?>';
    const UPLOAD_URL = '<?= UPLOAD_URL ?>';
  </script>
</head>
<body>
<div class="app-layout">

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ── SIDEBAR ───────────────────────────────── -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div>
      <div class="sidebar-logo">Factura<span>SRI</span></div>
      <div style="font-size:10px;color:var(--text-dim);margin-top:2px">Sistema de Facturación</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">
      <div class="nav-section-title">Principal</div>
    </div>
    <a href="<?= BASE_URL ?>index.php" class="nav-item <?= ($currentPage==='index' && $currentDir!=='facturas') ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>

    <div class="nav-section">
      <div class="nav-section-title">Facturación</div>
    </div>
    <a href="<?= BASE_URL ?>modules/facturas/index.php" class="nav-item <?= $currentDir==='facturas' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
      Facturas
    </a>
    <a href="<?= BASE_URL ?>modules/facturas/crear.php" class="nav-item <?= ($currentDir==='facturas' && $currentPage==='crear') ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      Nueva Factura
    </a>

    <div class="nav-section">
      <div class="nav-section-title">Maestros</div>
    </div>
    <a href="<?= BASE_URL ?>modules/clientes/index.php" class="nav-item <?= $currentDir==='clientes' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Clientes
    </a>
    <a href="<?= BASE_URL ?>modules/productos/index.php" class="nav-item <?= $currentDir==='productos' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      Productos
    </a>

    <?php if (($_SESSION['user_rol'] ?? '') === 'admin'): ?>
    <div class="nav-section">
      <div class="nav-section-title">Administración</div>
    </div>
    <a href="<?= BASE_URL ?>modules/usuarios/index.php" class="nav-item <?= $currentDir==='usuarios' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Usuarios
    </a>
    <a href="<?= BASE_URL ?>modules/configuracion/index.php" class="nav-item <?= $currentDir==='configuracion' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
      Configuración
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-card">
      <img src="<?= UPLOAD_URL ?>usuarios/<?= $currentUser['foto'] ?? 'default.png' ?>"
           class="user-avatar"
           onerror="this.src='<?= BASE_URL ?>assets/img/default_user.png'">
      <div class="user-info">
        <div class="user-name"><?= sanitize($currentUser['nombres'] ?? '') ?></div>
        <div class="user-role"><?= ucfirst($currentUser['rol'] ?? '') ?></div>
      </div>
      <a href="<?= BASE_URL ?>auth/logout.php" class="btn-logout" title="Cerrar sesión">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      </a>
    </div>
  </div>
</aside>

<!-- ── MAIN ───────────────────────────────────── -->
<div class="main-content">
  <!-- Topbar -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" id="menuToggle">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div>
        <div class="page-title"><?= PAGE_TITLE ?></div>
      </div>
    </div>
    <div class="topbar-right">
      <a href="<?= BASE_URL ?>modules/facturas/crear.php" class="btn btn-primary btn-sm">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nueva Factura
      </a>
    </div>
  </header>

  <!-- Page Body -->
  <main class="page-body">
    <?= renderFlash() ?>
