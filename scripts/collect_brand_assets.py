"""Inspect official brand pages, then download only reviewed image URLs from the manifest."""
import argparse
import io
import json
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path
from html.parser import HTMLParser
from urllib.parse import urljoin, urlparse
from urllib.request import Request, urlopen
from PIL import Image, ImageOps

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'assets/images/brands'
PAGES = {
    'grab': 'https://www.grab.com/vn/pressdownload/',
    'highlands': 'https://www.highlandscoffee.com.vn/vn/highlands-teas.html',
    'coolmate': 'https://www.coolmate.me/',
    'be': 'https://be.com.vn/',
    'traveloka': 'https://www.traveloka.com/vi-vn',
}

def fetch(url):
    with urlopen(Request(url, headers={'User-Agent': 'Mozilla/5.0'}), timeout=35) as response:
        return response.read()

class Images(HTMLParser):
    def __init__(self, base):
        super().__init__()
        self.base = base
        self.items = []
    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'img':
            src = a.get('data-src') or a.get('src', '')
            if src and not src.startswith('data:'):
                self.items.append((a.get('alt', '')[:90], urljoin(self.base, src)))
        if tag == 'a' and any(x in a.get('href', '').lower() for x in ('.jpg', '.jpeg', '.png')):
            self.items.append(('download', urljoin(self.base, a['href'])))
        if tag == 'link' and 'icon' in a.get('rel',''):
            self.items.append(('icon',urljoin(self.base,a.get('href',''))))

parser = argparse.ArgumentParser()
parser.add_argument('--inspect', action='store_true')
parser.add_argument('--hero', action='store_true')
parser.add_argument('--inspect-logos', action='store_true')
parser.add_argument('--logos', action='store_true')
args = parser.parse_args()
if args.logos:
    selected={'lazada-wordmark':'https://img.lazcdn.com/g/tps/images/ims-web/TB1T7K2d8Cw3KVjSZFuXXcAOpXa.png','shopeefood':'https://shopeefood.vn/favicon.ico'}
    for key,source in selected.items():
        try:
            picture=Image.open(io.BytesIO(fetch(source))).convert('RGBA')
            picture.save(ROOT/'assets/images/editorial'/(key+'.png'))
            if key=='lazada-wordmark':
                picture.crop((0,0,96,108)).save(ROOT/'assets/images/editorial/lazada.png')
            print(key,picture.size,source,flush=True)
        except Exception as error:
            print(key,type(error).__name__,str(error),flush=True)
elif args.inspect_logos:
    for page in ['https://shopeefood.vn/','https://www.lazada.vn/']:
        images=Images(page)
        images.feed(fetch(page).decode('utf-8','replace'))
        print(page,json.dumps([i for i in images.items if any(k in str(i).lower() for k in ['logo','icon'])],ensure_ascii=True),flush=True)
elif args.hero:
    # Fetch the original photograph, not the width-1069 derivative. Never upscale.
    hero_dir = ROOT / 'assets/images/hero'
    hero_dir.mkdir(exist_ok=True)
    brands = json.loads((OUT / 'sources.json').read_text(encoding='utf-8'))
    editorial = json.loads((ROOT / 'assets/images/editorial/sources.json').read_text(encoding='utf-8'))
    selected = []
    for item in brands['assets']:
        if item['key'] in ['coolmate-men','coolmate-women','grab-bike','grab-car','grab-food']:
            selected.append({'key':'brand-'+item['key'], 'source':item['image_url'].split('?')[0], 'page':item['page'], 'rights':brands['usage']})
    for key in ['shopping','travel','technology','fashion','food','beauty','cinema','headphones-source']:
        photo = editorial['photos'][key]
        selected.append({'key':'category-'+key, 'source':photo['source']+'?auto=format&fit=max&w=2560&q=95', 'page':photo['source'], 'rights':photo['license']})
    selected.append({'key':'travel-vivid', 'source':'https://images.unsplash.com/photo-1746138810419-dfafd3603c02?auto=format&fit=max&w=2560&q=95', 'page':'https://unsplash.com/photos/OtjpzW_h8eA', 'rights':'https://unsplash.com/license'})
    def download_hero(item):
        target = hero_dir / (item['key']+'.webp')
        if not target.exists():
            picture = ImageOps.exif_transpose(Image.open(io.BytesIO(fetch(item['source'])))).convert('RGB')
            item['source_dimensions'] = list(picture.size)
            picture.thumbnail((2560,1800), Image.Resampling.LANCZOS)
            picture.save(target,'WEBP',quality=93,method=6)
        with Image.open(target) as saved:
            item['dimensions'] = list(saved.size)
        item['bytes'] = target.stat().st_size
        print(item['key'],item['dimensions'],item['bytes'],flush=True)
        return item
    with ThreadPoolExecutor(max_workers=3) as pool:
        completed = list(pool.map(download_hero,selected))
    (hero_dir / 'sources.json').write_text(json.dumps({'processing':'Original photographs, resized down only; no AI or upscaling.','assets':completed},ensure_ascii=False,indent=2),encoding='utf-8')
elif args.inspect:
    for name, page in PAGES.items():
        try:
            images = Images(page)
            images.feed(fetch(page).decode('utf-8', 'replace'))
            print(name, json.dumps(list(dict.fromkeys(images.items))[:45], ensure_ascii=True))
        except Exception as err:
            print(name, type(err).__name__, str(err))
else:
    manifest = json.loads((OUT / 'sources.json').read_text(encoding='utf-8'))
    for item in manifest['assets']:
        host = urlparse(item['image_url']).hostname or ''
        if not any(host == domain or host.endswith('.'+domain) for domain in manifest['allowed_domains']):
            raise ValueError('Image host is outside reviewed official domains')
        target = OUT / (item['key']+'.webp')
        if target.exists():
            continue
        picture = ImageOps.exif_transpose(Image.open(io.BytesIO(fetch(item['image_url']))))
        picture.thumbnail((1440, 1100))
        picture.save(target, 'WEBP', quality=85, method=6)
        print(item['key'], picture.size, target.stat().st_size)
