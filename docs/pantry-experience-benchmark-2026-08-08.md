# Complete99 pantry experience benchmark

Reviewed: 2026-08-08

This benchmark guides the 1.18.1 public pantry shelf. It compares discovery and
shopping patterns only. Complete99 does not copy third-party text, layout,
photography or product claims.

## Audience and task

The public shelf serves people who want to cook, eat, learn or buy. The first
screen must answer four questions quickly:

1. What can I buy?
2. How much does it cost and is it in stock?
3. What can I cook with it?
4. Where can I learn more?

Detailed allergens, storage, material, care and scientific connections remain
available, but they should not push the first product below the initial screen.

## Side-by-side patterns

| Reference | Useful pattern | Limitation observed | Complete99 response |
|---|---|---|---|
| [Sous Chef Japanese Food & Ingredients](https://www.souschef.co.uk/collections/japanese-food) | Food photography establishes appetite first. Cuisine and ingredient chips narrow the shelf. Cards keep product, price and action concise. | The reviewed page exposed a cookie layer and many console messages. Its commercial categories are not a scientific knowledge graph. | Use compact real filter links, twelve server-rendered cards per page and a visible route from every product to dishes and ingredient knowledge. Avoid modal interruption. |
| [Sous Chef all products](https://www.souschef.co.uk/collections/all) | Items-per-page controls, sorting and concise cards show how a large catalog avoids one endless document. | The amount of choice can become visually dense, and it does not explain the cultural or molecular context of every ingredient. | Keep pagination bounded and simple. Put secondary specifications in native expandable panels while preserving semantic HTML. |
| [Meshiagare: Flavors of Japan](https://artsandculture.google.com/project/japanese-food) | A large appetizing visual and plain invitation turn cultural knowledge into a journey rather than a database screen. | It is a discovery experience, not a purchasable pantry. | Carry the same visual and narrative principle into cuisine pages, then hand visitors to exact WooCommerce products without changing the source of price or stock. |
| [Taste of Spain](https://artsandculture.google.com/project/taste-of-spain) | Dishes, maps, ingredients, history and people are connected through playful themed entry points. | The journey does not need to solve cart, stock, fulfilment or product identity. | Connect cuisine, region, dish family, ingredient, technique, equipment and shelf while keeping each search intent on one canonical owner. |
| [Eataly pantry](https://www.eataly.com/nationwide-shipping/pantry) | The public proposition connects a physical food destination, products, learning and events. | Automated review received a 403 response, so no visual conclusion was drawn from that capture. | Preserve the physical Complete99 location as context, but do not let institutional or operational language enter the consumer shelf. |

## Measured Complete99 gap before 1.18.1

- `/store/` returned all 36 cards and 36 Product objects in one document.
- The response exceeded 180 KB in the live read and the first card began below
  the initial desktop and mobile viewports.
- Every ingredients, allergens, storage, equipment and relation row was expanded
  before the price and add-to-cart continuation.
- Product filters depended on JavaScript to hide cards. The query string did not
  define the server response.
- Product anchors assumed every product lived on the root store page, preventing
  safe pagination.
- Product order, filters, language alternates and schema did not share one
  explicit shelf-state contract.

## 1.18.1 acceptance contract

- Twelve products per server-rendered page.
- Real filter and page links that work without JavaScript.
- The first product begins inside a 1440 by 1000 desktop viewport and a 390 by
  844 mobile viewport.
- Price, stock, add-to-cart and the primary culinary guide remain visible.
- Detailed facts and secondary connections use native `details` and `summary`.
- All controls are at least 44 by 44 CSS pixels.
- Only the current page products appear in Product schema.
- Unfiltered pages retain current store indexability. Filtered utility states
  are `noindex,follow`.
- Canonical, Hebrew, English and x-default URLs preserve one validated shelf
  state.
- Every product link from a dish, ingredient, science page or museum route lands
  on the shelf page containing that product anchor.
- Add-to-cart returns to the same validated page and filter state.
- The exact 36 products, prices, stock and disabled payment state remain
  unchanged.

## Local visual evidence

The read-only captures are stored outside the release package under:

`C:\Users\pro\Documents\websites\.codex-tmp\complete99-benchmark-1.19`

Relevant captures:

- `sous-chef-japanese-1440.png`
- `google-arts-meshiagare-1440.png`
- `sous-chef-home-1440.png`

The Eataly capture records only the 403 response and is not used as visual
evidence.
