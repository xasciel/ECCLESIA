ECCLESIA - PROTOTIPO INICIAL (NO APTO AÚN PARA PRODUCCIÓN)

1. Copia la carpeta ecclesia a C:\xampp\htdocs\
2. Inicia Apache y MySQL desde XAMPP.
3. Importa sql/schema.sql usando phpMyAdmin.
4. Revisa config/database.php y cambia las credenciales si corresponde.
5. Crea tu Superadmin desde consola:
   cd C:\xampp\htdocs\ecclesia
   C:\xampp\php\php.exe setup_admin.php admin@ejemplo.com "UnaClaveLargaYSegura123!"
6. Elimina setup_admin.php después de usarlo.
7. Abre http://localhost/ecclesia/
8. Registra una iglesia desde la página pública y apruébala desde el panel Superadmin.

INCLUIDO: autenticación, registro/aprobación de iglesias, aislamiento básico por iglesia,
personas, libros, actas, búsquedas básicas, subida de muestras, esquema de planes.

PENDIENTE: OCR, editor de formatos, PDF automático, historial/versionado completo,
matrimonios con dos personas, eliminación/restauración, membresías funcionales,
recuperación de contraseña, verificación de correo, limitación de intentos,
MFA, backups, apps móviles, permisos detallados, protección reforzada de archivos,
validación avanzada y pruebas de seguridad. NO SUBIR DATOS REALES SENSIBLES.

SEGURIDAD: para producción, mover uploads fuera de la carpeta pública y servirlos
con autorización, bloquear ejecución de scripts en uploads, configurar HTTPS,
credenciales separadas, CSP, límites de tasa y registros de acceso.
