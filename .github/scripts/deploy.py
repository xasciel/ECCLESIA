"""Publica los archivos revisados sin tocar database.php ni archivos del usuario."""
import ftplib
import os
from pathlib import Path
import ssl

FILES = (
    'agregar.php', 'books.php', 'bootstrap.php', 'consulta.php',
    'imprimir_acta.php', 'layout.php', 'plantilla_campos.php',
    'records.php', 'ver_libro.php',
)
ROOT = Path(__file__).resolve().parents[2]


def deploy():
    password = os.environ.get('FTP_PASSWORD')
    if not password:
        raise SystemExit('Falta configurar el secreto FTP_PASSWORD en GitHub Actions.')
    for filename in FILES:
        if not (ROOT / filename).is_file():
            raise SystemExit(f'Falta el archivo: {filename}')
    with ftplib.FTP_TLS(context=ssl.create_default_context(), timeout=60) as ftp:
        ftp.connect('ftpupload.net', 21)
        ftp.login('if0_42901477', password)
        ftp.prot_p()
        ftp.cwd('/ecclesia.free.nf/htdocs')
        # bootstrap.php necesita este archivo, que contiene la configuración local.
        if 'database.php' not in ftp.nlst():
            raise SystemExit('Falta database.php en el hosting; no se publicaron cambios.')
        for filename in FILES:
            with (ROOT / filename).open('rb') as source:
                ftp.storbinary(f'STOR {filename}.upload', source)
        # Se transfieren todos antes de reemplazar los archivos publicados.
        for filename in FILES:
            ftp.rename(f'{filename}.upload', filename)
            print(f'Publicado: {filename}')
        ftp.quit()


if __name__ == '__main__':
    deploy()
