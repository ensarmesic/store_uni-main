"""Local catalogue image retrieval. No uploaded image leaves this process.

CLIP API: https://huggingface.co/docs/transformers/v4.56.0/en/model_doc/clip
Similarity ranks candidates; it is not identification confidence.
"""
import argparse
import concurrent.futures
import hashlib
import io
import json
import os
from pathlib import Path
import sys
import warnings
import urllib.parse
import urllib.request

os.environ.setdefault('HF_HUB_DISABLE_TELEMETRY', '1')
os.environ.setdefault('TOKENIZERS_PARALLELISM', 'false')
os.environ.setdefault('HF_HUB_DOWNLOAD_TIMEOUT', '120')
os.environ.setdefault('HF_HUB_DISABLE_SYMLINKS_WARNING', '1')


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('mode', choices=['index', 'search', 'download-model'])
    parser.add_argument('--storage', required=True)
    parser.add_argument('--manifest')
    parser.add_argument('--image')
    args = parser.parse_args()
    import numpy as np
    import torch
    from PIL import Image, ImageOps
    from transformers import CLIPImageProcessor, CLIPVisionModelWithProjection

    Image.MAX_IMAGE_PIXELS = 25_000_000
    warnings.simplefilter('error', Image.DecompressionBombWarning)
    root = Path(args.storage)
    root.mkdir(parents=True, exist_ok=True)
    model_id = 'openai/clip-vit-base-patch32'
    options = dict(cache_dir=str(root / 'models'), local_files_only=args.mode != 'download-model')
    processor = CLIPImageProcessor.from_pretrained(model_id, **options)
    model = CLIPVisionModelWithProjection.from_pretrained(model_id, use_safetensors=True, **options).eval()
    torch.set_num_threads(max(1, min(4, os.cpu_count() or 1)))
    if args.mode == 'download-model':
        print(json.dumps({'ready': True, 'model': model_id}))
        return

    def image_from_bytes(data):
        with Image.open(io.BytesIO(data)) as source:
            source.load()
            return ImageOps.exif_transpose(source).convert('RGB')

    def embed(images):
        with torch.inference_mode():
            vectors = model(**processor(images=images, return_tensors='pt')).image_embeds
            return torch.nn.functional.normalize(vectors, dim=-1).cpu().numpy()

    index_path = root / 'index.npz'
    if args.mode == 'search':
        with np.load(index_path, allow_pickle=False) as index:
            ids, vectors = index['ids'], index['vectors']
        if len(ids) == 0:
            print(json.dumps({'matches': [], 'indexed': 0}))
            return
        query = embed([image_from_bytes(Path(args.image).read_bytes())])[0]
        similarities = vectors @ query
        ranked = np.argsort(-similarities)[:12]
        print(json.dumps({'indexed': len(ids), 'matches': [
            {'product_id': int(ids[i]), 'similarity': round(float(similarities[i]), 4)} for i in ranked
        ]}))
        return

    manifest = json.loads(Path(args.manifest).read_text(encoding='utf-8-sig'))
    allowed = set(manifest['allowed_hosts'])
    items = manifest['items']
    cache = root / 'images'
    cache.mkdir(exist_ok=True)
    old = {}
    if index_path.exists():
        with np.load(index_path, allow_pickle=False) as index:
            old = {int(pid): (url, vec) for pid, url, vec in zip(index['ids'], index['urls'], index['vectors'])}

    class NoRedirect(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, req, fp, code, msg, headers, newurl):
            return None

    def download(item):
        try:
            url = item['url']
            parsed = urllib.parse.urlparse(url)
            if parsed.scheme != 'https' or parsed.hostname not in allowed or parsed.port not in (None, 443) or parsed.username:
                return None
            file = cache / (hashlib.sha256(url.encode()).hexdigest() + '.img')
            if not file.exists():
                request = urllib.request.Request(url, headers={'User-Agent': 'AErchiLens/1.0'})
                with urllib.request.build_opener(NoRedirect()).open(request, timeout=15) as response:
                    data = response.read(8 * 1024 * 1024 + 1)
                if len(data) > 8 * 1024 * 1024:
                    return None
                image = image_from_bytes(data)
                file.write_bytes(data)
            else:
                image = image_from_bytes(file.read_bytes())
            return item, image
        except Exception:
            return None

    output = dict(old) if manifest.get('partial') else {}
    pending = []
    for item in items:
        previous = old.get(item['product_id'])
        if previous is not None and previous[0] == item['url']:
            output[item['product_id']] = previous
        else:
            pending.append(item)
    failed = 0
    with concurrent.futures.ThreadPoolExecutor(max_workers=6) as executor:
        batch = []
        for result in executor.map(download, pending):
            if result is None:
                failed += 1
            else:
                batch.append(result)
            if len(batch) >= 16:
                vectors = embed([image for _, image in batch])
                for (item, image), vector in zip(batch, vectors):
                    output[item['product_id']] = (item['url'], vector)
                    image.close()
                batch = []
        if batch:
            vectors = embed([image for _, image in batch])
            for (item, image), vector in zip(batch, vectors):
                output[item['product_id']] = (item['url'], vector)
                image.close()
    ids = sorted(output)
    temporary = root / 'index.tmp.npz'
    np.savez_compressed(temporary, ids=np.array(ids, dtype=np.int64),
                        urls=np.array([output[i][0] for i in ids]),
                        vectors=np.stack([output[i][1] for i in ids]) if ids else np.empty((0, 512), dtype=np.float32))
    os.replace(temporary, index_path)
    print(json.dumps({'indexed': len(ids), 'failed': failed, 'processed': len(items)}))


if __name__ == '__main__':
    try:
        main()
    except Exception as exc:
        print(type(exc).__name__ + ': ' + str(exc), file=sys.stderr)
        sys.exit(1)
