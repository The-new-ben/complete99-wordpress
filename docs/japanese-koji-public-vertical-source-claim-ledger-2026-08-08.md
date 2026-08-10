# Japanese koji to shoyu public vertical source and claim ledger

Status: post-1.19 Culinary Science v20 candidate, noindex public discovery.
Reviewed: 2026-08-08. This ledger records agent-led source, bilingual and
boundary review. It does not claim named human, food-safety, legal or owner
approval. Live publication and indexing remain held pending explicit owner
approval.

## Publication boundary

- Public owners are exactly `ingredient-shoyu-koji`, `equipment-kioke`,
  `guide-koji-hydrolysis` and `standard-jas-shoyu-1703`.
- `reaction-koji-enzymatic-hydrolysis` is a section owned by the hydrolysis
  guide, not a competing standalone canonical.
- `producer-yamaroku-shoyu` and all retail listing records remain private.
- The zero-source reaction-to-shoyu and shoyu-to-Edomae relations remain
  private. No producer, supplier, certification, lot or product-conformity
  claim may be inferred.
- The pages remain `noindex, follow`; WooCommerce payment and checkout state is
  unchanged and held.

## Source register

| Source ID | Authority and URL | Registry retrieval | Checked scope | Explicit exclusion |
|---|---|---:|---|---|
| `maff-fermented-foods` | Japan Ministry of Agriculture, Forestry and Fisheries, [An Introduction to Japanese Fermented Foods](https://www.maff.go.jp/j/keikaku/syokubunka/traditional-foods/files/user/pdf/an_Introduction_to_Japanese_fermented_foods.pdf) | 2026-08-05 | Generic koji role, shoyu substrate distinction, starch and protein breakdown | No SKU, strain, ratio, time, temperature, enzyme activity or product result |
| `maff-hon-mirin` | Japan Ministry of Agriculture, Forestry and Fisheries, [An Introduction to Japanese Fermented Foods, part 2](https://www.maff.go.jp/j/keikaku/syokubunka/traditional-foods/files/user/pdf/an_Introduction_to_Japanese_fermented_foods_part2.pdf) | 2026-08-05 | Additional generic koji and hydrolysis context | Not shoyu product or process evidence |
| `yamaroku-about` | [Yamaroku official producer page](https://yama-roku.net/en/about) | 2026-08-05 | A documented example of using and preserving kioke in shoyu production | No supplier relationship, universal benefit, product certification, volume, age or quality inference |
| `zhang-industrial-koji-proteases-2023` | Zhang, Kang and Xu, Microbiology Spectrum, [PMC10100866](https://pmc.ncbi.nlm.nih.gov/articles/PMC10100866/) | 2026-08-08 | Three industrial *Aspergillus oryzae* strains, 46 h study context and exact reported enzyme-assay ranges | Not a Complete99, supplier, product, lot, recipe or operating range |
| `jas-shoyu-1703` | Food and Agricultural Materials Inspection Center, [JAS 1703:2021 Soy sauce, tentative English translation](https://www.famic.go.jp/english/jas/_doc/jas1703.pdf) | 2026-08-05; exact receipt refreshed 2026-08-08 | Shoyu-koji and saishikomi definitions plus category thresholds | Not certification, conformity, grade or measurement of Yamaroku, Complete99, a shipment or lot; Japanese original controls |

## Immutable evidence receipts

| Source ID | Checked-in evidence | Evidence SHA256 | Upstream SHA256 | Locator scope |
|---|---|---|---|---|
| `zhang-industrial-koji-proteases-2023` | `docs/research-evidence/pmc10100866-koji-protease-evidence.json` | `44752ca77d881e2ccc71d7dc2fb4c2d9051c2207b7e67553b81abdc0206c4de5` | `fd57c0cdf14beb447ad47a0561cfc8c6fac1d356ce9cde64b10a2dbd2e1266c3` | Figure 2B results paragraph and Enzyme activity assays methods |
| `jas-shoyu-1703` | `docs/research-evidence/jas1703-saishikomi-evidence.json` | `5b7d44ac614256d86d7d547021146dbe66875045eaaca836637f0e9ea0be9357` | `9dbbf59b5fb4f5fbb557ce6edf3835056d649490f6287bf0ac25b2614ff766d4` | Sections 3.2, 3.12 and Table 4 in section 4.4 |

## Public fact and measurement claims

| Public owner | Fact or measurement ID | Supported public statement | Source IDs | Bilingual review | Mandatory boundary |
|---|---|---|---|---|---|
| `ingredient-shoyu-koji` | `fact-shoyu-koji-distinction` | Shoyu koji is kept distinct from kome koji so a rice substrate is not assigned to the soybean-and-wheat shoyu context. | `maff-fermented-foods` | HE/EN paired, agent-reviewed | No strain, recipe, ratio or SKU identity |
| `equipment-kioke` | `fact-kioke-documented-use` | Yamaroku is one documented example of preserving and using kioke in shoyu production. | `yamaroku-about` | HE/EN paired, agent-reviewed | Example only; no universal superiority or supplier relationship |
| `guide-koji-hydrolysis` | `fact-koji-guide-process` | MAFF describes koji as a source of enzymes that break down starches and proteins; substrate and process identity remain product-specific. | `maff-fermented-foods`, `maff-hon-mirin` | HE/EN paired, agent-reviewed | No universal protocol, rate, yield or outcome |
| `reaction-koji-enzymatic-hydrolysis` | `fact-koji-hydrolysis-process` | Starch and protein breakdown are central concepts in koji-based fermented foods. | `maff-fermented-foods` | HE/EN paired, agent-reviewed | Qualitative mechanism only |
| `reaction-koji-enzymatic-hydrolysis` | `measurement-industrial-koji-neutral-protease-range` | Neutral protease activity reported as 500 to 700 U/g across three industrial strains in the 46 h study context. | `zhang-industrial-koji-proteases-2023` | HE/EN paired, agent-reviewed | Literature context with exact extraction and assay definition; not a universal or product range |
| `reaction-koji-enzymatic-hydrolysis` | `measurement-industrial-koji-acidic-protease-range` | Acidic protease activity reported as 50 to 150 U/g in the same bounded context. | `zhang-industrial-koji-proteases-2023` | HE/EN paired, agent-reviewed | Same study, extraction and assay boundary |
| `reaction-koji-enzymatic-hydrolysis` | `measurement-industrial-koji-leucine-aminopeptidase-range` | Leucine aminopeptidase activity reported as 50 to 250 U/g in the same bounded context. | `zhang-industrial-koji-proteases-2023` | HE/EN paired, agent-reviewed | Same study, extraction and assay boundary |
| `standard-jas-shoyu-1703` | `fact-jas-shoyu-standard-identity` | JAS 1703 provides a shoyu classification framework. | `jas-shoyu-1703` | HE/EN paired, agent-reviewed | Specific product conformity requires product evidence |
| `standard-jas-shoyu-1703` | `fact-jas-saishikomi-category-thresholds` | Tentative English JAS thresholds for saishikomi: total nitrogen 1.65, 1.50 and 1.40 g/100 mL for special, superior and normal; soluble solids excluding salt 21 and 18 g/100 mL for special and superior. | `jas-shoyu-1703` | HE/EN paired, agent-reviewed | Category text only, not a scientific measurement or product grade; Japanese original controls |

The enzyme-activity units retain the paper's assay definitions. Neutral and
acidic protease units are tied to the reported tyrosine-color assay at 40°C;
leucine aminopeptidase units are tied to p-nitroaniline release at 40°C. The
registry must render the method, 46 h context, three-strain cohort and
literature-only boundary together with each range.

## Public relation claims

| Relation ID | From to target | Source IDs | Public boundary |
|---|---|---|---|
| `edge-ingredient-shoyu-koji-used_in-1` | shoyu koji to kioke shoyu | `maff-fermented-foods` | Process context, not product composition or conformity |
| `edge-ingredient-shoyu-koji-complements-2` | shoyu koji to hydrolysis section | `maff-fermented-foods` | Conceptual continuation only |
| `edge-equipment-kioke-used_in-1` | kioke to kioke shoyu | `yamaroku-about` | Documented producer example, not a universal requirement |
| `edge-guide-koji-hydrolysis-contains-1` | guide to reaction section | `maff-fermented-foods` | One canonical owner; no standalone reaction page |
| `edge-guide-koji-hydrolysis-references-2` | guide to kome koji | `maff-fermented-foods` | Ingredient-context continuation |
| `edge-guide-koji-hydrolysis-references-3` | guide to shoyu koji | `maff-fermented-foods` | Ingredient-context continuation |
| `edge-standard-jas-shoyu-1703-supported_by-1` | JAS context to kioke shoyu | `jas-shoyu-1703` | Classification context only, explicitly not product approval |

## Commerce and operational separation

The three existing Woo candidates for kioke shoyu, dried rice koji and koji
starter remain visible catalog identities under their existing owner-authorized
opening prices, but supplier label, origin and checkout gates remain false.
Cross-domain bindings remain unresolved and pending review. Nothing in this
ledger authorizes a supplier relationship, import, stock, price validity,
certification, checkout or payment method.
