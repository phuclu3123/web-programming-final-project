"""Read-only smoke checks of public navigation, guide routes, and local assets."""
import json
import re
from collections import Counter
from html.parser import HTMLParser
from urllib.error import HTTPError
from urllib.parse import parse_qs, urlencode, urljoin, urlsplit
from urllib.request import urlopen

BASE = 'http://127.0.0.1:8000/CouponHub_UI/'

class HeroSlides(HTMLParser):
    def __init__(self, source):
        super().__init__()
        self.slides, self.current, self.depth = [], None, 0
        self.feed(source)
    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'div' and self.current is not None:
            self.depth += 1
        elif tag == 'div' and 'data-hero-slide' in a:
            self.current = {'id':int(a['data-coupon-id']), 'hidden':'hidden' in a, 'codes':[], 'details':set(), 'width':0}
            self.depth = 1
        if self.current is None:
            return
        if 'data-copy-code' in a:
            self.current['codes'].append(a['data-copy-code'])
        if tag == 'a' and '/coupons/' in a.get('href', ''):
            self.current['details'].add(int(a['href'].rsplit('/', 1)[-1]))
        if tag == 'img' and a.get('class') == 'studio-hero-photo':
            self.current['width'] = int(a['width'])
            self.current['photo'] = a['src']
            assert 'srcset' in a
    def handle_endtag(self, tag):
        if tag == 'div' and self.current is not None:
            self.depth -= 1
            if self.depth == 0:
                self.slides.append(self.current)
                self.current = None

class Page(HTMLParser):
    def __init__(self, source):
        super().__init__()
        self.ids, self.assets, self.links = [], set(), set()
        self.feed(source)
    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if a.get('id'):
            self.ids.append(a['id'])
        if tag in ('img', 'script') and a.get('src'):
            self.assets.add(a['src'])
        if tag == 'link' and a.get('rel') == 'stylesheet':
            self.assets.add(a['href'])
        if tag == 'a' and a.get('href'):
            self.links.add(a['href'])

def request(path, expected=200):
    address = urljoin(BASE, path)
    try:
        with urlopen(address, timeout=15) as r:
            status, data = r.status, r.read()
    except HTTPError as e:
        status, data = e.code, e.read()
    assert status == expected, (path, status, expected, data[:200])
    return data.decode('utf-8')

assets, links = set(), set()
pages = ['', 'categories', 'coupons', 'articles', 'campaigns', 'campaign-calendar',
         'guides/doc-dieu-kien-ma', 'guides/so-sanh-muc-tiet-kiem', 'guides/kiem-chung-cung-cong-dong',
         'articles?topic=tinh-toan', 'categories?category=1', 'campaigns?phase=UPCOMING',
         'coupons?keyword=khong-co-ket-qua-couponhub', 'login.php']
for path in pages:
    source = request(path)
    assert not re.search(r'(Fatal error|Parse error|<b>Warning</b>|Uncaught [A-Z])', source), path
    page = Page(source)
    assert not [key for key, count in Counter(page.ids).items() if count > 1], path
    if path in ('', 'categories', 'coupons', 'articles', 'campaigns'):
        assert 'product.css?v=' in source, path
        for navigation in ('categories', 'coupons', 'articles', 'campaigns'):
            assert '/CouponHub_UI/'+navigation in page.links, (path, navigation)
    assets.update(page.assets)
    links.update(page.links)
    if path == '':
        assert 'cover-layout-feature' in source and 'class="cover-shortcuts"' in source
        assert 'class="studio-cover-link"' not in source
        hero_source = source
    if path == 'categories':
        assert 'data-category-preview' in source
    if path == 'coupons':
        assert 'community-contributor' in source
        assert 'community-feed-header' in source and 'community-join' not in source
    if path == 'campaigns':
        assert 'campaign-deals-section' in source and 'campaign-agenda-list' not in source
        campaign_source = source
    if path == 'articles?topic=tinh-toan':
        assert source.count('class="guide-card"') == 1
    print('PASS', path or '/')

for path, status in [('guides/not-a-guide',404), ('articles?topic=invalid',422),
                     ('articles?topic[]=all',422), ('categories?category=invalid',422),
                     ('categories?category=999999',404), ('campaigns?preview=invalid',422),
                     ('campaigns?preview[]=1',422), ('campaigns?preview=999999',404)]:
    request(path,status)
    print('PASS', status, path)

for link in sorted(links):
    if re.search(r'/CouponHub_UI/(campaigns/\d+|coupons/\d+|guides/[^?#/]+)$', link):
        request(link)

preview_links = [link for link in Page(campaign_source).links if 'preview=' in link]
for link in preview_links:
    selected = parse_qs(urlsplit(link).query)['preview'][0]
    preview_source = request(link)
    assert 'data-campaign-preview="'+selected+'"' in preview_source
    assert not re.search(r'(Fatal error|Parse error|<b>Warning</b>)', preview_source)
print('PASS campaign coupon previews:', len(preview_links), 'selections and 3 invalid inputs')

data = json.loads(request('api/coupons/search'))
assert data['success'] and data['data'] and 'community-contributor' in data['cardsHtml']
assert 'submitter_email' not in data['cardsHtml']
active = {row['id']:row for row in data['data']}
for page in range(2,data['pagination']['totalPages']+1):
    active.update({row['id']:row for row in json.loads(request('api/coupons/search?page='+str(page)))['data']})
slides = HeroSlides(hero_source).slides
assert 'class="hero-copy"' not in hero_source
assert hero_source.index('data-hero-carousel') < hero_source.index('id="home-filter-form"')
assert 'community-cover-photo' in request('coupons')
for merchant in ['lazada','shopeefood']:
    with urlopen(BASE+'assets/images/editorial/'+merchant+'.png',timeout=10) as logo:
        assert logo.status==200 and logo.read(8)==b'\x89PNG\r\n\x1a\n'
assert len(slides) == 10 and sum(not slide['hidden'] for slide in slides) == 1
assert len({slide['id'] for slide in slides}) == len(slides)
assert len({slide['photo'] for slide in slides}) == len(slides)
assert 'community-post' in data['cardsHtml'] and 'coupon-photo' not in data['cardsHtml']
for slide in slides:
    assert slide['id'] in active
    assert slide['codes'] == ([active[slide['id']]['code']] if active[slide['id']]['code'] else [])
    assert slide['details'] == {slide['id']}
    assert slide['width'] >= 1800
single = request('?'+urlencode({'q':active[slides[0]['id']]['code']}))
assert len(HeroSlides(single).slides) == 1 and 'data-hero-prev' not in single
no_hero = request('?q=khong-co-ket-qua-couponhub')
assert not HeroSlides(no_hero).slides and 'data-hero-prev' not in no_hero
print('PASS hero: 10 active coupons, unique photos, matching copy/detail, 1800px+ images, single/empty states')
empty = json.loads(request('api/coupons/search?keyword=khong-co-ket-qua-couponhub'))
assert empty['success'] and empty['data'] == []
local = [a for a in assets if a.startswith('/CouponHub_UI/')]
for asset in sorted(local):
    with urlopen(urljoin(BASE, asset), timeout=10) as r:
        assert r.status == 200 and r.read(1), asset
print('PASS API: shared cards, empty results, public attribution')
print('PASS', len(local), 'local image/CSS/JS assets;', len(pages)+8, 'page checks')
