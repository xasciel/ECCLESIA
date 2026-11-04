"""Pruebas del despliegue con FTP simulado, sin acceder al hosting."""
import importlib.util
from pathlib import Path
from tempfile import TemporaryDirectory
import unittest
from unittest.mock import MagicMock, patch

spec = importlib.util.spec_from_file_location('deploy', Path(__file__).resolve().parents[1] / '.github/scripts/deploy.py')
deploy = importlib.util.module_from_spec(spec)
spec.loader.exec_module(deploy)

class DeployTest(unittest.TestCase):
    def setUp(self):
        self.temp = TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        root = Path(self.temp.name)
        for filename in deploy.FILES:
            p = root / filename
            p.parent.mkdir(parents=True, exist_ok=True)
            p.write_text('fixture')
        self.root = patch.object(deploy, 'ROOT', root)
        self.root.start()
        self.addCleanup(self.root.stop)

    def run_deploy(self, config):
        ftp = MagicMock()
        ftp.nlst.side_effect = [config, ['assets']]
        with patch.dict('os.environ', {'FTP_PASSWORD': 'test-only'}), patch.object(deploy.ftplib, 'FTP_TLS') as factory, patch('builtins.print'):
            factory.return_value.__enter__.return_value = ftp
            deploy.deploy()
            self.assertTrue(factory.call_args.kwargs['context'].check_hostname)
        return ftp

    def test_uploads_styles_and_code_without_configuration(self):
        ftp = self.run_deploy(['config/database.php'])
        uploaded = [c.args[0] for c in ftp.storbinary.call_args_list]
        self.assertTrue(any('assets/style.css' in c for c in uploaded))
        self.assertFalse(any('database.php' in c for c in uploaded))
        self.assertEqual(len(uploaded), len(deploy.FILES))
        ftp.prot_p.assert_called_once()
        self.assertEqual(ftp.rename.call_count, len(deploy.FILES))

    def test_missing_configuration_stops_before_upload(self):
        with self.assertRaisesRegex(SystemExit, 'Falta config/database.php'):
            self.run_deploy([])

    def test_missing_password_never_connects(self):
        with patch.dict('os.environ', {}, clear=True), patch.object(deploy.ftplib, 'FTP_TLS') as factory:
            with self.assertRaisesRegex(SystemExit, 'FTP_PASSWORD'):
                deploy.deploy()
            factory.assert_not_called()
