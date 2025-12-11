#!/usr/bin/env python3
# fetch_all_products.py
# Scrape MikroTik, Ubiquiti, Cambium, Mimosa product lists and export CSV.
# Usage: python fetch_all_products.py
# Optional: set --use-selenium to enable selenium rendering for sites that require JS.

import requests
from bs4 import BeautifulSoup
import csv
import time
import re
import argparse
from datetime import datetime
from tqdm import tqdm
import pandas as pd
import os
import sys

# Optional selenium
USE_SELENIUM = False
try:
    from selenium import webdriver
    from selenium.webdriver.chrome.options import Options
    SELENIUM_OK = True
except Exception:
    SELENIUM_OK = False

REQUEST_HEADERS = {
    "User-Agent": "product-scraper/1.0 (+https://example.com) Python/requests"
}

OUTPUT_DIR = "output_products"
os.makedirs(OUTPUT_DIR, exist_ok=True)

# ---------- Helpers ----------
def clean_text(s):
    if s is None: return ''
    return re.sub(r'\s+', ' ', s).strip()

def save_to_csv(rows, filename):
    keys = ['category', 'brand', 'model', 'source_url']
    path = os.path.join(OUTPUT_DIR, filename)
    with open(path, 'w', newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, keys)
        writer.writeheader()
        for r in rows:
            writer.writerow({k: r.get(k,'') for k in keys})
    print(f"[+] Saved {len(rows)} rows to {path}")
    return path

def maybe_selenium_get(url, driver=None, wait_s=1.0):
    if driver:
        driver.get(url)
        time.sleep(wait_s)
        return driver.page_source
    else:
        r = requests.get(url, headers=REQUEST_HEADERS, timeout=20)
        r.raise_for_status()
        return r.text

# ---------- MikroTik scraper ----------
def fetch_mikrotik():
    """
    Scrape mikrotik.com/products and category pages.
    Works by crawling /products and category group pages.
    """
    base = "https://mikrotik.com"
    start = base + "/products"
    rows = []
    print("[mikrotik] Fetching main products page...")
    r = requests.get(start, headers=REQUEST_HEADERS, timeout=20)
    r.raise_for_status()
    soup = BeautifulSoup(r.text, "lxml")

    # Approach: find product links by CSS selectors - on mikrotik site products are anchors to /product/<slug> or group pages
    # We'll gather product links from the main page and from group pages
    links = set()
    # gather anchors to /product/ or /products/group or /products/<something>
    for a in soup.find_all('a', href=True):
        href = a['href']
        if href.startswith('/product') or href.startswith('/products/group') or href.startswith('/products/'):
            full = requests.compat.urljoin(base, href)
            links.add(full)

    # Also try direct group pages for categories known
    group_pages = [
        "https://mikrotik.com/products/group/ethernet-routers",
        "https://mikrotik.com/products/group/switches",
        "https://mikrotik.com/products/group/wireless-systems",
        "https://mikrotik.com/products/group/lte-5g",
        "https://mikrotik.com/products/group/60-ghz-products",
        "https://mikrotik.com/products/group/routerboard"
    ]
    for gp in group_pages:
        links.add(gp)

    print(f"[mikrotik] Found {len(links)} candidate pages — crawling them...")
    for url in tqdm(sorted(links)):
        try:
            page = requests.get(url, headers=REQUEST_HEADERS, timeout=20)
            page.raise_for_status()
            s = BeautifulSoup(page.text, "lxml")
            # product card titles often in h3 or h4 or .product-title
            # find image cards with product title
            # attempt multiple selectors:
            sel_candidates = [
                '.product-title', '.product-card__title', 'h3', 'h4', '.title', '.product-name'
            ]
            found = []
            for sel in sel_candidates:
                for el in s.select(sel):
                    txt = clean_text(el.get_text())
                    if txt and len(txt) < 120:
                        # try to find price/label parent link for URL
                        parent_a = el.find_parent('a', href=True)
                        source = parent_a['href'] if parent_a else url
                        if source and source.startswith('/'):
                            source = requests.compat.urljoin(base, source)
                        rows.append({
                            'category': '', 'brand': 'MikroTik', 'model': txt, 'source_url': source
                        })
                        found.append(txt)
            # fallback: list items with product names inside cards
            # also try .product-list .card
            for card in s.select('.product, .product-card, .card, .catalog-item'):
                text = clean_text(card.get_text())
                # heuristic: first line as name
                name = text.splitlines()[0] if text else ''
                if name and len(name) < 120:
                    rows.append({'category':'','brand':'MikroTik','model':name,'source_url':url})
        except Exception as e:
            # skip errors
            print("[mikrotik] skip", url, "err:", e)
            continue

    # dedupe by model name
    seen = set()
    unique = []
    for r in rows:
        key = (r['brand'].lower(), r['model'].lower())
        if key in seen: continue
        seen.add(key)
        unique.append(r)

    print(f"[mikrotik] Collected {len(unique)} unique models (raw).")
    # assign category guess based on keywords
    for r in unique:
        m = r['model'].lower()
        if any(k in m for k in ['switch','crs','switch','sfp','sg']):
            r['category'] = 'switch'
        elif any(k in m for k in ['hap','wAP','cap','ap','wireless','nano','sxt','lbe']):
            r['category'] = 'ap'
        elif any(k in m for k in ['ptp','ptmp','backhaul','c5','b5']):
            r['category'] = 'ptp'
        else:
            r['category'] = 'router'
    return unique

# ---------- Mimosa scraper ----------
def fetch_mimosa():
    rows = []
    base = "https://mimosa.co"
    start = base + "/products"
    print("[mimosa] fetching product listing...")
    r = requests.get(start, headers=REQUEST_HEADERS, timeout=20)
    r.raise_for_status()
    s = BeautifulSoup(r.text, "lxml")
    # Mimosa site often lists product links under /products/<slug>
    for a in s.select('a[href]'):
        href = a['href']
        if href.startswith('/products/') and href.count('/')>=2:
            full = requests.compat.urljoin(base, href)
            # get product name from link text
            name = clean_text(a.get_text())
            if name:
                rows.append({'category':'ptp','brand':'Mimosa','model':name,'source_url':full})

    # also check product pages categories
    # fallback: parse product category pages like /products/accessories, /products/antennas
    for cat in ['accessories','antennas','backhaul','access-points','clients']:
        try:
            page = requests.get(base + '/products/' + cat, headers=REQUEST_HEADERS, timeout=15)
            page.raise_for_status()
            ss = BeautifulSoup(page.text, "lxml")
            for a in ss.select('a[href]'):
                href = a['href']
                if href.startswith('/products/') and href.count('/')>=2:
                    n = clean_text(a.get_text())
                    if n:
                        rows.append({'category':'ptp','brand':'Mimosa','model':n,'source_url':requests.compat.urljoin(base,href)})
        except Exception:
            pass

    # dedupe
    seen = set()
    unique = []
    for r in rows:
        key = (r['brand'].lower(), r['model'].lower())
        if key in seen: continue
        seen.add(key)
        unique.append(r)
    print(f"[mimosa] Collected {len(unique)} unique models.")
    return unique

# ---------- Cambium scraper ----------
def fetch_cambium():
    rows = []
    base = "https://www.cambiumnetworks.com"
    start = base + "/products/"
    print("[cambium] fetching product pages (product-finder fallback)...")
    r = requests.get(start, headers=REQUEST_HEADERS, timeout=20)
    r.raise_for_status()
    s = BeautifulSoup(r.text, "lxml")

    # Cambium site has product finder and category pages. We'll try product finder which may use JS.
    # Try to find product links in page
    for a in s.select('a[href]'):
        href = a['href']
        if href.startswith('/products/') and href.count('/')>=2:
            full = requests.compat.urljoin(base, href)
            name = clean_text(a.get_text())
            if name:
                rows.append({'category':'ptp','brand':'Cambium','model':name,'source_url':full})

    # Try product finder page scraping
    try:
        pf = base + "/product-finder/"
        r2 = requests.get(pf, headers=REQUEST_HEADERS, timeout=20)
        r2.raise_for_status()
        s2 = BeautifulSoup(r2.text, "lxml")
        # find product names listed
        for el in s2.select('.product-listing, .pf-result, .product-card, .product'):
            txt = clean_text(el.get_text())
            if txt:
                name = txt.splitlines()[0]
                rows.append({'category':'ptp','brand':'Cambium','model':name,'source_url':pf})
    except Exception:
        pass

    # dedupe
    seen = set()
    unique = []
    for r in rows:
        key = (r['brand'].lower(), r['model'].lower())
        if key in seen: continue
        seen.add(key)
        unique.append(r)
    print(f"[cambium] Collected {len(unique)} unique models.")
    return unique

# ---------- Ubiquiti scraper ----------
def fetch_ubiquiti(use_selenium=False, driver=None):
    """
    Ubiquiti product lists are spread across multiple domains (ui.com, store.ui.com, help.ui.com).
    Best try: crawl store.ui.com collections & ubiquiti.com product pages.

    If JS renders lists, consider enabling selenium and passing a driver.
    """
    rows = []
    base_candidates = [
        "https://store.ui.com/collections/ubiquiti",  # store variants
        "https://www.ui.com/collections/unifi"  # sometimes used
    ]
    # Also try ubiquiti.com product pages
    base2 = "https://www.ui.com"
    # Try the UniFi Product page listing:
    candidates = [
        "https://www.ui.com/unifi/",
        "https://www.ui.com/edge/",
        "https://store.ui.com/collections/all",
        "https://store.ui.com/collections/unifi"
    ]
    tried = set()
    for url in candidates + base_candidates:
        if url in tried: continue
        tried.add(url)
        try:
            if use_selenium and driver:
                html = maybe_selenium_get(url, driver, wait_s=2.0)
            else:
                r = requests.get(url, headers=REQUEST_HEADERS, timeout=20)
                r.raise_for_status()
                html = r.text
            s = BeautifulSoup(html, "lxml")
            # look for product name markers
            for sel in ['.product-card__title','.product-title','h2','h3', '.product-title a', '.card-title']:
                for el in s.select(sel):
                    name = clean_text(el.get_text())
                    if name and len(name) < 200:
                        rows.append({'category':'ap','brand':'Ubiquiti','model':name,'source_url':url})
            # also anchors to /products/ or /collections/
            for a in s.select('a[href]'):
                href = a['href']
                if 'unifi' in href.lower() or 'airmax' in href.lower() or 'edge' in href.lower() or '/product' in href:
                    name = clean_text(a.get_text())
                    if name:
                        rows.append({'category':'ap','brand':'Ubiquiti','model':name,'source_url':requests.compat.urljoin(url, href)})
        except Exception as e:
            print("[ubiquiti] skip", url, "err:", e)
            continue

    # dedupe
    seen = set()
    unique = []
    for r in rows:
        key = (r['brand'].lower(), r['model'].lower())
        if key in seen: continue
        seen.add(key)
        unique.append(r)
    print(f"[ubiquiti] Collected {len(unique)} unique models (best effort).")
    return unique

# ---------- Main runner ----------
def run_all(use_selenium=False):
    rows_all = []

    # MikroTik
    try:
        mk = fetch_mikrotik()
        rows_all.extend(mk)
    except Exception as e:
        print("MikroTik failed:", e)

    # Ubiquiti (may need selenium)
    try:
        if use_selenium and SELENIUM_OK:
            # setup headless chrome
            chrome_opts = Options()
            chrome_opts.add_argument("--headless=new")
            chrome_opts.add_argument("--no-sandbox")
            chrome_opts.add_argument("--disable-dev-shm-usage")
            driver = webdriver.Chrome(options=chrome_opts)
            ub = fetch_ubiquiti(use_selenium=True, driver=driver)
            driver.quit()
        else:
            ub = fetch_ubiquiti(use_selenium=False, driver=None)
        rows_all.extend(ub)
    except Exception as e:
        print("Ubiquiti failed:", e)

    # Cambium
    try:
        cb = fetch_cambium()
        rows_all.extend(cb)
    except Exception as e:
        print("Cambium failed:", e)

    # Mimosa
    try:
        mm = fetch_mimosa()
        rows_all.extend(mm)
    except Exception as e:
        print("Mimosa failed:", e)

    # Normalize and dedupe across brands
    seen = set()
    unique = []
    for r in rows_all:
        key = (r['brand'].strip().lower(), re.sub(r'[^a-z0-9\(\)\- ]','', r['model'].strip().lower()))
        if key in seen:
            continue
        seen.add(key)
        # ensure fields
        r['category'] = r.get('category','')
        r['brand'] = r.get('brand','')
        r['model'] = r.get('model','')
        r['source_url'] = r.get('source_url','')
        unique.append(r)

    ts = datetime.utcnow().strftime('%Y%m%d_%H%M%S')
    filename = f"all_products_{ts}.csv"
    path = save_to_csv(unique, filename)

    # Also save brand-separated CSVs
    df = pd.DataFrame(unique)
    for brand, g in df.groupby('brand'):
        bn = re.sub(r'[^A-Za-z0-9]+','_', brand).strip('_').lower()
        subpath = os.path.join(OUTPUT_DIR, f"{bn}_{ts}.csv")
        g.to_csv(subpath, columns=['category','brand','model','source_url'], index=False)
        print(f"[+] Saved brand CSV: {subpath}")

    print("[*] Done. total unique models:", len(unique))
    return path

if __name__ == "__main__":
    ap = argparse.ArgumentParser()
    ap.add_argument('--use-selenium', action='store_true', help='Use Selenium (ChromeDriver) for JS-heavy sites')
    args = ap.parse_args()
    if args.use_selenium and not SELENIUM_OK:
        print("Selenium not available. Install selenium and chromedriver to use this mode.")
        sys.exit(1)
    run_all(use_selenium=args.use_selenium)
