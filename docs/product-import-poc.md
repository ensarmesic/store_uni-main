# ASICS BiH product import PoC

Status: live import executed on 2026-08-16 using a project-local PHP 8.4.24 runtime. The catalogue is running at `http://127.0.0.1:8085/asics`.

## Access policy

Importers use public catalogue and product pages only. They do not authenticate, solve CAPTCHAs, rotate identities or bypass anti-bot controls. Requests have a descriptive User-Agent, 15-second timeout, delay between requests, three retries with exponential backoff and a hard PoC cap of 40 candidate pages per store/run. Before production use, the operator must confirm each store's current robots.txt and terms and obtain permission where required.

## Sources

### Sport Vision BiH

- URL: https://www.sportvision.ba/proizvodi/asics/page-1
- Discovery: public HTML catalogue; search index showed 454 ASICS items on 2026-08-16.
- Method: product links from HTML, then Schema.org Product/Offer JSON-LD on product pages; HTML size controls are a fallback for sizes.
- Fields: name, product URL, image, price/currency, availability, SKU/MPN/GTIN when exposed, EU sizes when exposed.
- Risks: catalogue totals and markup change; JSON-LD must be verified during each run. No public API/feed was found in public discovery.

### Buzz Sneakers BiH

- URL: https://www.buzzsneakers.ba/proizvodi/asics
- Discovery: public HTML catalogue showed 88 ASICS items on 2026-08-16 and exposed article codes, prices, and EU size labels.
- Method: product links from HTML, Product/Offer JSON-LD on details, HTML size controls as fallback.
- Fields: strong SKU, price, URL, image and size coverage; EAN depends on product JSON-LD.
- Risks: displayed size chart may include selectable sizes that are not in stock. The importer only records controls exposed on the product page; production validation should identify disabled/out-of-stock markup precisely.

### The Spot BiH (not imported)

- URL: https://www.thespot.ba/catalogsearch/result/?q=asics
- Discovery: the retailer states it carries ASICS; public search/catalogue pages are Magento-style HTML.
- Method evaluated: search result links, then Product/Offer JSON-LD and HTML size controls.
- Fields: expected name, SKU, price, URL, image and selectable sizes when exposed.
- Blocker: current `robots.txt` disallows `/catalogsearch/` and wildcard query URLs. The connector remains implemented but was not executed; no attempt was made to bypass the restriction.

| Store | Products discovered | Price | Sizes | Stock by size | SKU | EAN | Method |
|---|---:|---|---|---|---|---|---|
| Sport Vision | 24 imported (PoC cap) | Yes | Yes | Yes | Yes | No EAN found | HTML + JSON-LD |
| Buzz | 24 imported (PoC cap) | Yes | Yes | Yes | Yes | No EAN found | HTML + JSON-LD |
| The Spot | 0 (robots blocked) | — | — | — | — | — | Not executed |

## Live run results

- Offers imported: 48 (24 Sport Vision, 24 Buzz Sneakers).
- Master products: 47.
- Cross-store duplicates merged: 1 (`ASICS GEL-1130`, exact manufacturer code `1203A609-114`).
- Products with available-size data: 47/47; offers with sizes: 48/48.
- Available size variants: 649; offers currently carrying EU 44: 31.
- Products with SKU/MPN: 47/47. EAN/GTIN exposed by these pages: 0.
- Price-history rows: 48; refreshes at unchanged prices did not create duplicates.
- Failed product imports: 0.
- HTTP checks: `/asics` returned 200; `/asics?size=44` returned 200.
- Tests: 6 passed, 10 assertions.

## Architecture and behavior

Each shop has its own importer behind `StoreImporterInterface`. `ProductNormalizer` standardizes ASICS model names while preserving `offers.name_original`. `ProductMatcher` prioritizes EAN/GTIN, MPN, then exact brand/model and refuses a name match when EAN conflicts. Color constrains model matching. `ImportManager` upserts stores/offers/sizes, records price history only when price changes, logs `import_runs`, and invalidates catalogue caches.

The general `/catalog` page groups offers under master products and supports brand, model, gender, size, price, color, store, availability, sale, and sort controls. `/asics` remains as a compatible redirect to the ASICS filter. When a size is supplied, both catalogue minimum price and product-detail best price use only offers whose matching `offer_variants` row is `in_stock`. Merchant buttons point directly to original product URLs.

## Multi-brand expansion

The importer and normalizer now support ASICS, Nike, adidas, New Balance, Puma, Hoka, Skechers, Reebok, Salomon, Mizuno, On, Converse, and Under Armour footwear. Manufacturer-code matching is scoped by brand, so identical-looking codes from different manufacturers cannot collide.

### Sport Reality BiH

- URL: https://www.sportreality.ba/patike/page-1
- Robots status: the public footwear/product routes used by the connector are not disallowed.
- Method: public catalogue HTML to discover product links, then Product/Offer JSON-LD and the same available-size markup used by the regional commerce platform.
- Observed fields: brand, model, SKU, price, image, direct URL, gender text, and EU sizes.
- Connector status: implemented; live execution deferred because the current tool session reached its approved external-network usage limit.

### Intersport BiH

- URL: https://www.intersport.ba/sportovi/trcanje/obuca
- Robots status: category and direct product pages are allowed; `/catalogsearch` and search-query routes are avoided.
- Method: public category HTML plus Product/Offer JSON-LD on direct product pages.
- Observed brands include Nike, adidas, ASICS, Hoka, Salomon, Mizuno, and On.
- Connector status: implemented; live execution deferred because the current tool session reached its approved external-network usage limit.

The refreshed UI uses a responsive catalogue, brand shortcuts, live database statistics, a sticky filter panel, price/sale badges, available-size chips, custom pagination, and a redesigned product comparison page.

## Honest PoC limits

- The generic JSON-LD parser is intentionally conservative. First-run fixtures should be captured and selectors tailored per retailer before unattended production scheduling.
- Robots and terms can change; operational permission is not encoded in source code.
- Broad `Cache::flush()` is acceptable for this isolated PoC but should become tagged invalidation in a shared application.
- Expanding to 20+ stores is structurally straightforward (one importer plus fixtures per store), but source-specific validation, monitoring, permissions and selector maintenance remain the main cost.
