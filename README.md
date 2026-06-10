# FacturaSRI — Sistema de Facturación Electrónica para Ecuador

Sistema web PHP/MySQL para gestión de inventario y facturación electrónica
con validación ante el SRI Ecuador.

---

## 📋 REQUISITOS

- PHP 8.1+ con extensiones: `pdo_mysql`, `openssl`, `soap`, `mbstring`
- MySQL 5.7+ o MariaDB 10.4+
- Apache 2.4+ con `mod_rewrite`
- (Opcional) Composer para PHPMailer

---

## 🚀 INSTALACIÓN RÁPIDA

### 1. Copiar archivos
```bash
cp -r sistema_facturacion/ /var/www/html/
# o en XAMPP/WAMP:
# cp -r sistema_facturacion/ C:/xampp/htdocs/
```

### 2. Crear la base de datos
```bash
mysql -u root -p < sistema_facturacion/database.sql
```
O desde phpMyAdmin: importar el archivo `database.sql`

### 3. Configurar conexión
Editar `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
define('DB_NAME', 'facturacion_db');
define('BASE_URL', 'http://localhost/sistema_facturacion/');
```

### 4. Crear carpeta de uploads
```bash
mkdir -p uploads/{productos,usuarios,certificados}
chmod 755 uploads -R
```

### 5. (Opcional) Instalar PHPMailer
```bash
cd sistema_facturacion
composer require phpmailer/phpmailer
```

---

## 🔐 ACCESO INICIAL

| Campo    | Valor     |
|----------|-----------|
| Usuario  | `admin`   |
| Clave    | `Admin123!` |

⚠️ **Cambia la contraseña inmediatamente** en Usuarios → Editar.

---

## 📑 MÓDULOS DEL SISTEMA

| Módulo | Descripción |
|--------|-------------|
| Dashboard | Estadísticas, facturas recientes, top productos |
| Clientes | CRUD completo con validación de cédula/RUC |
| Productos | Inventario con fotos, stock, precios e IVA |
| Usuarios | Control de acceso por roles (admin/cajero/bodeguero) |
| Facturas | Creación, visualización, modificación, impresión |
| Facturación SRI | Envío XML, firma electrónica, autorización |
| Configuración | Datos empresa, SMTP, certificado .p12 |

---

## ⚡ FACTURACIÓN ELECTRÓNICA — FLUJO

```
1. Crear factura (estado: BORRADOR)
       ↓
2. Clic "Facturar Electrónicamente (SRI)"
       ↓
3. Generar XML según norma SRI
       ↓
4. Firmar con certificado .p12 (si configurado)
       ↓
5. Enviar al WebService SRI (Recepción)
       ↓
6. Solicitar Autorización al SRI
       ↓
7. Guardar número de autorización
       ↓
8. Enviar al correo del cliente (XML + cuerpo HTML)
```

---

## 🔧 CONFIGURACIÓN SMTP

Para Gmail:
- Host: `smtp.gmail.com`
- Puerto: `587`
- Usuario: `tucorreo@gmail.com`
- Contraseña: **Contraseña de App** (no tu contraseña normal)

Para generar contraseña de app en Gmail:
`Cuenta Google → Seguridad → Verificación en 2 pasos → Contraseñas de aplicaciones`

---

## 🏛️ CERTIFICADO DE FIRMA ELECTRÓNICA

1. Obtener certificado `.p12` del **Banco Central del Ecuador** o **Security Data**
2. Ir a `Administración → Configuración → Firma Electrónica`
3. Subir el archivo `.p12` y escribir su contraseña
4. Seleccionar ambiente (Pruebas o Producción)

Ambiente de pruebas SRI: `https://celcer.sri.gob.ec`
Ambiente de producción SRI: `https://cel.sri.gob.ec`

---

## 📁 ESTRUCTURA DE ARCHIVOS

```
sistema_facturacion/
├── config/
│   └── database.php            # Configuración BD y constantes
├── includes/
│   ├── functions.php           # Funciones globales, helpers
│   ├── header.php              # Cabecera HTML + Sidebar
│   └── footer.php              # Cierre HTML + JS
├── assets/
│   ├── css/style.css           # Hoja de estilos principal
│   └── js/main.js              # JavaScript (sidebar, facturas, búsqueda)
├── auth/
│   ├── login.php               # Formulario de inicio de sesión
│   └── logout.php              # Cierre de sesión
├── modules/
│   ├── clientes/               # CRUD clientes
│   ├── productos/              # CRUD productos
│   ├── usuarios/               # CRUD usuarios
│   ├── facturas/               # Facturas (crear, ver, editar, imprimir)
│   └── configuracion/          # Config empresa + SMTP + SRI
├── sri/
│   ├── xml_generator.php       # Generador XML según spec SRI
│   ├── sri_service.php         # Comunicación SOAP con SRI
│   └── email_service.php       # Envío de correo con adjuntos
├── ajax/
│   └── productos_search.php    # Búsqueda de productos (autocomplete)
├── uploads/
│   ├── productos/              # Imágenes de productos
│   ├── usuarios/               # Fotos de usuarios
│   └── certificados/           # Certificados .p12 (protegidos)
├── index.php                   # Dashboard principal
├── database.sql                # Script de instalación BD
├── .htaccess                   # Seguridad Apache
└── README.md                   # Este archivo
```

---

## 🛡️ SEGURIDAD

- Contraseñas hasheadas con `bcrypt` (cost 12)
- Protección CSRF en todos los formularios
- Prepared statements PDO (prevención SQL injection)
- Sanitización de salida HTML
- Directorio `certificados/` bloqueado por .htaccess
- Control de roles: admin / cajero / bodeguero

---

## 📞 SOPORTE

Verifica la conexión al SRI en:  
https://srienlinea.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl

Para validar comprobantes:  
https://srienlinea.sri.gob.ec/sri-en-linea/inicio/NAP
