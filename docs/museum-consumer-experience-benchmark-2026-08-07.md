# Complete99 museum consumer experience benchmark

Date: 2026-08-07

## Decision

The museum entrance must behave like a living world pantry for hungry people. Food, place, color and a clear next action come first. Research, source notes and technical detail remain available after the visitor has found something worth opening.

The public order is:

1. A sensory promise and a large culinary image.
2. Two immediate choices: discover dishes or enter the pantry.
3. Image-led doors into Japan, Syria and Lebanon.
4. Shelves for dishes, ingredients, the store and traditions.
5. Intent paths for eating, cooking, learning, shopping and group meals.
6. A natural Syria to Lebanon discovery bridge.
7. Supporting reading and sources inside a collapsed section at the end.

## Baseline observed on the live page

The previous live page opened like a research record. On mobile, the title, status labels, source count and update date appeared before the main image. The visitor then met research framing and relationship cards before being offered a satisfying culinary route.

This did not reflect the primary public intent:

- I am hungry and want to see dishes.
- I want to discover a cuisine.
- I want to browse a pantry shelf.
- I want to cook or learn.
- I want to buy something.
- I am planning a meal for a group or company.

## Global references

| Reference | Pattern worth adopting | Complete99 adaptation |
|---|---|---|
| [Japan House London, Eat and Drink](https://www.japanhouselondon.uk/eat-and-drink/) | One strong cultural image, a short sensory introduction and clear doors into related experiences | Each cuisine opens as a cultural room with dishes, ingredients, methods, stories and a connected store shelf |
| [Food52 Recipes](https://food52.com/pages/recipes) | A simple promise, generous food photography and browsing by what a person wants to make or eat | The museum exposes dishes and ingredient-led discovery before research terminology |
| [Great British Chefs, Middle Eastern recipes](https://www.greatbritishchefs.com/collections/middle-eastern-recipes) | Food photography dominates the first screen and the visitor can continue into recipes, chefs, methods and ingredients | Cuisine pages lead visually into dishes, regional tables, pantry products and practical guides |
| [Gastro Obscura](https://www.atlasobscura.com/gastro) | Strong color, curiosity-led headlines and several distinct ways to explore food and place | Complete99 uses vivid cuisine doors, appetizing shelf language and cross-cuisine discovery |
| [Eataly](https://www.eataly.com/us_en) | Food, stories, products, restaurants and classes belong to one commercial world | Museum, knowledge, store, group meals and future learning remain one connected WordPress experience |
| [TasteAtlas](https://www.tasteatlas.com/) | Exploration by geography, dishes and nearby relationships | Each cuisine can expand into regions, dishes, traditions, institutions and neighboring cuisines |
| [Serious Eats, World Cuisines](https://www.seriouseats.com/world-cuisine-guides-5117177) | Science is presented through practical questions about why a method works | Molecular depth is introduced as taste, texture, heat and what happens in the pot, with full detail available deeper in the page |
| [MasterClass Culinary](https://www.masterclass.com/categories/culinary) | Visitors choose a learning outcome and continue into a structured path | Guides can later become courses connected to dishes, ingredients, equipment and kits |

## Before to after mapping

| Previous public signal | Consumer-first replacement |
|---|---|
| Source count and update status in the first screen | Large world-pantry image and two clear actions |
| Research language before discovery | Food and culture first, research later |
| One generic relationship path | Cuisine cards, shelves and intent-based paths |
| Sources displayed as a primary section | Collapsed supporting reading at the end |
| No immediate store or group-meal path | Direct store shelf and proposal routes |
| Syria and Lebanon visible only as database relations | A visible, contextual Syria to Lebanon journey |
| Technical labels such as structure and relationships | Natural language such as what goes well together |

## Implementation acceptance

- Hebrew root route: `/museum/`
- English root route: `/en/museum/`
- All visible cards are links with real destinations.
- All English destinations remain under `/en/`.
- The first screen contains a local responsive image and two actions.
- Japan, Syria and Lebanon have image-led entry cards.
- Dishes, ingredients, store and traditions are visible as shelves.
- Knowledge, group meals and the Syria to Lebanon route are visible.
- Supporting sources are collapsed and remain accessible.
- Focus states, reduced motion and mobile layouts are included.
- No public project status, internal operations language or placeholder links appear.

## Visual evidence

Evidence directory:

`C:\Users\pro\Documents\websites\complete99-wordpress\output\playwright\museum-benchmark-2026-08-07`

Key files:

- `before-live-complete99-museum-1440.webp`
- `before-live-complete99-museum-390.webp`
- `benchmark-global-reference-contact-sheet.webp`
- `benchmark-japan-house-eat-drink-1440-clean.webp`
- `benchmark-food52-recipes-1440-clean.webp`
- `benchmark-great-british-chefs-middle-east-1440-clean.webp`
- `benchmark-gastro-obscura-1440-clean.webp`

Post-release desktop and mobile captures must be added to the same directory before final acceptance.
