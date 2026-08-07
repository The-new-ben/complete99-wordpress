from __future__ import annotations

import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
FRONTEND = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "includes"
    / "class-complete99-culinary-museum-frontend.php"
)
SCIENCE_PROJECTION = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "includes"
    / "class-complete99-culinary-science.php"
)
SCIENCE_DATA = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "data"
    / "culinary-science-pilot.php"
)
CSS = (
    ROOT
    / "plugin"
    / "complete99-platform"
    / "assets"
    / "css"
    / "culinary-museum.css"
)
ASSETS = ROOT / "plugin" / "complete99-platform"


def _text(path: Path) -> str:
    return path.read_text(encoding="utf-8")


def _method(source: str, name: str, next_name: str) -> str:
    start = source.index(f"private static function {name}")
    end = source.index(f"private static function {next_name}", start)
    return source[start:end]


def test_root_dispatches_to_a_dedicated_consumer_landing() -> None:
    source = _text(FRONTEND)
    dispatch = re.search(
        r"if \( 'museum-culinary-science' === \$bundle\['entity'\]\['id'\] \)"
        r"\s*\{\s*self::render_museum_landing\( \$bundle \);\s*return;\s*\}",
        source,
    )

    assert dispatch is not None
    collection_call = source.index(
        "$collection = self::approved_collection_projection", dispatch.start()
    )
    assert dispatch.start() < collection_call


def test_lebanese_gateway_dispatches_to_a_dedicated_consumer_landing() -> None:
    source = _text(FRONTEND)
    dispatch = re.search(
        r"if \( 'cuisine-lebanese-regional' === \$bundle\['entity'\]\['id'\] \)"
        r"\s*\{\s*self::render_lebanese_landing\( \$bundle \);\s*return;\s*\}",
        source,
    )

    assert dispatch is not None
    assert source.index("render_museum_landing( $bundle )") < dispatch.start()
    collection_call = source.index(
        "$collection = self::approved_collection_projection", dispatch.start()
    )
    assert dispatch.start() < collection_call


def test_landing_is_food_first_and_contains_every_discovery_layer() -> None:
    source = _text(FRONTEND)
    landing = _method(source, "render_museum_landing", "render_museum_landing_sources")

    required_classes = {
        "c99-museum-home-hero",
        "c99-museum-home-cuisines",
        "c99-museum-home-shelves",
        "c99-museum-home-intents",
        "c99-museum-home-neighbors",
        "c99-museum-home-groups",
    }
    for class_name in required_classes:
        assert class_name in landing

    assert '<div class="c99-museum-home" id="c99-museum-home">' in landing
    assert "<main" not in landing
    assert "self::render_visual( $entity, true )" in landing
    assert landing.count("c99-museum-home-actions") >= 2
    assert "מה בא לי לאכול?" in landing
    assert "What do I want to eat?" in landing
    assert "להיכנס למזווה" in landing
    assert "Enter the pantry" in landing


def test_landing_copy_is_bilingual_natural_and_appetite_led() -> None:
    source = _text(FRONTEND)
    data = _text(SCIENCE_DATA)
    landing = _method(source, "render_museum_landing", "render_museum_landing_sources")

    pairs = (
        ("מטבחים שאפשר להיכנס אליהם", "Kitchens you can step into"),
        ("כל מדף פותח עולם אחר", "Every shelf opens another world"),
        ("פשוט בוחרים מה בא לכם עכשיו", "Just choose what you feel like doing"),
        ("מסוריה ממשיכים ללבנון", "From Syria, continue into Lebanon"),
        ("מתכננים ארוחה לקבוצה או לחברה?", "Planning a meal for a group or company?"),
    )
    for hebrew, english in pairs:
        assert hebrew in landing
        assert english in landing

    assert "בואו רעבים" in landing
    assert "Come hungry" in landing
    assert "$entity['seo']['h1']" in landing
    assert "העולם כולו נכנס למטבח" in data
    assert "A world of flavor, one delicious doorway" in data
    assert "courses" not in landing.lower()
    assert "קורסים" not in landing


def test_all_consumer_links_have_real_bilingual_destinations() -> None:
    source = _text(FRONTEND)
    landing = _method(source, "render_museum_landing", "render_museum_landing_sources")

    hebrew_paths = (
        "/museum/japanese-culinary-science/",
        "/museum/syrian-culinary-science/",
        "/museum/lebanese-culinary-science/",
        "/dishes/",
        "/store/",
        "/ingredients/",
        "/traditions/",
        "/knowledge/",
        "/request-proposal/",
    )
    for path in hebrew_paths:
        assert f"=> '{path}'" in landing
        assert f"=> '/en{path}'" in landing

    assert "foreach ( $paths as $key => $path )" in landing
    assert "$urls[ $key ] = self::internal_url( $path );" in landing
    assert 'href="#"' not in landing
    assert "javascript:" not in landing.lower()


def test_intent_paths_cover_eat_cook_learn_shop_and_groups() -> None:
    source = _text(FRONTEND)
    landing = _method(source, "render_museum_landing", "render_museum_landing_sources")

    labels = (
        "בא לי לאכול",
        "בא לי לבשל",
        "בא לי להבין",
        "אני מחפש מוצר",
        "אנחנו קבוצה או חברה",
        "I want to eat",
        "I want to cook",
        "I want to learn",
        "I am looking for a product",
        "We are a group or company",
    )
    for label in labels:
        assert label in landing

    assert '<nav class="c99-museum-home-intent-grid"' in landing
    assert "aria-label=" in landing


def test_root_does_not_render_the_research_dashboard_or_trust_sidebar() -> None:
    source = _text(FRONTEND)
    landing = _method(source, "render_museum_landing", "render_museum_landing_sources")

    forbidden_root_renderers = (
        "render_profiles",
        "render_facts",
        "render_sections",
        "render_connections",
        "render_offer",
        "render_market_context",
        "render_taxonomy",
        "render_safety",
        "render_trust",
        "c99-museum-fact-strip",
        "substantive_updated_at",
        "entity_type_label",
    )
    for renderer in forbidden_root_renderers:
        assert renderer not in landing

    forbidden_public_copy = (
        "איך אנחנו בודקים את המידע",
        "How we check the information",
        "מקורות וציטוטים",
        "Sources and citations",
        "שליחת תיקון מבוסס",
        "Submit a sourced correction",
    )
    for phrase in forbidden_public_copy:
        assert phrase not in landing


def test_reading_list_is_collapsed_and_comes_after_the_public_journey() -> None:
    source = _text(FRONTEND)
    landing = _method(source, "render_museum_landing", "render_museum_landing_sources")
    reading = _method(source, "render_museum_landing_sources", "render_landing_picture")

    assert landing.index("c99-museum-home-groups") < landing.index(
        "render_museum_landing_sources"
    )
    assert "<details>" in reading
    assert "<summary>" in reading
    assert " open" not in reading
    assert "לקריאה נוספת" in reading
    assert "For curious readers" in reading
    assert "לפתוח ולקרוא" in reading
    assert "Open and read" in reading


def test_every_landing_asset_has_size_responsive_avif_and_webp_files() -> None:
    source = _text(FRONTEND)
    landing = _method(source, "render_museum_landing", "render_museum_landing_sources")
    stems = set(re.findall(r"'image'\s*=>\s*'([^']+)'", landing))
    stems.update(
        re.findall(
            r"render_landing_picture\( '([^']+)'",
            landing,
        )
    )

    assert len(stems) >= 7
    for stem in stems:
        assert (ASSETS / f"{stem}.avif").is_file(), stem
        assert (ASSETS / f"{stem}.webp").is_file(), stem
        assert (ASSETS / f"{stem}-768.avif").is_file(), stem
        assert (ASSETS / f"{stem}-768.webp").is_file(), stem


def test_landing_picture_wires_small_and_large_sources_with_true_dimensions() -> None:
    source = _text(FRONTEND)
    picture = _method(source, "render_landing_picture", "render_lebanese_landing")

    assert "$asset_stem . '-768.avif'" in picture
    assert "$asset_stem . '-768.webp'" in picture
    assert "$avif_small ); ?> 768w" in picture
    assert "$webp_small ); ?> 768w" in picture
    assert "$source_width" in picture
    assert "c99-food-house-spread-hero-2021-wp-v01" in picture
    assert "$width        = 1400;" in picture
    assert "$height       = 788;" in picture


def test_landing_picture_declares_truthful_layout_specific_sizes() -> None:
    source = _text(FRONTEND)
    landing = _method(source, "render_museum_landing", "render_museum_landing_sources")
    picture = _method(source, "render_landing_picture", "render_lebanese_landing")

    assert "'shelf'" in picture
    assert "(max-width: 1120px) 50vw" in picture
    assert "(max-width: 1320px) 25vw" in picture
    assert "'neighbor'" in picture
    assert "(max-width: 920px) 50vw" in picture
    assert "(max-width: 1320px) 27vw" in picture
    assert "render_landing_picture( $shelf['image'], $shelf['alt'], 'shelf' )" in landing
    assert landing.count("'', 'neighbor' )") == 2


def test_public_shelves_use_assets_approved_for_their_exact_surface() -> None:
    source = _text(FRONTEND)
    landing = _method(source, "render_museum_landing", "render_museum_landing_sources")

    assert "c99-science-japanese-premium-ingredients-v01" in landing
    assert "c99-science-museum-store-pantry-v01" in landing
    assert "c99-science-syrian-pantry-foundations-v01" not in landing
    assert "complete99-pantry-packaging-concept-v1" not in landing
    assert "already shown in the store" in landing


def test_priority_museum_visuals_project_their_768_sources() -> None:
    projection = _text(SCIENCE_PROJECTION)

    assert "'small_url'" in projection
    assert "'small_avif_url'" in projection
    assert "$asset_stem . '-768.webp'" in projection
    assert "$asset_stem . '-768.avif'" in projection


def test_landing_styles_are_responsive_accessible_and_motion_safe() -> None:
    css = _text(CSS)

    assert len(css) > 25_000
    assert ".c99-culinary-museum a:focus-visible" in css
    assert "outline: 3px solid var(--c99-museum-rust);" in css
    assert ".c99-museum-home-actions .c99-button" in css
    assert "min-height: 48px;" in css
    assert ".c99-museum-home-card-cta" in css
    assert "min-height: 44px;" in css
    assert "@media (max-width: 920px)" in css
    assert "@media (max-width: 680px)" in css
    assert "@media (prefers-reduced-motion: reduce)" in css
    assert ".c99-museum-home-picture img" in css
    assert "object-fit: cover;" in css


def test_lebanese_gateway_leads_with_appetite_and_place() -> None:
    source = _text(FRONTEND)
    data = _text(SCIENCE_DATA)
    lebanon = _method(source, "render_lebanese_landing", "render_collection_page")

    pairs = (
        ("לבנון, מהים אל ההר", "Lebanon, from sea to mountain"),
        ("לחם זעתר חם", "Warm zaatar bread"),
        ("הצלחת משתנה עם הדרך", "The plate changes with the road"),
        ("שמונה דלתות מהשולחן", "Eight doors from the table"),
        ("רוצים ארוחה לקבוצה או לחברה?", "Want a meal for a group or company?"),
    )
    for hebrew, english in pairs:
        assert hebrew in lebanon
        assert english in lebanon

    assert "self::render_visual( $entity, true )" in lebanon
    assert "$entity['seo']['h1']" in lebanon
    assert "המטבח הלבנוני, מהמאפייה ועד המונה" in data
    assert "Lebanese cuisine, from the bakery to the mouneh pantry" in data
    assert 'class="c99-lebanon-place-grid"' in lebanon
    assert 'class="c99-lebanon-door-grid"' in lebanon
    assert "<main" not in lebanon


def test_lebanese_gateway_wires_only_real_public_bilingual_doors() -> None:
    source = _text(FRONTEND)
    lebanon = _method(source, "render_lebanese_landing", "render_collection_page")
    hebrew_paths = (
        "/dishes/",
        "/ingredients/",
        "/store/",
        "/traditions/",
        "/knowledge/",
        "/request-proposal/",
        "/museum/",
        "/museum/syrian-culinary-science/",
    )

    for path in hebrew_paths:
        assert f"=> '{path}'" in lebanon
        assert f"=> '/en{path}'" in lebanon

    assert "foreach ( $paths as $key => $path )" in lebanon
    assert "$urls[ $key ] = self::internal_url( $path );" in lebanon
    assert lebanon.count("'url'   => $urls[") == 8
    assert 'href="#"' not in lebanon


def test_lebanese_gateway_hides_private_and_editorial_machinery() -> None:
    source = _text(FRONTEND)
    lebanon = _method(source, "render_lebanese_landing", "render_collection_page")
    forbidden = (
        "render_profiles",
        "render_facts",
        "render_sections",
        "render_connections",
        "render_offer",
        "render_market_context",
        "render_taxonomy",
        "render_safety",
        "render_trust",
        "c99-museum-fact-strip",
        "substantive_updated_at",
        "entity_type_label",
        "private_entities",
        "121",
        "placeholder",
        "איך אנחנו בודקים",
        "How we check",
        "מקורות וציטוטים",
        "Sources and citations",
    )
    for value in forbidden:
        assert value not in lebanon

    assert lebanon.index("c99-lebanon-invitation") < lebanon.index(
        "render_museum_landing_sources"
    )


def test_lebanese_gateway_styles_cover_focus_mobile_and_reduced_motion() -> None:
    source = _text(FRONTEND)
    css = _text(CSS)

    assert ".c99-lebanon-hero a:focus-visible" in css
    assert ".c99-lebanon-invitation a:focus-visible" in css
    assert "box-shadow: 0 0 0 3px var(--c99-museum-citrus);" in css
    assert ".c99-lebanon-door-grid a" in css
    assert "min-height: 250px;" in css
    assert ".c99-lebanon-place-grid" in css
    assert "@media (max-width: 680px)" in css
    assert ".c99-lebanon-button-secondary" in css
    assert "@media (prefers-reduced-motion: reduce)" in css
    assert "srcset=\"<?php echo esc_url( $avif_small ); ?> 768w" in source
    assert "sizes=\"<?php echo esc_attr( $sizes ); ?>\"" in source


def test_changed_surface_contains_no_em_dash() -> None:
    assert "\u2014" not in _text(FRONTEND)
    assert "\u2014" not in _text(CSS)
    assert "\u2014" not in _text(Path(__file__))
