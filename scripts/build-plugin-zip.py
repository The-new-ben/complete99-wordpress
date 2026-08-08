#!/usr/bin/env python3
"""Build the Complete99 WordPress plugin as byte-for-byte reproducible ZIP."""

from __future__ import annotations

import argparse
import hashlib
import json
import re
import tempfile
import zipfile
from pathlib import Path, PurePath, PurePosixPath

ROOT = Path(__file__).resolve().parents[1]
SLUG = "complete99-platform"
SOURCE = ROOT / "plugin" / SLUG
MAIN = SOURCE / f"{SLUG}.php"
DEFAULT_DIST = ROOT / "plugin-dist"
UPDATE_MANIFEST_NAME = f"{SLUG}.json"
INTEGRITY_METADATA_NAME = f"{SLUG}-integrity.json"
RAW_REPOSITORY_ROOT = "https://raw.githubusercontent.com/The-new-ben/complete99-wordpress/main"
RELEASE_LAST_UPDATED = "2026-08-08 07:30:00"
FIXED_TIME = (1980, 1, 1, 0, 0, 0)
EXCLUDED_NAMES = {".DS_Store", "Thumbs.db"}
EXCLUDED_PARTS = {".git", ".github", "tests", "node_modules", "__pycache__"}
GENERATED_SOURCE_ROOT = PurePath("assets/images/generated")
SCIENCE_SOURCE_ROOT = PurePath("assets/images/science")
SOURCE_ONLY_PNG_ROOTS = {GENERATED_SOURCE_ROOT, SCIENCE_SOURCE_ROOT}
FORBIDDEN_SECRET_SUFFIXES = {".pem", ".key", ".p12", ".pfx"}
FORBIDDEN_SECRET_EXACT_NAMES = {"id_rsa", "id_ed25519"}
FORBIDDEN_JSON_NAME = re.compile(
    r"(?:credential|service[-_. ]?account|client[-_. ]?secret|secret[-_. ]?key)",
    re.IGNORECASE,
)
CREDENTIAL_SIGNATURES: tuple[tuple[str, re.Pattern[bytes]], ...] = (
    (
        "private-key material",
        re.compile(
            rb"-----BEGIN (?:RSA |EC |DSA |OPENSSH |ENCRYPTED )?PRIVATE KEY-----",
            re.IGNORECASE,
        ),
    ),
    ("GitHub access token", re.compile(rb"\bgh(?:p|o|u|s|r)_[A-Za-z0-9]{20,}\b")),
    ("GitHub fine-grained token", re.compile(rb"\bgithub_pat_[A-Za-z0-9_]{20,}\b")),
    (
        "OpenAI API key",
        re.compile(rb"\bsk-(?:proj-|svcacct-)?[A-Za-z0-9_-]{20,}\b"),
    ),
    ("AWS access key ID", re.compile(rb"\b(?:AKIA|ASIA)[A-Z0-9]{16}\b")),
    ("Google API key", re.compile(rb"\bAIza[0-9A-Za-z_-]{35}\b")),
    (
        "Slack access token",
        re.compile(rb"\bxox(?:a|b|p|r|s)-[0-9A-Za-z-]{20,}\b"),
    ),
    ("Stripe live secret key", re.compile(rb"\bsk_live_[0-9A-Za-z]{16,}\b")),
    ("npm access token", re.compile(rb"\bnpm_[0-9A-Za-z]{20,}\b")),
)


def forbidden_secret_path_reason(path: PurePath) -> str | None:
    """Return a policy label for paths that must never enter a public package."""
    for component in path.parts:
        name = component.casefold().rstrip(" .")
        if name == ".env" or name.startswith(".env."):
            return "environment file"
        if Path(name).suffix in FORBIDDEN_SECRET_SUFFIXES:
            return "private key/certificate container"
        if name in FORBIDDEN_SECRET_EXACT_NAMES:
            return "private key filename"
        if re.search(r"\.json(?:$|[._-])", name) and FORBIDDEN_JSON_NAME.search(name):
            return "credential JSON filename"
    return None


def credential_signature_label(contents: bytes) -> str | None:
    """Identify a credential signature without returning or logging its value."""
    for label, pattern in CREDENTIAL_SIGNATURES:
        if pattern.search(contents):
            return label
    return None


def canonical_contents(path: Path) -> bytes:
    """Normalize text line endings so Windows and Linux produce identical ZIP bytes."""
    raw = path.read_bytes()
    if b"\0" in raw:
        return raw
    try:
        raw.decode("utf-8")
    except UnicodeDecodeError:
        return raw
    return raw.replace(b"\r\n", b"\n").replace(b"\r", b"\n")


def version_contract() -> tuple[str, str]:
    text = MAIN.read_text(encoding="utf-8")
    header = re.search(r"^\s*\*\s*Version:\s*([0-9]+\.[0-9]+\.[0-9]+)\s*$", text, re.MULTILINE)
    constant = re.search(r"define\(\s*'COMPLETE99_PLATFORM_VERSION'\s*,\s*'([^']+)'\s*\)", text)
    deployment = re.search(r"define\(\s*'COMPLETE99_PLATFORM_DEPLOYMENT_ID'\s*,\s*'([^']+)'\s*\)", text)
    if not header or not constant or not deployment:
        raise SystemExit("Missing plugin version/deployment contract")
    if header.group(1) != constant.group(1):
        raise SystemExit(f"Version mismatch: header={header.group(1)} constant={constant.group(1)}")
    expected_deployment = f"c99-wp-{header.group(1)}"
    if deployment.group(1) != expected_deployment:
        raise SystemExit(
            f"Deployment ID mismatch: expected={expected_deployment} actual={deployment.group(1)}"
        )
    return header.group(1), deployment.group(1)


def source_files() -> list[Path]:
    files: list[Path] = []
    for path in SOURCE.rglob("*"):
        if not path.is_file():
            continue
        relative = path.relative_to(SOURCE)
        if any(part in EXCLUDED_PARTS for part in relative.parts):
            continue
        if relative.parent in SOURCE_ONLY_PNG_ROOTS and relative.suffix.casefold() == ".png":
            continue
        forbidden_reason = forbidden_secret_path_reason(relative)
        if forbidden_reason:
            raise SystemExit(
                f"Refusing to package forbidden secret-like path: "
                f"{relative.as_posix()} ({forbidden_reason})"
            )
        if path.name in EXCLUDED_NAMES:
            continue
        signature = credential_signature_label(path.read_bytes())
        if signature:
            raise SystemExit(
                f"Refusing to package credential signature in "
                f"{relative.as_posix()} ({signature})"
            )
        files.append(path)
    return sorted(files, key=lambda path: path.relative_to(SOURCE).as_posix())


def build_bytes(target: Path) -> None:
    target.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(target, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for path in source_files():
            relative = path.relative_to(SOURCE).as_posix()
            info = zipfile.ZipInfo(f"{SLUG}/{relative}", FIXED_TIME)
            info.create_system = 3
            info.external_attr = (0o100644 & 0xFFFF) << 16
            info.compress_type = zipfile.ZIP_DEFLATED
            info.flag_bits |= 0x800
            archive.writestr(
                info,
                canonical_contents(path),
                compress_type=zipfile.ZIP_DEFLATED,
                compresslevel=9,
            )


def source_digest() -> str:
    digest = hashlib.sha256()
    for path in source_files():
        relative = path.relative_to(SOURCE).as_posix().encode("utf-8")
        raw = canonical_contents(path)
        digest.update(len(relative).to_bytes(8, "big"))
        digest.update(relative)
        digest.update(len(raw).to_bytes(8, "big"))
        digest.update(raw)
    return digest.hexdigest()


def installed_digest(artifact: Path) -> str:
    """Hash the ZIP's installed tree exactly as the temporary bridge does."""
    entries: list[bytes] = []
    with zipfile.ZipFile(artifact) as archive:
        for info in archive.infolist():
            if info.is_dir():
                continue
            relative = (
                PurePosixPath(info.filename)
                .relative_to(SLUG)
                .as_posix()
                .encode("utf-8")
            )
            file_digest = (
                hashlib.sha256(archive.read(info)).hexdigest().encode("ascii")
            )
            entries.append(relative + b"\0" + file_digest)
    return hashlib.sha256(b"\n".join(sorted(entries))).hexdigest()


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--dist", type=Path, default=DEFAULT_DIST)
    parser.add_argument("--verify-reproducible", action="store_true")
    args = parser.parse_args()

    version, deployment_id = version_contract()
    dist = args.dist.resolve()
    artifact = dist / f"{SLUG}-{version}.zip"

    if args.verify_reproducible:
        with tempfile.TemporaryDirectory(prefix="complete99-repro-") as temp:
            first = Path(temp) / "first.zip"
            second = Path(temp) / "second.zip"
            build_bytes(first)
            build_bytes(second)
            first_digest = hashlib.sha256(first.read_bytes()).hexdigest()
            second_digest = hashlib.sha256(second.read_bytes()).hexdigest()
            if first_digest != second_digest or first.read_bytes() != second.read_bytes():
                raise SystemExit("Reproducibility check failed")

    build_bytes(artifact)
    raw = artifact.read_bytes()
    digest = hashlib.sha256(raw).hexdigest()
    integrity = {
        "artifact": artifact.name,
        "deployment_id": deployment_id,
        "installed_sha256": installed_digest(artifact),
        "sha256": digest,
        "size": len(raw),
        "slug": SLUG,
        "source_sha256": source_digest(),
        "type": "plugin",
        "version": version,
    }
    update_manifest = {
        "name": "Complete99 Platform",
        "slug": SLUG,
        "version": version,
        "author": "Complete99",
        "homepage": "https://complete99.co.il/",
        "requires": "6.4",
        "tested": "7.0",
        "requires_php": "8.0",
        "download_url": f"{RAW_REPOSITORY_ROOT}/plugin-dist/{artifact.name}",
        "last_updated": RELEASE_LAST_UPDATED,
        "sections": {
            "changelog": (
                f"<h4>{version}</h4>"
                "<ul>"
                "<li>Compacted only the mobile pantry masthead and shelf introduction so the first product card can appear inside the initial 390 by 844 viewport in both languages.</li>"
                "<li>Kept the complete consumer copy in the server-rendered document while removing repeated narrow-screen labels from the visual flow and scaling the two store headings for a concise mobile shelf.</li>"
                "<li>Preserved every 44 by 44 control target, the desktop layout, all 36 products, prices, stock, links, cart behavior and disabled payment state.</li>"
                "</ul>"
                "<h4>1.18.1</h4>"
                "<ul>"
                "<li>Rebuilt the bilingual pantry shelf as a compact server-rendered catalog with twelve products per page, real pagination and safe product-type links that work without JavaScript.</li>"
                "<li>Kept stock, price, add-to-cart and the primary culinary continuation visible while moving detailed ingredients, allergens, storage, equipment care and secondary connections into accessible expandable panels.</li>"
                "<li>Added page-aware product links across dishes, ingredients, culinary science and the museum, including bounded compatibility handling for older product bookmarks.</li>"
                "<li>Aligned localized titles, canonical links, hreflang, robots and Product structured data with the exact rendered page and filter state.</li>"
                "<li>Preserved the exact 36 WooCommerce products, prices, stock and disabled payment state, with no catalog rematerialization, supplier assertion or checkout activation.</li>"
                "</ul>"
                "<h4>1.18.0</h4>"
                "<ul>"
                "<li>Expanded the reviewed bilingual public science graph from 24 to 27 entities while preserving 19 canonical page owners per language and 38 bilingual routes.</li>"
                "<li>Kept the registry at 672 entities with 370 sources and Entity Studio at 728 subjects; the 84-identity Japanese cluster now contains 24 public and 60 private records.</li>"
                "<li>Added dashi extraction to the ichiban-dashi guide and L-glutamate plus inosine monophosphate to the umami guide, with source-bound consumer copy and natural links across Japanese ingredients, food science and techniques.</li>"
                "<li>Added three original responsive culinary-science visual sets with bilingual alternative text, human approval receipts and a clear boundary that the molecule sculptures are conceptual rather than exact structural diagrams.</li>"
                "<li>Kept every culinary-science page noindex until its separate long-form and kitchen-test gates pass, and added no new product, supplier, price, stock, bundle or checkout activation.</li>"
                "<li>Added a site-wide favicon declaration and marked public add-to-cart links nofollow so search crawlers do not trigger cart mutations.</li>"
                "<li>Versioned the unchanged commerce registry as v12 so its dependency on culinary-science v18 remains immutable and auditable.</li>"
                "<li>Preserved the exact 36-product WooCommerce store, prices, stock, editable cart, disabled payment state and private operating boundary.</li>"
                "</ul>"
                "<h4>1.17.0</h4>"
                "<ul>"
                "<li>Expanded the bilingual culinary-science registry to 672 entities and Entity Studio to 728 subjects: 672 science identities plus 56 unchanged product identities.</li>"
                "<li>Added 121 source-bound private Lebanese regional, scientific, community and institutional identities, bringing the Lebanese graph from 82 to 203 entities while keeping every new identity private, noindex and reference-only.</li>"
                "<li>Separated Beirut, Mount Lebanon, Chouf, Aley, Tripoli, Akkar, the northern coast, Bekaa, Zahle, Baalbek, Hermel, South Lebanon and Jabal Amel evidence, while retaining Jewish, Druze, Christian, Muslim, Armenian and Palestinian records within their documented scope.</li>"
                "<li>Added molecules, reactions, techniques, equipment, markets, institutions, restaurant benchmarks, original visual specifications and twelve fail-closed held identities for unresolved evidence or handling risk.</li>"
                "<li>Published one reviewed noindex Lebanese cuisine gateway and redesigned the museum entrance around appetizing imagery, cuisines, dishes, pantry shelves, guides and clear consumer journeys.</li>"
                "<li>Preserved the exact 36-product public WooCommerce store, prices, stock, cart, disabled payment state and no-role boundary. The public science graph now contains 24 entities across 19 page owners per language, while all 121 new Lebanese identities remain private, noindex and reference-only.</li>"
                "</ul>"
                "<h4>1.16.0</h4>"
                "<ul>"
                "<li>Expanded the bilingual culinary-science registry to 551 entities and Entity Studio to 607 subjects: 551 science identities plus 56 product identities.</li>"
                "<li>Added 86 source-bound private Syrian regional, community and institutional identities, bringing the Syrian graph from 196 to 282 entities without adding a public offer or public route.</li>"
                "<li>Separated Aleppo, Damascus, Homs, Hama, Idlib, Qadmus, Kassab, Baniyas, Jableh, Qamishli, Deir ez-Zor, Al-Bukamal, Palmyra, Suwayda and Hauran evidence, while retaining Jewish, Armenian, Assyrian, Kurdish, Druze and family records within their documented scope.</li>"
                "<li>Added source-bound institutions, archives, markets and restaurant benchmarks, original visual specifications, scientific controls and four fail-closed held identities for unresolved plant, preservation or product-identity risk.</li>"
                "<li>Preserved the exact 36-product public WooCommerce store, 23 public science entities, 18 public page owners, prices, stock, cart, disabled payment state and no-role boundary unchanged. All 86 new Syrian identities remain private and noindex.</li>"
                "</ul>"
                "<h4>1.15.0</h4>"
                "<ul>"
                "<li>Expanded the bilingual culinary-science registry to 465 entities and Entity Studio to 521 subjects: 465 science identities plus 56 product identities.</li>"
                "<li>Added a 96-identity private Iraqi regional and community foundation with 32 dishes, 16 regional or topic hubs, 12 ingredients, eight techniques, ten traditions, four preparations, institutions, markets, restaurants, guides and one central trade-compliance rule.</li>"
                "<li>Kept Baghdad, Mosul and Ninewa, Basra and the Shatt al-Arab, the Middle Euphrates, the southern marshes, Iraqi Kurdistan, Kirkuk and Diyala within their sourced regional boundaries, while preserving Jewish, Kurdish, Marsh Arab and family records within their documented community scope.</li>"
                "<li>Kept kubba, dolma, biryani, qeema, tannour, turshi, basturma, kebab, kofta and sayadiyah as shared regional families, linked sabich and amba to existing identities, and gated fish, rice, offal, fermented foods, dairy, date syrup, open fire and wild-plant handling with explicit safety controls.</li>"
                "<li>Preserved the exact 36-product public WooCommerce store, 23 public science entities, 18 public page owners, prices, stock, cart, disabled payment state and no-role boundary unchanged. All 96 Iraqi identities remain private, noindex and reference-only.</li>"
                "</ul>"
                "<h4>1.14.0</h4>"
                "<ul>"
                "<li>Expanded the bilingual culinary-science registry to 369 entities and Entity Studio to 425 subjects: 369 science identities plus 56 product identities.</li>"
                "<li>Added an 82-entity private Lebanese regional foundation with 27 dishes, 13 regional or topic hubs, eight ingredients, five techniques, nine traditions, institutions, markets, restaurants, six dated retail observations and one central trade-compliance rule.</li>"
                "<li>Kept Beirut, Mount Lebanon and Chouf, North and Tripoli, Bekaa and Baalbek-Hermel, South Lebanon, Jewish family, Druze, Armenian-Lebanese and Palestinian-in-Lebanon evidence within their sourced regional or community boundaries.</li>"
                "<li>Kept shared Levantine dish families separate from Syrian identities, blocked exclusive-origin claims, distinguished pomegranate concentrate from molasses and retained every external price as a dated non-comparable reference.</li>"
                "<li>Preserved the exact 36-product public WooCommerce store, 23 public science entities, 18 public page owners, prices, stock, cart, disabled payment state and no-role boundary unchanged. All 82 Lebanese identities remain private, noindex and reference-only.</li>"
                "</ul>"
                "<h4>1.13.0</h4>"
                "<ul>"
                "<li>Expanded the bilingual culinary-science registry to 287 entities and Entity Studio to 343 subjects: 287 science identities plus 56 product identities.</li>"
                "<li>Added 86 private, source-bound Syrian regional-depth identities plus one separate pomegranate-concentrate identity, bringing the Syrian graph to 196 entities across 56 dishes, 55 ingredients, 21 regional or topic hubs, 17 techniques, 17 traditions, 15 preparations, markets, restaurants and hospitality institutions.</li>"
                "<li>Kept Aleppan, Damascene, Homsi, Hamawi, coastal, Jazira, Euphrates, Palmyrene, Idlib, Afrin, Suwayda and Hauran evidence distinct, and scoped Jewish, Assyrian, Kurdish, Druze and family records to their actual sources.</li>"
                "<li>Bound the observed pomegranate concentrate to its exact retail-listing identity, kept pomegranate molasses as comparison only, filtered techniques out of commercial cross-sells and separated registry validity from active-offer readiness.</li>"
                "<li>Preserved the exact 36-product public WooCommerce store, 23 public science entities, public routes, prices, stock, cart, disabled payment state and no-role boundary unchanged. All 87 new identities remain private, noindex and reference-only.</li>"
                "</ul>"
                "<h4>1.12.1</h4>"
                "<ul>"
                "<li>Replaced the last construction-style phrase in the English pantry with direct consumer cooking language for kome koji.</li>"
                "<li>Preserved all 36 WooCommerce products, prices, stock, images, filters, cart behavior, Syrian research entities and disabled payment state unchanged.</li>"
                "</ul>"
                "<h4>1.12.0</h4>"
                "<ul>"
                "<li>Expanded the source-bound bilingual culinary-science registry to 200 entities and Entity Studio to 256 subjects: 200 science identities plus 56 product identities, with 36 live WooCommerce prices and 20 private planning prices.</li>"
                "<li>Added a 109-entity Syrian regional foundation: 106 culinary entities, including 46 ingredient entities and six preparation entities, plus three private held market observations, with one safe noindex consumer gateway and 108 private entities.</li>"
                "<li>Expanded the noindex public science preview to 23 entities across 18 canonical page owners per language and corrected Japanese public language to answer consumer food, cooking, buying and learning intent.</li>"
                "<li>Preserved the exact 36-product public store and added no WooCommerce offers, stock, supplier claims, payment activation or role assignments. The three Syrian market observations remain private and held.</li>"
                "</ul>"
                "<h4>1.11.0</h4>"
                "<ul>"
                "<li>Added 12 source-bound Japanese premium pantry and professional-tool candidates, eight knowledge subjects, five draft bundles and nine draft merchandising relationships.</li>"
                "<li>Expanded Entity Studio to 144 subjects and 53 product identities with 36 live WooCommerce prices, 17 private planning prices and complete 53 of 53 price-basis coverage.</li>"
                "<li>Made public source-market projection fail closed so only the exact explicit value public is eligible, while all new candidates remain held with planning stock zero and no WooCommerce code or active offer.</li>"
                "<li>Preserved the exact 36-product public store, 22 public science entities, all public routes, live stock, POS behavior and disabled payment state unchanged.</li>"
                "</ul>"
                "<h4>1.10.0</h4>"
                "<ul>"
                "<li>Added a private administrator-only Entity Studio inside WordPress for modular culinary, scientific and commercial dossiers.</li>"
                "<li>Added five evidence-bound private draft planning offers, bringing commercial price identity coverage to 41 of 41 products while activating no new offers.</li>"
                "<li>Preserved the exact 36-product public WooCommerce catalog, all public routes and the public interface unchanged.</li>"
                "<li>Kept WooCommerce as the price and stock authority for live offers, kept payment disabled and kept planned prices separate from supplier cost, landed cost and achieved margin.</li>"
                "</ul>"
                "<h4>1.9.0</h4>"
                "<ul>"
                "<li>Expanded the public WooCommerce catalog from 32 to 36 owner-authorized products with Uozu Koshihikari rice, dried rice koji, Chouhaku-kin koji starter and Dutch-grown fresh wasabi.</li>"
                "<li>Added current dated source-market observations, Bank of Israel currency conversions, explicit opening retail prices, one unit of opening stock and no backorders for all four products.</li>"
                "<li>Expanded the bilingual Japanese culinary-science pilot to 22 public entities across 17 canonical page owners per language, with dedicated imagery, natural internal links and reciprocal store offers.</li>"
                "<li>Added range-aware Product structured data for the 50 to 60 gram wasabi offer while preserving the maximum operational shipping weight.</li>"
                "<li>Kept actual supplier cost, landed cost and gross margin unset until documented commercial evidence exists, and kept every payment gateway disabled.</li>"
                "</ul>"
                "<h4>1.8.0</h4>"
                "<ul>"
                "<li>Bound public read-model freshness to a recursive canonical SHA-256 digest that excludes only its own top-level digest field.</li>"
                "<li>Persisted the normalized OS transport envelope unchanged with exact UTC millisecond generation and item timestamps.</li>"
                "<li>Added a narrow identity-preserving migration gate for the known 1.7 read-model envelope and a fixed PHP/OS digest fixture.</li>"
                "<li>Made health, menu, public catalog, SEO and sitemap consumers fail closed when a stored digest is missing, malformed, arbitrary or does not match the stored content.</li>"
                "<li>Kept the exact 32-item public WooCommerce catalog and approved public content unchanged.</li>"
                "</ul>"
                "<h4>1.7.0</h4>"
                "<ul>"
                "<li>Introduced the modular culinary-science v5 ownership and public-exposure contracts for safe multi-cuisine expansion.</li>"
                "<li>Added a bilingual Japanese Foundations Lab with curated canonical links, accessible filters and collection schema.</li>"
                "<li>Kept the new Lab outside search indexing while preserving strict separation from suppliers, costs, margins and private records.</li>"
                "</ul>"
                "<h4>1.6.1</h4>"
                "<ul>"
                "<li>Expanded the consumer breadcrumb and live cart-status links to the 44 by 44 CSS-pixel acceptance target.</li>"
                "<li>Added release contracts for both corrected targets after live desktop, mobile and 200 percent zoom acceptance.</li>"
                "</ul>"
                "<h4>1.6.0</h4>"
                "<ul>"
                "<li>Expanded the bilingual culinary store from 30 to 32 owner-authorized products with fresh Japanese wasabi and professional stainless-steel wasabi preparation equipment.</li>"
                "<li>Added typed food and equipment product contracts, type-specific facts, public rendering, Product schema, exact prices, opening stock and reciprocal science links.</li>"
                "<li>Published a source-led bilingual wasabi, AITC and preparation cluster with dedicated visual assets while preserving its noindex long-form review gate.</li>"
                "<li>Separated dated source-market observations, owner-authorized retail prices and private margin scenarios so public commerce remains truthful and operationally useful.</li>"
                "</ul>"
                "<h4>1.5.1</h4>"
                "<ul>"
                "<li>Localized the complete culinary-museum entity and relationship vocabularies in Hebrew and English, including preparation, guide and contextual related-item labels.</li>"
                "<li>Localized the current public Japanese taxonomy, evidence classes and knowledge-map terminology while preserving scientific abbreviations.</li>"
                "<li>Added regression coverage that prevents internal machine labels from appearing in the public bilingual museum.</li>"
                "</ul>"
                "<h4>1.5.0</h4>"
                "<ul>"
                "<li>Expanded the bilingual pantry from 28 to 30 owner-authorized products with kioke-fermented shoyu and Kito yuzu juice.</li>"
                "<li>Added dated producer evidence, owner-authorized ILS prices, opening stock, original product imagery and reciprocal culinary-science links.</li>"
                "<li>Expanded the public Japanese culinary-science pilot to 13 reviewed entities, including techniques, food science, hon mirin, ichiban dashi and umami synergy.</li>"
                "<li>Added localized image alternatives, liquid net quantities, Japanese-pantry filtering and contextual related-product continuation.</li>"
                "</ul>"
                "<h4>1.4.0</h4>"
                "<ul>"
                "<li>Expanded the bilingual pantry from 26 to 28 owner-authorized products with Rishiri kombu and Honkarebushi katsuobushi.</li>"
                "<li>Added current dated market evidence, opening ILS prices, stock, exact image receipts and fail-closed deployment coverage for both products.</li>"
                "<li>Connected the reviewed kombu and katsuobushi science pages to their exact WooCommerce offers through reciprocal safe public links.</li>"
                "</ul>"
                "<h4>1.3.14</h4>"
                "<ul>"
                "<li>Expanded museum breadcrumb and evidence-citation targets to the 44 by 44 CSS-pixel acceptance target.</li>"
                "</ul>"
                "<h4>1.3.13</h4>"
                "<ul>"
                "<li>Opened a bilingual Culinary Science Museum preview for the reviewed Japanese pilot through exact projection-only routes.</li>"
                "<li>Added canonical and hreflang metadata, visible breadcrumbs, citation-aware schema, original responsive assets and dated source-market observations.</li>"
                "<li>Kept every preview route noindex and outside the sitemap until its independent long-form editorial gate is approved.</li>"
                "</ul>"
                "<h4>1.3.12</h4>"
                "<ul>"
                "<li>Added a source-bound bilingual culinary-science registry with a Japanese pilot, molecular measurements, topic-cluster ownership and fail-closed publication gates.</li>"
                "<li>Added a modular commerce graph that separates products, variants, SKUs, market observations, supplier offers, channel offers, landed costs, margins and bundles.</li>"
                "<li>Added an administrator review surface and a signed vendor-neutral POS catalog projection while preserving WooCommerce as catalog and stock authority.</li>"
                "</ul>"
                "<h4>1.3.11</h4>"
                "<ul>"
                "<li>Made pantry filters visually hide every nonmatching product card while keeping the result count and URL state synchronized.</li>"
                "</ul>"
                "<h4>1.3.10</h4>"
                "<ul>"
                "<li>Kept signed catalog identity independent of WooCommerce customer-language and session filters.</li>"
                "<li>Kept the Hebrew and English pantry available after cart-language changes and item removal.</li>"
                "<li>Read stock identity and immediate catalog verification from the raw WooCommerce edit context.</li>"
                "</ul>"
                "<h4>1.3.9</h4>"
                "<ul>"
                "<li>Published a bilingual, food-first pantry catalog with 26 linked ingredient products and opening stock.</li>"
                "<li>Presented approved dish and product images normally without archive notices or unusual public treatment.</li>"
                "<li>Separated public catalog and cart readiness from the electronic payment launch gate.</li>"
                "<li>Added exact WooCommerce dependency verification and fail-closed recovery for dependency and catalog materialization.</li>"
                "<li>Normalized WooCommerce page option types across cache flushes so strict catalog readback stays deterministic.</li>"
                "<li>Kept the approved WordPress presentation authoritative, applied synchronized publication controls per dish, set WooCommerce visibility to live and purged host page caches after commit.</li>"
                "</ul>"
            )
        },
    }
    dist.mkdir(parents=True, exist_ok=True)
    (dist / UPDATE_MANIFEST_NAME).write_text(
        json.dumps(update_manifest, ensure_ascii=False, indent=2, sort_keys=True) + "\n",
        encoding="utf-8",
        newline="\n",
    )
    (dist / INTEGRITY_METADATA_NAME).write_text(
        json.dumps(integrity, ensure_ascii=False, indent=2, sort_keys=True) + "\n",
        encoding="utf-8",
        newline="\n",
    )
    (dist / f"{artifact.name}.sha256").write_text(f"{digest}  {artifact.name}\n", encoding="ascii", newline="\n")
    print(json.dumps(integrity, sort_keys=True))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
