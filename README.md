# Sistema de Control y Gestión de Clientes - ISP

Sistema web en PHP + MySQL para administrar clientes, planes, contratos, pagos
y reportes de una empresa proveedora de servicio de internet.

## Módulos incluidos

1. Login / Autenticación
2. Usuarios y Roles (administrador / operador)
3. Registro de Clientes (alta, edición, baja lógica)
4. Planes de Internet
5. Contratos
6. Recibos y Pagos (recibo imprimible)
7. Búsqueda y Filtrado (integrado en el listado de clientes)
8. Reportes (clientes activos, suspendidos, morosos, ingresos mensuales)
9. Panel de Control (Dashboard)

## Requisitos

- PHP 8.0 o superior con la extensión `pdo_mysql`
- MySQL o MariaDB
- Un servidor local: XAMPP, WAMP, Laragon, o el servidor embebido de PHP

## Instalación

### 1. Base de datos

1. Abre phpMyAdmin (o el cliente de MySQL que uses) e importa el archivo
   `database/schema.sql`. Esto crea la base de datos `isp_clientes`, sus
   tablas y datos de ejemplo.
   - **Importante:** al importar, asegúrate de usar la codificación
     `utf8mb4` (en phpMyAdmin, selecciona "utf8mb4_general_ci" o
     "utf8mb4_unicode_ci" en el combo de cotejamiento antes de importar,
     o desde consola usa:
     `mysql -u root --default-character-set=utf8mb4 < database/schema.sql`)
     para que los nombres con tildes (Ñ, á, é, í, ó, ú) se guarden bien.

2. Si tu usuario/contraseña de MySQL no son `root` / (vacío), edita
   `config.php` con tus credenciales:

   ```php
   $DB_HOST = 'localhost';
   $DB_NAME = 'isp_clientes';
   $DB_USER = 'root';
   $DB_PASS = '';
   ```

### 2. Ejecutar el sistema

**Opción A: XAMPP / WAMP**
Copia toda la carpeta `sistema_isp` dentro de `htdocs` (XAMPP) o `www` (WAMP)
y visita `http://localhost/sistema_isp/login.php`.

**Opción B: Servidor embebido de PHP (rápido, para pruebas/demo)**
Desde la carpeta del proyecto:

```bash
php -S localhost:8000
```

Luego visita `http://localhost:8000/login.php`.

## Usuarios de prueba

| Correo                     | Contraseña   | Rol            |
|-----------------------------|--------------|----------------|
| admin@ispguate.com          | admin123     | administrador  |
| operador@ispguate.com       | operador123  | operador       |

El rol **operador** no puede acceder al módulo de Usuarios (queda restringido
solo a administradores).

## Notas para las pruebas con Selenium

- El módulo de **Login** ya está preparado para los casos CP-LOGIN-001,
  CP-LOGIN-002 y CP-LOGIN-003 documentados (credenciales válidas, contraseña
  incorrecta y campos vacíos).
- El módulo de **Clientes** valida el formato de DPI (13 dígitos numéricos),
  correspondiente al caso CP-CLI-002.
- Como las pruebas se ejecutan a través de la interfaz web (sin acceso al
  código interno), el tipo de prueba a documentar en el video es
  **Caja Negra**.

## Estructura de carpetas

```
sistema_isp/
├── config.php              Conexión a BD, sesión y funciones de apoyo
├── login.php / logout.php  Módulo 1: autenticación
├── index.php                Módulo 9: dashboard
├── usuarios_*.php           Módulo 2: usuarios y roles
├── clientes_*.php           Módulo 3 y 7: clientes, búsqueda y filtrado
├── planes_*.php             Módulo 4: planes de internet
├── contratos_*.php          Módulo 5: contratos
├── pagos_*.php               Módulo 6: recibos y pagos
├── reportes.php              Módulo 8: reportes administrativos
├── includes/                 Header, footer y navbar comunes
├── assets/css/style.css      Estilos del sistema
└── database/schema.sql       Script de base de datos y datos de ejemplo
```
