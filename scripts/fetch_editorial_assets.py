"""Fetch pinned photographs and site icons; never generates or replaces photos with AI.
Run from the project root. Images are resized/cropped by Unsplash's image CDN.
"""
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor
import urllib.request
import json

ROOT = Path(__file__).resolve().parents[1]
DEST = ROOT / 'assets' / 'images' / 'editorial'
PHOTOS = {
    'shopping': '1441986300917-64674bd600d8',
    'food': '1504674900247-0877df9cc836',
    'travel': '1507525428034-b723cf961d3e',
    'technology': '1498049794561-7780e7231661',
    'beauty': '1596462502278-27bfdc403348',
    'cinema': '1489599849927-2ee91cede3ba',
    'living': '1600585154340-be6161a56a0c',
    'fashion': '1483985988355-763728e1935b',
    'headphones-source': '1546435770-a3e426bf472b',
}
BRANDS = {'shopee':'shopee.vn','lazada':'lazada.vn','grab':'grab.com','be':'be.com.vn',
    'tiki':'tiki.vn','shopeefood':'shopeefood.vn','traveloka':'traveloka.com',
    'cgv':'cgv.vn','hasaki':'hasaki.vn','fpt-shop':'fptshop.com.vn',
    'coolmate':'coolmate.me','highlands':'highlandscoffee.com.vn'}

def fetch(item):
    name, url = item
    target = DEST / name
    if target.exists():
        return name, target.stat().st_size
    req = urllib.request.Request(url, headers={'User-Agent':'Mozilla/5.0 CouponHub-asset-fetch'})
    try:
        with urllib.request.urlopen(req, timeout=35) as response:
            data = response.read()
            if not response.headers.get('Content-Type','').startswith('image/'):
                raise ValueError('Expected image: '+name)
    except Exception:
        if name.endswith('.png'): return name+' (use text fallback)',0
        raise
    target.write_bytes(data)
    return name, len(data)

if __name__ == '__main__':
    DEST.mkdir(parents=True, exist_ok=True)
    jobs = [(name+'.webp','https://images.unsplash.com/photo-'+photo+'?auto=format&fit=crop&w=1200&q=82&fm=webp') for name,photo in PHOTOS.items()]
    jobs += [(name+'.png','https://www.google.com/s2/favicons?domain='+domain+'&sz=128') for name,domain in BRANDS.items()]
    with ThreadPoolExecutor(max_workers=5) as pool:
        for name,size in pool.map(fetch,jobs): print(name,size)
    (DEST/'sources.json').write_text(json.dumps({
        'photos':{name:{'source':'https://images.unsplash.com/photo-'+photo,'license':'https://unsplash.com/license','type':'photograph; CDN resize only'} for name,photo in PHOTOS.items()},
        'logos':{name:{'website':'https://'+domain,'source':'https://www.google.com/s2/favicons?domain='+domain+'&sz=128','type':'website icon; trademark belongs to its owner'} for name,domain in BRANDS.items()},
    }, indent=2),encoding='utf-8')
