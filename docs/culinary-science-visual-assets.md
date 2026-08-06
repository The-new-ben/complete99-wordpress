# Complete99 culinary science visual assets

## Scope and provenance

This register covers thirteen visual assets for the culinary
science pilot. OpenAI's built-in image generation tool created the source PNG
files for Complete99 on 2026-08-06. The original generated PNG bytes are
preserved in the plugin. Each project PNG has the same SHA256 digest as its
corresponding generated source.

ImageMagick 7.1.2 Q16-HDRI produced the delivery derivatives. WebP files use
quality 88 with encoding method 6. AVIF files use quality 70. Every artifact is
1536 by 1024 pixels, 8-bit sRGB. No resize or crop was applied.

Public-use status for all thirteen assets is `project-generated`. All thirteen
entities passed the current public projection gate and are approved for use as
editorial visuals on their matching Complete99 culinary science pages. The
ichiban dashi source is reused from the earlier pilot without regeneration and
is newly public in release 1.5.0. None of these images is
documentary evidence of a supplier, package, SKU, stock level, certification,
laboratory measurement, or health outcome.

For public science pages, `rights_receipt_digest` binds the exact delivered
WebP file. The retained PNG hash proves source integrity, while the AVIF hash
proves the optional alternate delivery derivative.

## Asset map

All generated sources are under:

`C:\Users\pro\.codex\generated_images\019faa9f-cb38-7c22-9bd5-9fcdf3d37b3b\`

All project artifacts are under:

`plugin/complete99-platform/assets/images/science/`

| Entity visual | Generated source | Project basename | Brief prompt purpose | Public-use status |
|---|---|---|---|---|
| Culinary science museum | `exec-6c6d5ae6-5c27-4a56-8f65-780861240b58.png` | `c99-science-culinary-science-museum-v01` | Premium editorial gateway to the culinary science museum and international pantry | Project-generated, approved for matching editorial page |
| Japanese washoku | `exec-aea08f55-0c1d-44ba-9b64-fbe612e2f710.png` | `c99-science-japanese-washoku-v01` | Refined visual introduction to Japanese washoku heritage and culinary practice | Project-generated, approved for matching editorial page |
| Japanese premium ingredients | `exec-f8d5636c-754e-41d9-a8f0-9b71f8174f9b.png` | `c99-science-japanese-premium-ingredients-v01` | Curated Japanese pantry scene connecting premium ingredients as one knowledge hub | Project-generated, approved for matching editorial page |
| Japanese culinary techniques | `exec-798ec497-a7c4-4c38-8bed-bf33ccef0bd1.png` | `c99-science-japanese-culinary-techniques-v01` | Technique-hub still life linking rice handling, dashi extraction, cutting, time, temperature and tools | Project-generated, approved for matching editorial page |
| Japanese food science | `exec-529258eb-e9e9-4bd6-9ba1-7fbb00109b54.png` | `c99-science-japanese-food-science-v01` | Science-hub still life linking ingredients, fermentation substrates, measurement and flavor extraction | Project-generated, approved for matching editorial page |
| Kombu | `exec-679bceca-ddfa-4fc7-9b8d-a0b6b4820f4c.png` | `c99-science-kombu-v01` | Culinary studio study of kombu texture, preparation context, and umami role | Project-generated, approved for matching editorial page |
| Kioke shoyu | `exec-5e185809-3ecc-4721-b90c-4eaa6ee08d9f.png` | `c99-science-kioke-shoyu-v01` | Culinary studio study of traditionally barrel-aged shoyu, color, and serving context | Project-generated, approved for matching editorial page |
| Fresh wasabi rhizome | `exec-f58c65cf-c744-42e0-83af-33c1a5ff86e7.png` | `c99-science-fresh-wasabi-rhizome-v01` | Culinary studio study of a fresh wasabi rhizome and careful grating context | Project-generated, approved for matching editorial page |
| Kito yuzu | `exec-346464a6-b9f0-4ca4-96a0-561ba193fcda.png` | `c99-science-kito-yuzu-v01` | Culinary studio study of fresh Kito yuzu, peel, juice, and aromatic context | Project-generated, approved for matching editorial page |
| Hon mirin | `exec-ea527d0c-0190-459e-af8e-29ef6d01ec7b.png` | `c99-science-hon-mirin-v01` | Editorial ingredient study of amber hon mirin, glutinous rice and rice koji | Project-generated, approved for matching editorial page |
| Katsuobushi | `exec-562a63db-fdd5-4757-be3b-6020bd2e9d41.png` | `c99-science-katsuobushi-v01` | Culinary studio study of a whole smoked bonito block, shaved curls, and the shaving tool | Project-generated, approved for matching editorial page |
| Ichiban dashi | `exec-d25a8ee3-da63-470e-9a25-c3c8c26f1fbd.png` | `c99-science-ichiban-dashi-v01` | Culinary studio study of clear first-extraction dashi with kombu and katsuobushi context | Existing project-generated source, approved for matching editorial page in 1.5.0 |
| Glutamate and IMP umami synergy | `exec-7bf4b49a-e6bb-49d7-a714-79eee12e5c4e.png` | `c99-science-umami-synergy-glutamate-imp-v01` | Editorial visualization linking representative molecular models with kombu, katsuobushi and dashi | Project-generated, approved for matching editorial page |

## Release 1.5.0 alt and prompt register

The following bilingual alt text describes visible content without presenting
an editorial visualization as a laboratory measurement or supplier record.

| Project basename | Hebrew alt | English alt |
|---|---|---|
| `c99-science-japanese-culinary-techniques-v01` | כלי עבודה וטכניקות בישול יפניות: אורז בהאנגירי, דאשי, קומבו, קצואובושי, קוג׳י, סכין, מדחום וטיימר | Japanese culinary tools and techniques with rice in a hangiri, dashi, kombu, katsuobushi, koji, a knife, thermometer and timer |
| `c99-science-japanese-food-science-v01` | מדע המזון היפני עם דאשי, קומבו, קצואובושי, שויו, אורז, קוג׳י, סויה, חיטה, יוזו וכלי מדידה | Japanese food science still life with dashi, kombu, katsuobushi, shoyu, rice, koji, soybeans, wheat, yuzu and measuring tools |
| `c99-science-hon-mirin-v01` | הון מירין ענברי בקנקן זכוכית ובקערית קרמיקה, לצד אורז דביק וקוג׳י אורז | Amber hon mirin in a glass carafe and ceramic dish beside glutinous rice and rice koji |
| `c99-science-umami-synergy-glutamate-imp-v01` | הדמיה מערכתית של מודלים מולקולריים לגלוטמט ו-IMP מעל דאשי, קומבו וקצואובושי | Editorial visualization of molecular models representing glutamate and IMP above dashi, kombu and katsuobushi |

The current prompt of record for each new science asset is:

Japanese culinary techniques:

```text
Museum-grade commercial editorial composition representing Japanese culinary techniques, clear visual hierarchy, authentic culinary materials, restrained natural light, no logos, no embedded text, no invented certification marks.
```

Japanese food science:

```text
Museum-grade commercial editorial composition representing Japanese food science, clear visual hierarchy, authentic culinary materials, restrained natural light, no logos, no embedded text, no invented certification marks.
```

Hon mirin:

```text
Amber hon mirin in a clear unbranded glass vessel beside glutinous rice and rice koji, soft backlight revealing viscosity and warm color, precise Japanese culinary studio styling, no labels, no bottle branding.
```

Glutamate and IMP umami synergy:

```text
Editorial culinary science visualization of glutamate and IMP molecular models beside kombu, katsuobushi and clear dashi, accurate atom conventions, museum-grade dark background, no health claims, text or labels.
```

All four use the shared negative prompt of record:

```text
No text, no logos, no certification seals, no false brand packaging, no watermarks, no distorted utensils or hands.
```

The reused ichiban dashi visual keeps its existing prompt and source bytes. Its
current public alt is `איצ׳יבאן דאשי צלול בכלי זכוכית לצד קומבו וקצואובושי` in
Hebrew and `Clear ichiban dashi in a glass vessel beside kombu and
katsuobushi` in English.

## Artifact integrity register

| File | Format | Dimensions | Bytes | SHA256 |
|---|---:|---:|---:|---|
| `c99-science-culinary-science-museum-v01.png` | PNG | 1536x1024 | 2,649,256 | `d0c6613c3ce5d073c405f7ebae04687bc62437139304e937e690b2ae9edff4cd` |
| `c99-science-culinary-science-museum-v01.webp` | WebP | 1536x1024 | 272,832 | `ee2441315d9c03074bbe88bba7408e66e06323a4906d1c5310574028d970f18b` |
| `c99-science-culinary-science-museum-v01.avif` | AVIF | 1536x1024 | 190,803 | `b48db7c848a09835bcd8a32664907aa998dba89afbef3c1f0049e6a1cdc94a0e` |
| `c99-science-japanese-washoku-v01.png` | PNG | 1536x1024 | 2,405,607 | `6963f2434e683c118d43298dea7c09e5c1537e506398551bd8e58143a12e1a59` |
| `c99-science-japanese-washoku-v01.webp` | WebP | 1536x1024 | 193,618 | `98558d16ea7975b78ba7b925ea2a4b3a7dc0f6158a42e94855f30f73f7fa644c` |
| `c99-science-japanese-washoku-v01.avif` | AVIF | 1536x1024 | 139,558 | `6a3cb696bd167a5cbf59d6170e75d26c31004ed7afe7a468221de84f9c7cf549` |
| `c99-science-japanese-premium-ingredients-v01.png` | PNG | 1536x1024 | 3,082,885 | `cc5eb59c3809ee04cd603b49d0bfc5303752c2d037897f582cb0696b782c70c0` |
| `c99-science-japanese-premium-ingredients-v01.webp` | WebP | 1536x1024 | 358,274 | `76cc7ecfebd4eac9ecb9ed6a670cee097941a99637fbc2446b00eb7692848e10` |
| `c99-science-japanese-premium-ingredients-v01.avif` | AVIF | 1536x1024 | 254,989 | `c521222293ac74e24199087f2c2dcd19b100b41aa841f88f6c9fc9b3f0de7b4f` |
| `c99-science-kombu-v01.png` | PNG | 1536x1024 | 2,654,687 | `30259469c6a3a6c8ec191c74c3dea72898c5df059082b4993eeebfd8a8deb54f` |
| `c99-science-kombu-v01.webp` | WebP | 1536x1024 | 247,562 | `046d2ba7f392efa8076afc3acae177604e27cbe77ef3d8c626fc2974abe8ac4e` |
| `c99-science-kombu-v01.avif` | AVIF | 1536x1024 | 177,909 | `5ff41d68946dc3c7ec6a710f063920c3e1455b177a27df96d0165c28e141d7cf` |
| `c99-science-kioke-shoyu-v01.png` | PNG | 1536x1024 | 2,453,478 | `54d49d3d9aad635769c65c3290d765d40a9236ae041895be0d7b623c68f11614` |
| `c99-science-kioke-shoyu-v01.webp` | WebP | 1536x1024 | 240,956 | `7bbb750f81dac4c2ec8326174f48d2aedba782be68a780c0b63acfcf1ad8b950` |
| `c99-science-kioke-shoyu-v01.avif` | AVIF | 1536x1024 | 163,302 | `cebe2c470b74bf7e9bd3c4d6679366aea0aceec10f2a4c2a0ca78a0e4b6464d9` |
| `c99-science-fresh-wasabi-rhizome-v01.png` | PNG | 1536x1024 | 2,629,334 | `1e26911116dc7f595dc1c2c9028e669c589db258cae3eb41a2b86e04bad874f8` |
| `c99-science-fresh-wasabi-rhizome-v01.webp` | WebP | 1536x1024 | 245,154 | `740471ec3f8970016f31af46ef6206c9984f07b25b09e00ed5f59a4bfe15d1b1` |
| `c99-science-fresh-wasabi-rhizome-v01.avif` | AVIF | 1536x1024 | 175,669 | `9ecfdfb4c7f0b5d27c2b2f416de1b80cc3714aa654279fdfda16614e090f642a` |
| `c99-science-kito-yuzu-v01.png` | PNG | 1536x1024 | 2,272,868 | `7f437eb79c9687c480e421068e57144c3bb66b133edbf73f9cc08378a35b4979` |
| `c99-science-kito-yuzu-v01.webp` | WebP | 1536x1024 | 182,042 | `e058ebfece1033d37f2835678a961f4bfbf7fbe988b960036d23f12bf83b2464` |
| `c99-science-kito-yuzu-v01.avif` | AVIF | 1536x1024 | 138,168 | `d77c0cc9ea72261743c10e94e676494ae8b53a1196dca3a81da968ef1173fbcd` |
| `c99-science-katsuobushi-v01.png` | PNG | 1536x1024 | 2,406,661 | `d2f37b6e4f1890a1fb1489ccbd8f6d9dd8fc2a7f92afeb7ce4041f9b5709edd4` |
| `c99-science-katsuobushi-v01.webp` | WebP | 1536x1024 | 171,562 | `a48c8adf8f92b0c425301ff5cfff502301af0babb059cf446aa100c1fdd91b8e` |
| `c99-science-katsuobushi-v01.avif` | AVIF | 1536x1024 | 88,266 | `2000e38a171c2e718c856269d887b4fd921ef049e83d4f0212b6435e5092b2e9` |
| `c99-science-ichiban-dashi-v01.png` | PNG | 1536x1024 | 2,400,377 | `5d5c24588e071243b3a44ad4bb56617d5b266bb88d8eaa0611455159167a47cd` |
| `c99-science-ichiban-dashi-v01.webp` | WebP | 1536x1024 | 148,964 | `28eb6c05cec30ba9f4fb986c12afc31b8dd9c3cf2c90a3ec2a25400482a847e2` |
| `c99-science-ichiban-dashi-v01.avif` | AVIF | 1536x1024 | 78,823 | `f21766a4ce0aa0f5e8f81c54e7a98ff0fb8f841d09d4329bc440302b896cb604` |
| `c99-science-japanese-culinary-techniques-v01.png` | PNG | 1536x1024 | 2,768,564 | `79bafc95b1366747c35427cba8be7d1273200199df8c602d67ca8bf9a813300c` |
| `c99-science-japanese-culinary-techniques-v01.webp` | WebP | 1536x1024 | 280,378 | `2eda7710abfa5ce35e1634fecdf69a57efdf3875638889c217bba804d44027b4` |
| `c99-science-japanese-culinary-techniques-v01.avif` | AVIF | 1536x1024 | 201,844 | `9707538d51c7088f32a1ef824e8c46d36a86da0e2be2d26bfbff9efb43c84894` |
| `c99-science-japanese-food-science-v01.png` | PNG | 1536x1024 | 2,469,999 | `b0ae4081aa28d1af78c8bc9ea03779755e873954993c2dd95bd005100bb68625` |
| `c99-science-japanese-food-science-v01.webp` | WebP | 1536x1024 | 214,184 | `41affd1d16f01e9aeb418d05139d0df6aad5bee4c02df88473ea2c33c516c49b` |
| `c99-science-japanese-food-science-v01.avif` | AVIF | 1536x1024 | 162,428 | `37aad62b0ae0dab54aa82e23f9c07c68fa43f014c4cdfb54032dc37f0f54c507` |
| `c99-science-hon-mirin-v01.png` | PNG | 1536x1024 | 2,032,609 | `74c72b476a16d6950783d433ce1152ec3d44ecf619e994e37ce94ac5355b0480` |
| `c99-science-hon-mirin-v01.webp` | WebP | 1536x1024 | 107,164 | `c8808bebd8f92d7ebfd4b78d3ab3853ebff56fbe00e8d98c3433db75d4de97d0` |
| `c99-science-hon-mirin-v01.avif` | AVIF | 1536x1024 | 86,254 | `3915d6ecefcdb70e77c9849067cd4133d9f5646224be8b5cc1354b013cebc289` |
| `c99-science-umami-synergy-glutamate-imp-v01.png` | PNG | 1536x1024 | 2,419,166 | `37f0feebd4be1e67e549f04b8de62d6f62a0f8bbd337d22f295b606dbd4aed40` |
| `c99-science-umami-synergy-glutamate-imp-v01.webp` | WebP | 1536x1024 | 175,826 | `cff653805e2e90b3ee4d565cdfdd21c8ac4e13782441860bd81a98516d1c7cd5` |
| `c99-science-umami-synergy-glutamate-imp-v01.avif` | AVIF | 1536x1024 | 143,835 | `2823d4f7ad334c0be2dd8494e4544115d6d13ab91d560514ed17f78d977e0d14` |

## Validation record

- All 39 artifacts decode successfully with ImageMagick.
- Format signatures resolve as thirteen PNG, thirteen WebP, and thirteen AVIF files.
- Every decoded artifact reports 1536 by 1024 pixels, 8-bit sRGB.
- Each retained project PNG is byte-identical to its generated source according
  to SHA256.
- No existing file was overwritten during import or conversion.
