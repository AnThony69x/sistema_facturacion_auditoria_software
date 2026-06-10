<?php
// includes/functions.php
require_once dirname(__DIR__) . '/config/database.php';

session_start();

/* ─── Auth ──────────────────────────────────────────────── */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}

function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['user_rol'] ?? '', $roles)) {
        $_SESSION['flash_error'] = 'No tienes permisos para acceder a esta sección.';
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    return db()->fetchOne("SELECT * FROM usuarios WHERE id = ?", [$_SESSION['user_id']]);
}

/* ─── Flash Messages ─────────────────────────────────────── */
function setFlash(string $type, string $msg): void {
    $_SESSION["flash_$type"] = $msg;
}

function getFlash(string $type): ?string {
    $key = "flash_$type";
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

function renderFlash(): string {
    $html = '';
    foreach (['success','error','warning','info'] as $type) {
        $msg = getFlash($type);
        if ($msg) {
            $icons = ['success'=>'check-circle','error'=>'x-circle','warning'=>'alert-triangle','info'=>'info'];
            $html .= "<div class='alert alert-$type' id='flash-$type'>
                        <i data-feather='{$icons[$type]}'></i>
                        <span>$msg</span>
                        <button onclick=\"this.parentElement.remove()\" class='alert-close'>&times;</button>
                      </div>";
        }
    }
    return $html;
}

/* ─── Security ───────────────────────────────────────────── */
function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function generateToken(): string {
    return bin2hex(random_bytes(32));
}

function validateToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

/* ─── File Upload ────────────────────────────────────────── */
function uploadFile(array $file, string $folder, array $allowed = ['jpg','jpeg','png','gif','webp']): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return false;
    if ($file['size'] > 5 * 1024 * 1024) return false;

    $dest = UPLOAD_PATH . $folder . '/';
    if (!is_dir($dest)) mkdir($dest, 0755, true);

    $filename = uniqid('', true) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $dest . $filename)) {
        return $filename;
    }
    return false;
}

/* ─── Number / Format Utils ─────────────────────────────── */
function money(float $n): string {
    return '$' . number_format($n, 2, '.', ',');
}

function formatDate(string $date): string {
    return date('d/m/Y', strtotime($date));
}

/* ─── Pagination ─────────────────────────────────────────── */
function paginate(string $table, string $where, array $params, int $page, int $perPage = 15): array {
    $offset = ($page - 1) * $perPage;
    $total  = db()->fetchOne("SELECT COUNT(*) as c FROM $table $where", $params)['c'];
    $pages  = max(1, ceil($total / $perPage));
    return compact('total', 'pages', 'offset', 'perPage');
}

/* ─── SRI Claves ─────────────────────────────────────────── */
function generarClaveAcceso(string $fecha, string $tipoDoc, string $ruc, string $ambiente,
                              string $estab, string $ptoEmi, string $secuencial, string $tipoEmision): string {
    $fecha_str   = str_replace('-', '', $fecha); // ddmmyyyy
    $partes      = str_pad($fecha_str, 8, '0', STR_PAD_LEFT)
                 . $tipoDoc
                 . $ruc
                 . $ambiente
                 . $estab . $ptoEmi
                 . str_pad($secuencial, 9, '0', STR_PAD_LEFT)
                 . str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT)
                 . $tipoEmision;
    return $partes . calcularDigitoVerificador($partes);
}

function calcularDigitoVerificador(string $clave): int {
    $suma  = 0;
    $mult  = 2;
    for ($i = strlen($clave) - 1; $i >= 0; $i--) {
        $suma += (int)$clave[$i] * $mult;
        $mult  = ($mult === 7) ? 2 : $mult + 1;
    }
    $residuo = $suma % 11;
    $dv = 11 - $residuo;
    if ($dv === 11) $dv = 0;
    if ($dv === 10) $dv = 1;
    return $dv;
}

function generarSecuencial(int $n): string {
    return str_pad($n, 9, '0', STR_PAD_LEFT);
}

function getConfig(): array {
    return db()->fetchOne("SELECT * FROM configuracion LIMIT 1") ?? [];
}

function getNextNumeroFactura(): string {
    $cfg = getConfig();
    $sec = $cfg['secuencial'] ?? 1;
    return ($cfg['establecimiento'] ?? '001') . '-' . ($cfg['punto_emision'] ?? '001') . '-' . str_pad($sec, 9, '0', STR_PAD_LEFT);
}
