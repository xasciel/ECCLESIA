# Publicación en InfinityFree

En GitHub, abre Settings → Secrets and variables → Actions → New repository secret.
Crea `FTP_PASSWORD` con la contraseña FTP de la cuenta de InfinityFree.
No agregues la contraseña al código ni al chat.

El workflow «Publicar en InfinityFree» corre al actualizar main o manualmente
mediante Actions → Publicar en InfinityFree → Run workflow.
Valida PHP y las pruebas de consultas antes de subir por FTPS con TLS verificado.

Destino: ftpupload.net:21, cuenta if0_42901477, /ecclesia.free.nf/htdocs.
Publica únicamente los nueve archivos indicados en .github/scripts/deploy.py.
Conserva database.php, archivos subidos y demás contenido del hosting.
El archivo database.php debe existir en htdocs antes del primer despliegue.
Si FTPS o la validación del certificado fallan, el despliegue se detiene.
La configuración FTP y la conexión real a MySQL requieren verificación en el hosting.
