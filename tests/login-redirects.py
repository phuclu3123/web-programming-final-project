"""Real login redirects and role guards at root/subfolder URLs, on the isolated DB.

Requires the demo fixture in couponhub_merge_test on 127.0.0.1:13379.
COUPONHUB_RUN_HTTP_TEST=1 python tests/login-redirects.py [--php path/to/php]
Only logs in and reads pages; never seeds or changes database records.
"""
import argparse
import http.cookiejar
import json
import os
from pathlib import Path
import re
import subprocess
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--php', default='C:/xampp/php/php.exe')
args = parser.parse_args()
if os.environ.get('COUPONHUB_RUN_HTTP_TEST') != '1':
    raise SystemExit('SKIP: set COUPONHUB_RUN_HTTP_TEST=1 for isolated login checks.')
assert not (ROOT / 'config/local.php').exists(), 'Local DB override could bypass test isolation'
env = os.environ | {'COUPONHUB_DB': 'couponhub_merge_test', 'COUPONHUB_HOST': '127.0.0.1',
                    'COUPONHUB_PORT': '13379', 'COUPONHUB_USER': 'root', 'COUPONHUB_PASSWORD': ''}
env.pop('COUPONHUB_APP_URL', None)
accounts = [('admin_demo', 'AdminDemo!2026', '/admin'),
            ('merchant_shops', 'MerchantDemo!2026', '/merchant'),
            ('merchant_services', 'MerchantDemo!2026', '/merchant'),
            ('merchant_lifestyle', 'MerchantDemo!2026', '/merchant'),
            ('user_demo', 'UserDemo!2026', '/index.php'),
            ('user_community', 'UserDemo!2026', '/index.php')]


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


class Client:
    def __init__(self, origin, base):
        self.origin, self.base = origin, base
        self.open = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirect())

    def request(self, path, data=None, accept='text/html'):
        body = urllib.parse.urlencode(data).encode() if data is not None else None
        try:
            response = self.open.open(urllib.request.Request(self.origin + path, data=body, headers={'Accept': accept}), timeout=10)
        except urllib.error.HTTPError as error:
            response = error
        return response.status, response.headers, response.read().decode('utf-8')

    def login(self, username, password, return_url='', ajax=True):
        status, _, html = self.request(self.base + '/login.php')
        assert status == 200, ('Login form', status)
        token = re.search(r'name="csrf" value="([a-f0-9]+)"', html).group(1)
        data = {'csrf': token, 'account': username, 'password': password, 'return_url': return_url}
        return self.request(self.base + ('/api/login.php' if ajax else '/login'), data,
                            'application/json' if ajax else 'text/html')


for base, port in [('', 8088), ('/' + ROOT.name, 8089)]:
    # Root uses the project router; subfolder mirrors a parent document root,
    # where the PHP built-in server finds the project's index.php itself.
    session_dir = ROOT / '.runtime' / 'login-redirect-sessions'
    session_dir.mkdir(parents=True, exist_ok=True)
    command = [args.php, '-d', 'session.save_path=' + str(session_dir),
               '-S', f'127.0.0.1:{port}', '-t', str(ROOT if not base else ROOT.parent)]
    if not base:
        command.append(str(ROOT / 'router.php'))
    origin = f'http://127.0.0.1:{port}'
    log_path = ROOT / '.runtime' / f'login-redirects-{port}.log'
    log_path.parent.mkdir(exist_ok=True)
    with log_path.open('w', encoding='utf-8') as log:
        process = subprocess.Popen(command, cwd=ROOT, env=env, stdout=log, stderr=log,
                                   creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0))
        try:
            for attempt in range(40):
                try:
                    status = Client(origin, base).request(base + '/login.php')[0]
                    assert status == 200, ('Login page failed', status, str(log_path))
                    break
                except OSError:
                    if process.poll() is not None:
                        raise AssertionError('PHP server failed to start; inspect ' + str(log_path))
                    time.sleep(0.1)
            else:
                raise AssertionError('PHP server did not become ready')
            guest = Client(origin, base)
            for path in ['/admin', '/merchant']:
                assert guest.request(base + path)[0] == 303, 'Guest guard'
            for username, password, home in accounts:
                client = Client(origin, base)
                status, _, body = client.login(username, password)
                result = json.loads(body)
                assert status == 200 and result['success'], (username, status, body[:200])
                assert result['redirect'] == base + home, (username, result['redirect'])
                status, _, html = client.request(result['redirect'])
                assert status == 200, ('Destination did not render', username, status, html[:200])
                if home != '/index.php':
                    assert 'Xem trang người dùng' in html, 'Public navigation for center users'
                    assert client.request(base + '/')[0] == 200, 'Public browsing does not change role'
                blocked = '/merchant' if home == '/admin' else '/admin'
                assert client.request(base + blocked)[0] == 403, 'Cross-role center blocked'
                status, headers, _ = client.request(base + '/login.php')
                assert status == 303 and headers['Location'] == base + home, 'Already-authenticated login route'
                # Non-JS form submission must use the identical target.
                plain = Client(origin, base)
                status, headers, _ = plain.login(username, password, ajax=False)
                assert status == 303 and headers['Location'] == base + home, 'HTML form target'
            for username, password, return_url, expected in [
                ('admin_demo', 'AdminDemo!2026', 'admin.php?section=reports', '/admin/reports'),
                ('merchant_shops', 'MerchantDemo!2026', 'shop.php', '/merchant'),
                ('admin_demo', 'AdminDemo!2026', base + '/admin/users', '/admin/users'),
                ('user_demo', 'UserDemo!2026', base + '/admin', '/index.php'),
                ('merchant_services', 'MerchantDemo!2026', '//evil.example/admin', '/merchant'),
            ]:
                client = Client(origin, base)
                status, _, body = client.login(username, password, return_url)
                result = json.loads(body)
                assert status == 200 and result['redirect'] == base + expected, ('Safe return', result)
                assert client.request(result['redirect'])[0] == 200, 'Safe return destination renders'
            print(f'PASS: {base or "/"} — all six demo logins, JSON/HTML targets, rendered centers, safe returns and role isolation.', flush=True)
        finally:
            process.terminate()
            process.wait(timeout=10)
