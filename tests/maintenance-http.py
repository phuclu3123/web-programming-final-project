"""Verify migration maintenance blocks HTTP writers but not CLI recovery."""
from pathlib import Path
import json
import os
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
assert os.environ.get('COUPONHUB_RUN_DB_TEST') == '1', 'Explicit test opt-in required'
port = 8098
with socket.socket() as s:
    assert s.connect_ex(('127.0.0.1', port)) != 0, 'Test HTTP port is occupied'

with tempfile.TemporaryDirectory(prefix='couponhub-maintenance-') as temp:
    flag = Path(temp) / 'maintenance.lock'
    flag.write_text('Isolated maintenance test', encoding='utf-8')
    env = os.environ | {'COUPONHUB_MAINTENANCE_FILE':str(flag), 'COUPONHUB_APP_URL':f'http://127.0.0.1:{port}'}
    with (Path(temp) / 'server.log').open('w') as log:
        server = subprocess.Popen(['C:/xampp/php/php.exe', '-S', f'127.0.0.1:{port}', 'router.php'], cwd=ROOT,
                                  env=env, stdout=log, stderr=log,
                                  creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
        try:
            def request(path, data=None, headers=None):
                req = urllib.request.Request(f'http://127.0.0.1:{port}' + path, data=data, headers=headers or {})
                try: response = urllib.request.urlopen(req, timeout=5)
                except urllib.error.HTTPError as error: response = error
                return response.status, response.headers, response.read().decode()
            for _ in range(40):
                try:
                    status, headers, body = request('/')
                    break
                except OSError:
                    assert server.poll() is None
                    time.sleep(.1)
            else: raise AssertionError('Test server unavailable')
            assert status == 503 and headers['Retry-After'] == '60' and headers['Cache-Control'] == 'no-store'
            assert 'bảo trì' in body
            for path, data in [('/api/login.php', None), ('/api/login.php', b'account=blocked-test')]:
                status, headers, body = request(path, data)
                assert status == 503 and headers['Content-Type'].startswith('application/json')
                assert json.loads(body)['ok'] is False
            cli = subprocess.run(['C:/xampp/php/php.exe','-r',"require 'app/bootstrap.php'; echo 'CLI recovery available';"],
                                 cwd=ROOT,env=env,capture_output=True)
            assert cli.returncode == 0 and cli.stdout == b'CLI recovery available'
            flag.unlink()
            status, _, _ = request('/api/login.php')
            assert status != 503, 'Maintenance did not end after removing its flag'
            print('PASS maintenance: HTTP GET/POST blocked with 503, API JSON, CLI recovery allowed, flag removal restores routing.')
        finally:
            server.terminate()
            server.wait(timeout=10)
