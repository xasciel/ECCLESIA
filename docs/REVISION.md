# Revisión de ECCLESIA

## Implementado

- Inicio con contadores de la parroquia y botones para registrar actas, consultar
  libros, crear libros y abrir formatos.
- Libros en tarjetas con búsqueda sin distinción de acentos, filtros por tipo,
  orden por periodo/número/cantidad de actas y estado sin coincidencias.
- Navegación móvil, accesos rápidos con Ctrl/Cmd+K, foco de teclado,
  preferencias de movimiento reducido y versiones de CSS/JS para renovar caché.
- Búsqueda de actas con carga, reintento, limpiar y cancelación de solicitudes antiguas.
- Administración de colaboradores desde Equipo y permisos.

## Modelo de acceso

El responsable es la primera cuenta `church_admin` de cada parroquia por ID
(la cuenta creada al registrar la iglesia). Conserva acceso completo y es la única
que administra usuarios. Los demás usuarios de esa parroquia tienen consulta y
solo las capacidades adicionales concedidas: crear, editar, eliminar/archivar,
imprimir y administrar formatos/tipos. Este modelo no transfiere la titularidad.
El superadmin de la plataforma conserva sus pantallas administrativas separadas.

El rol existente `church_admin` se reutiliza por compatibilidad con el esquema
original; el rol por sí solo nunca concede acceso completo a un colaborador.
La titularidad y los permisos se comprueban en el servidor. Los permisos se
consultan de nuevo en cada petición y las cuentas desactivadas pierden acceso
en la siguiente petición. Los datos siempre se filtran por parroquia.

El responsable debe abrir Equipo y permisos y activar la función una sola vez.
Se crea la tabla adicional `ecclesia_user_permissions` mediante un POST con CSRF.
No se modifican libros, actas ni contraseñas existentes. Crear usuarios y guardar
sus permisos se ejecuta en una transacción y deja registro de auditoría.
La cuenta MySQL necesita permiso CREATE para esa activación.

Las invitaciones no envían correo: el responsable crea la contraseña inicial y
comparte los datos de acceso por un canal privado. La consulta corresponde al
archivo de la parroquia; no existe todavía filtrado por expediente individual.

## Siguientes mejoras recomendadas

1. Retirar `database.php` con credenciales del repositorio y rotar la contraseña
   expuesta en su historial. La configuración del hosting no se sube ni sobrescribe.
2. Respaldos y restauración, incluyendo uploads y base de datos.
3. Recuperación de contraseña, cambio de contraseña por el propio usuario y
   autenticación adicional para el responsable.
4. Un esquema SQL completo y un entorno de pruebas reproducible: el repositorio
   original no contiene el esquema de la base del hosting.
5. Revisión de rutas de archivos y autorización al servir documentos subidos.

## Verificación

- Pruebas de consultas y despliegue: `python -B -m unittest discover -s tests -v`.
- Permisos con SQLite: ejecutar `php tests/permissions_check.php <escenario>`
  para owner, collaborator, other_church_owner, tenant_mismatch y superadmin.
- Sintaxis: `php -l` en todos los PHP y `node --check assets/app.js`.
- Chromium con fixtures de SQLite: filtros, orden, estados vacíos, menú móvil,
  accesos por teclado, controles ocultos y recuperación de errores de búsqueda.
- Peticiones directas sin permisos: 403; sesión desactivada: redirección al acceso.

Las pruebas aisladas no demuestran acceso a la base MySQL de InfinityFree.
Tras desplegar se debe activar la función y verificar con un colaborador real.
