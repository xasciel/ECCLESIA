"""Publica los cambios sin modificar la configuración del hosting."""
import ftplib
import os
from pathlib import Path
import ssl

FILES = (
    'agregar.php', 'books.php', 'bootstrap.php', 'consulta.php',
    'imprimir_acta.php', 'layout.php', 'plantilla_campos.php',
    'records.php', 'ver_libro.php', 'assets/style.css',
)
ROOT = Path(__file__).resolve().parents[2]


def deploy():
    password = os.environ.get('FTP_PASSWORD')
    if not password:
        raise SystemExit('Falta el secreto FTP_PASSWORD en GitHub Actions.')

    for filename in FILES:
        if not (ROOT / filename).is_file():
            raise SystemExit(f'Falta el archivo: {filename}')

    with ftplib.FTP_TLS(
        context=ssl.create_default_context(), timeout=60
    ) as ftp:
        ftp.connect('ftpupload.net', 21)
        ftp.login('if0_42901477', password)
        ftp.prot_p()
        ftp.cwd('/ecclesia.free.nf/htdocs')

        if not any(
            name.rstrip('/').split('/')[-1] == 'database.php'
            for name in ftp.nlst('config')
        ):
            raise SystemExit('Falta config/database.php en el hosting.')

        folders = {
            name.rstrip('/').split('/')[-1]
            for name in ftp.nlst()
        }
        if 'assets' not in folders:
            ftp.mkd('assets')

        for filename in FILES:
            temporary = f'{filename}.upload{Path(filename).suffix}'
            with (ROOT / filename).open('rb') as source:
                ftp.storbinary(f'STOR {temporary}', source)

        for filename in FILES:
            temporary = f'{filename}.upload{Path(filename).suffix}'
            ftp.rename(temporary, filename)
            print(f'Publicado: {filename}')


if __name__ == '__main__':
    try:
        deploy()
    except (OSError, ftplib.Error, SystemExit) as exc:
        message = str(exc)
        password = os.environ.get('FTP_PASSWORD')
        if password:
            message = message.replace(password, '[oculto]')
        message = (
            message.replace('%', '%25')
            .replace('\r', '%0D')
            .replace('\n', '%0A')
        )
        print(
            f'::error title=Error de despliegue::{message}',
            flush=True,
        )
        raise SystemExit(1)