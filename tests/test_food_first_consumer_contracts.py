from __future__ import annotations

import json
import re
import shutil
import subprocess
import unittest
from pathlib import Path
from urllib.parse import urlparse

ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
BOOTSTRAP = PLUGIN / "complete99-platform.php"
CONSUMER = PLUGIN / "includes" / "class-complete99-consumer.php"
COMMERCE = PLUGIN / "includes" / "class-complete99-commerce.php"
FRONTEND = PLUGIN / "includes" / "class-complete99-frontend.php"
LEADS = PLUGIN / "includes" / "class-complete99-leads.php"
CONNECTORS = PLUGIN / "includes" / "class-complete99-order-connectors.php"
CONNECTOR_DATA = PLUGIN / "data" / "order-connectors.php"
FACETS = PLUGIN / "data" / "culinary-facets.php"
MENU = PLUGIN / "data" / "consumer-menu.php"
CONTENT = PLUGIN / "data" / "consumer-content.php"
LAUNCH = PLUGIN / "data" / "launch-content.php"
PUBLIC_JS = PLUGIN / "assets" / "js" / "public.js"
PUBLIC_SHELL = PLUGIN / "templates" / "public-shell.php"
COMMERCE_SHELL = PLUGIN / "templates" / "commerce-shell.php"
LIVE_CATALOG_PRODUCTS = PLUGIN / "data" / "live-catalog-products.php"


def _read(path: Path) -> str:
    return path.read_text(encoding="utf-8")


def _php_path(path: Path, *, directory: bool = False) -> str:
    value = path.as_posix()
    if directory:
        value = value.rstrip("/") + "/"
    return value.replace("\\", "\\\\").replace("'", "\\'")


def _run_php_json(script: str) -> object:
    if not shutil.which("php"):
        raise unittest.SkipTest("PHP is required for executable PHP contract checks")
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=20,
    )
    return json.loads(completed.stdout)


def _php_method(source: str, method_name: str) -> str:
    """Return one PHP method without relying on the method that follows it."""

    signature = re.search(
        rf"\b(?:public|private|protected)\s+static\s+function\s+"
        rf"{re.escape(method_name)}\s*\([^)]*\)\s*\{{",
        source,
    )
    if not signature:
        raise AssertionError(f"PHP method not found: {method_name}")

    opening = source.find("{", signature.start())
    depth = 0
    quote: str | None = None
    escaped = False
    line_comment = False
    block_comment = False
    index = opening
    while index < len(source):
        char = source[index]
        following = source[index + 1] if index + 1 < len(source) else ""

        if line_comment:
            if char == "\n":
                line_comment = False
            index += 1
            continue
        if block_comment:
            if char == "*" and following == "/":
                block_comment = False
                index += 2
                continue
            index += 1
            continue
        if quote:
            if escaped:
                escaped = False
            elif char == "\\":
                escaped = True
            elif char == quote:
                quote = None
            index += 1
            continue
        if char in {"'", '"'}:
            quote = char
            index += 1
            continue
        if char == "/" and following == "/":
            line_comment = True
            index += 2
            continue
        if char == "#":
            line_comment = True
            index += 1
            continue
        if char == "/" and following == "*":
            block_comment = True
            index += 2
            continue
        if char == "{":
            depth += 1
        elif char == "}":
            depth -= 1
            if depth == 0:
                return source[signature.start() : index + 1]
        index += 1

    raise AssertionError(f"Unclosed PHP method: {method_name}")


def test_connector_registry_returns_only_verified_public_https_destinations() -> None:
    connector_path = _php_path(CONNECTORS)
    connector_data_path = _php_path(CONNECTOR_DATA)
    plugin_dir = _php_path(PLUGIN, directory=True)
    result = _run_php_json(
        f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '{plugin_dir}');
function sanitize_key($value) {{
    return strtolower((string) preg_replace('/[^a-z0-9_\\-]/i', '', (string) $value));
}}
function sanitize_text_field($value) {{
    return trim(strip_tags((string) $value));
}}
function esc_url_raw($value) {{
    return (string) $value;
}}
function wp_parse_url($value) {{
    return parse_url((string) $value);
}}
require '{connector_path}';
$raw = require '{connector_data_path}';
echo json_encode(
    array(
        'raw' => $raw,
        'he' => Complete99_Order_Connectors::public_connectors('he'),
        'en' => Complete99_Order_Connectors::public_connectors('en'),
    ),
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
);
"""
    )

    assert isinstance(result, dict)
    assert set(result["raw"]) == {"wolt", "tenbis", "cibus", "spareeat"}
    expected_urls = {
        "he": "https://wolt.com/he/isr/tel-aviv/restaurant/sabich-complete",
        "en": "https://wolt.com/en/isr/tel-aviv/restaurant/sabich-complete",
    }
    for language, expected_url in expected_urls.items():
        assert result[language] == [
            {"key": "wolt", "label": "Wolt", "url": expected_url}
        ]
        parsed = urlparse(result[language][0]["url"])
        assert parsed.scheme == "https"
        assert parsed.netloc == "wolt.com"
        assert parsed.username is None
        assert parsed.password is None

    for inactive in ("tenbis", "cibus", "spareeat"):
        connector = result["raw"][inactive]
        assert connector["public_enabled"] is False
        assert connector["merchant_verified"] is False
        assert connector["availability_check"] is False
        assert connector["acceptance_receipt"] == ""
        assert connector["url_he"] == ""
        assert connector["url_en"] == ""


def test_inactive_order_connectors_have_no_public_render_path() -> None:
    public_sources = "\n".join(
        _read(path)
        for path in (CONSUMER, PUBLIC_JS, PUBLIC_SHELL, COMMERCE_SHELL)
    ).casefold()
    for forbidden in ("tenbis", "cibus", "spareeat", "תן ביס"):
        assert forbidden.casefold() not in public_sources

    bootstrap = _read(BOOTSTRAP)
    commerce = _read(COMMERCE)
    order_url = _php_method(commerce, "order_url")
    assert "class-complete99-order-connectors.php" in bootstrap
    assert "Complete99_Order_Connectors::primary_url( $language )" in order_url

    connector_class = _read(CONNECTORS)
    readiness = _php_method(connector_class, "is_public_ready")
    for gate in (
        "public_enabled",
        "merchant_verified",
        "availability_check",
        "acceptance_receipt",
    ):
        assert gate in readiness
    assert "'https' === strtolower" in readiness
    assert "$parts['host']" in readiness
    assert "$parts['user']" in readiness
    assert "$parts['pass']" in readiness


def test_culinary_filter_and_badge_registry_is_fact_only() -> None:
    facets_path = _php_path(FACETS)
    menu_path = _php_path(MENU)
    result = _run_php_json(
        f"""
define('ABSPATH', __DIR__);
$facets = require '{facets_path}';
$menu = require '{menu_path}';
echo json_encode(
    array('facets' => $facets, 'menu' => $menu),
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
);
"""
    )
    facets = result["facets"]
    filters = facets["filters"]
    badges = facets["badges"]

    assert "all" in filters
    assert "all" not in badges
    assert set(filters) - {"all"} <= set(badges)
    assert {"pita", "plate", "pots", "vegetarian", "meat", "fish"} <= set(filters)
    assert {"pan", "griddled", "picante"} <= set(badges)

    medical_or_nutrition_claim = re.compile(
        r"\b(?:medical|clinical|diagnos\w*|treat\w*|cure\w*|prevent\w*|"
        r"therap\w*|health\w*|diabet\w*|ketogenic|keto|calor\w*|"
        r"protein\w*|low[\s_-]?(?:carb|fat|sodium|sugar)|"
        r"sugar[\s_-]?free|gluten[\s_-]?free|allergen[\s_-]?free|"
        r"immune\w*|weight[\s_-]?loss|detox)\b|"
        r"(?:בריא|רפוא|אבחו|טיפול|מרפא|מונע|סוכרת|קטו|קלור|חלבון|"
        r"דל[\s_-]?(?:פחמימות|שומן|נתרן|סוכר)|ללא[\s_-]?(?:גלוטן|אלרגנים)|"
        r"חיזוק[\s_-]?חיסוני|ירידה[\s_-]?במשקל)",
        re.IGNORECASE,
    )
    for registry_name, registry in (("filters", filters), ("badges", badges)):
        assert registry
        for code, labels in registry.items():
            assert re.fullmatch(r"[a-z][a-z0-9_-]*", code), (
                registry_name,
                code,
            )
            assert set(labels) == {"he", "en"}
            assert all(isinstance(label, str) and label.strip() for label in labels.values())
            public_value = " ".join((code, labels["he"], labels["en"]))
            assert not medical_or_nutrition_claim.search(public_value), public_value

    filter_codes = set(filters) - {"all"}
    badge_codes = set(badges)
    forbidden_item_fields = {
        "nutrition",
        "calories",
        "protein",
        "health_claim",
        "medical_claim",
        "allergens",
    }
    assert result["menu"]
    for dish in result["menu"]:
        assert dish["published"] is True
        assert dish.get("menu_evidence")
        assert set(dish["facets"]) <= filter_codes, dish["id"]
        assert set(dish["badge_codes"]) <= badge_codes, dish["id"]
        assert 1 <= len(dish["badge_codes"]) <= 2, dish["id"]
        assert not (forbidden_item_fields & set(dish)), dish["id"]


def test_consumer_dish_filters_and_cards_are_accessible_and_operational() -> None:
    consumer = _read(CONSUMER)
    filters = _php_method(consumer, "render_menu_filters")
    badges = _php_method(consumer, "render_dish_badges")
    grid = _php_method(consumer, "render_menu_grid")
    page = _php_method(consumer, "render_menu_page")

    for marker in (
        "data-c99-dish-filter",
        'role="group"',
        "aria-label=",
        'type="button"',
        "data-c99-filter=",
        "aria-pressed=",
        'aria-live="polite"',
        "data-c99-filter-count",
    ):
        assert marker in filters
    for marker in (
        'class="c99-dish-badges"',
        "aria-label=",
        "data-badge=",
    ):
        assert marker in badges
    for marker in (
        "data-c99-dish-grid",
        "data-c99-dish-card",
        "data-c99-facets=",
        "Complete99_Frontend::live_dish_url",
        "alt=",
        'width="1000"',
        'height="700"',
        'loading="lazy"',
    ):
        assert marker in grid
    assert "self::render_menu_filters( $lang )" in page
    assert "self::render_menu_grid( $lang, 0, true )" in page
    assert "data-c99-filter-empty hidden" in page

    script = _read(PUBLIC_JS)
    for marker in (
        "querySelector('[data-c99-dish-filter]')",
        "querySelector('[data-c99-dish-grid]')",
        "querySelectorAll('[data-c99-filter]')",
        "querySelectorAll('[data-c99-dish-card]')",
        "button.setAttribute('aria-pressed'",
        "card.hidden = !matches",
        "empty.hidden = visible !== 0",
        "count.textContent",
        "button.addEventListener('click'",
        "button.addEventListener('keydown'",
        "event.preventDefault()",
        "buttons[next].focus()",
    ):
        assert marker in script
    for key in ("ArrowRight", "ArrowDown", "ArrowLeft", "ArrowUp", "Home", "End"):
        assert key in script


def test_public_consumer_copy_has_no_internal_or_store_coming_language() -> None:
    consumer = _read(CONSUMER)
    content_path = _php_path(CONTENT)
    content_without_held_store = _run_php_json(
        f"""
define('ABSPATH', __DIR__);
$content = require '{content_path}';
unset($content['store']);
echo json_encode(
    $content,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
);
"""
    )
    public_renderer = "\n".join(
        _php_method(consumer, method)
        for method in (
            "render_header",
            "render_current",
            "render_home",
            "render_menu_preview",
            "render_menu_filters",
            "render_dish_badges",
            "render_menu_grid",
            "render_menu_page",
            "render_group_order_teaser",
            "render_group_order_page",
            "render_store_page",
            "render_pantry_teaser",
            "render_generic_page",
            "render_order_band",
            "render_footer",
        )
    )
    public_copy = "\n".join(
        (
            public_renderer,
            json.dumps(content_without_held_store, ensure_ascii=False),
            _read(MENU),
        )
    ).casefold()
    forbidden_patterns = (
        r"\bproject\b",
        r"\bprototype\b",
        r"\bnot working\b",
        r"\bcoming soon\b",
        r"\bthe pantry is being prepared\b",
        r"\bon the way to the pantry\b",
        r"\bstore catalogue is being planned\b",
        r"\bcatalogue planning\b",
        r"\bwhen commerce is ready\b",
        r"\bno site cart or checkout\b",
        r"\bnot a product for sale\b",
        r"\bdirection concept\b",
        r"פרויקט",
        r"אב[\s-]?טיפוס",
        r"לא עובד",
        r"בקרוב",
        r"המזווה (?:נבנה|בהכנה)",
        r"החנות נבנית",
        r"תכנון הקטלוג",
        r"כשהמסחר יהיה מוכן",
        r"אין סל או תשלום",
        r"לא מוצר למכירה",
        r"הדמיית כיוון",
    )
    for pattern in forbidden_patterns:
        assert not re.search(pattern, public_copy, re.IGNORECASE), pattern


def test_live_catalog_copy_uses_consumer_language() -> None:
    public_catalog_copy = _read(LIVE_CATALOG_PRODUCTS).casefold()
    for pattern in (r"\bproject(?:s)?\b", r"פרוי?יקט(?:ים)?"):
        assert not re.search(pattern, public_catalog_copy, re.IGNORECASE), pattern


def test_dish_components_and_generic_pages_use_consumer_intent_actions() -> None:
    consumer = _read(CONSUMER)
    dish_components = _php_method(consumer, "render_dish_component_tree")
    generic_page = _php_method(consumer, "render_generic_page")
    action_map = _php_method(consumer, "generic_page_actions")

    for marker in (
        "מרכיבים וטעמים",
        "Ingredients and flavours",
        "מה יש במנה",
        "What is in the dish",
    ):
        assert marker in dish_components
    for stale in (
        "עץ המנה",
        "Dish tree",
        "מה פוגשים במנה",
        "What you meet in the dish",
    ):
        assert stale not in dish_components

    assert "self::generic_page_actions( $key, $lang )" in generic_page
    assert "foreach ( $actions as $action_index => $action )" in generic_page
    assert 'id="c99-consumer-page-content"' in generic_page
    for marker in (
        "למנות שלנו",
        "See our dishes",
        "מגיעים אלינו",
        "Visit us",
        "ניווט לאבן גבירול 99",
        "Get directions",
        "לכל המרכיבים",
        "Explore ingredients",
        "למדריכי הבישול",
        "Cooking guides",
        "לסיפורי האוכל",
        "Explore food stories",
        "למנות מהסיפורים",
        "See the dishes",
        "למדריכים",
        "Explore guides",
        "לבשל עם המרכיבים",
        "Cook with the ingredients",
        "שאלה בנושא פרטיות",
        "Ask a privacy question",
        "לתנאי השימוש",
        "Read the terms",
        "למדיניות הפרטיות",
        "Read the privacy policy",
        "ליצירת קשר",
        "Contact us",
        "דיווח על קושי בנגישות",
        "Report an accessibility barrier",
        "חזרה לעמוד הבית",
        "Back to the homepage",
    ):
        assert marker in action_map
    assert "Complete99_Commerce::catalog_is_ready()" in action_map
    assert "#c99-ingredient-index-title" in action_map
    assert "Complete99_Commerce::order_url( $lang )" in action_map
    assert "google.com/maps/search/" in action_map
    for stale in ("לתפריט ההזמנות", "Open ordering menu"):
        assert stale not in action_map


def test_generic_page_action_map_returns_distinct_working_destinations() -> None:
    consumer_path = _php_path(CONSUMER)
    result = _run_php_json(
        f"""
define('ABSPATH', __DIR__);
function home_url($path = '') {{
    return 'https://complete99.example' . (string) $path;
}}
class Complete99_Content {{
    public static function route_url($key, $lang) {{
        $prefix = 'en' === $lang ? '/en' : '';
        return $prefix . '/' . str_replace('_', '-', (string) $key) . '/';
    }}
}}
class Complete99_Commerce {{
    public static function catalog_is_ready() {{ return true; }}
    public static function order_url($lang) {{
        return 'https://wolt.example/' . (string) $lang . '/complete99';
    }}
}}
require '{consumer_path}';
$method = new ReflectionMethod('Complete99_Consumer', 'generic_page_actions');
$method->setAccessible(true);
$result = array();
foreach (array('he', 'en') as $lang) {{
    foreach (array('about', 'contact', 'ingredients', 'traditions', 'knowledge', 'privacy', 'terms', 'accessibility') as $key) {{
        $result[$lang][$key] = $method->invoke(null, $key, $lang);
    }}
}}
echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
"""
    )

    expected_labels = {
        "he": {
            "about": ["למנות שלנו", "מגיעים אלינו"],
            "contact": ["ניווט לאבן גבירול 99", "הזמנה ב-Wolt"],
            "ingredients": ["לכל המרכיבים", "למדריכי הבישול"],
            "traditions": ["לסיפורי האוכל", "למנות מהסיפורים"],
            "knowledge": ["למדריכים", "לבשל עם המרכיבים"],
            "privacy": ["שאלה בנושא פרטיות", "לתנאי השימוש"],
            "terms": ["למדיניות הפרטיות", "ליצירת קשר"],
            "accessibility": ["דיווח על קושי בנגישות", "חזרה לעמוד הבית"],
        },
        "en": {
            "about": ["See our dishes", "Visit us"],
            "contact": ["Get directions", "Order on Wolt"],
            "ingredients": ["Explore ingredients", "Cooking guides"],
            "traditions": ["Explore food stories", "See the dishes"],
            "knowledge": ["Explore guides", "Cook with the ingredients"],
            "privacy": ["Ask a privacy question", "Read the terms"],
            "terms": ["Read the privacy policy", "Contact us"],
            "accessibility": ["Report an accessibility barrier", "Back to the homepage"],
        },
    }
    for language, page_actions in result.items():
        assert set(page_actions) == {
            "about",
            "contact",
            "ingredients",
            "traditions",
            "knowledge",
            "privacy",
            "terms",
            "accessibility",
        }
        for key, actions in page_actions.items():
            assert [action["label"] for action in actions] == expected_labels[language][key]
            assert len(actions) == 2
            assert actions[0]["url"] != actions[1]["url"]
            assert all(action["url"] for action in actions)
        assert page_actions["contact"][0]["external"] is True
        assert page_actions["contact"][1]["external"] is True
        assert "#c99-ingredient-index-title" in page_actions["ingredients"][0]["url"]
        assert "#c99-consumer-page-content" in page_actions["traditions"][0]["url"]
        assert "#c99-consumer-page-content" in page_actions["knowledge"][0]["url"]


def test_proposal_is_a_public_culinary_group_order_route() -> None:
    launch_path = _php_path(LAUNCH)
    proposal = _run_php_json(
        f"""
define('ABSPATH', __DIR__);
$records = require '{launch_path}';
$proposal = null;
foreach ($records as $record) {{
    if ('proposal' === $record['key']) {{
        $proposal = $record;
        break;
    }}
}}
echo json_encode(
    $proposal,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
);
"""
    )
    assert proposal is not None
    assert proposal["status"] == "publish"
    assert proposal["public_route"] is True
    assert proposal["index_eligible"] is True
    assert proposal["title"] == {
        "he": "ארוחות לקבוצות ולמקומות עבודה",
        "en": "Meals for groups and workplaces",
    }
    assert proposal["slug"] == {
        "he": "request-proposal",
        "en": "request-proposal",
    }

    consumer = _read(CONSUMER)
    header = _php_method(consumer, "render_header")
    dispatch = _php_method(consumer, "render_current")
    group_page = _php_method(consumer, "render_group_order_page")
    assert "array( 'proposal'" in header
    assert "'proposal' === $key" in dispatch
    assert "self::render_group_order_page( $post, $lang )" in dispatch
    assert 'id="c99-group-order-form"' in group_page
    assert "Complete99_Leads::render_form( $lang, 'group-order' )" in group_page
    assert "self::route( 'dishes', $lang )" in group_page


def test_group_order_form_collects_and_persists_planning_fields() -> None:
    leads = _read(LEADS)
    form = _php_method(leads, "render_form")
    handler = _php_method(leads, "handle")
    input_names = set(re.findall(r'\bname=["\']([^"\']+)["\']', form))

    assert "const GROUP_ORDER_INTEREST = 'group-order'" in leads
    assert "$is_group_order" in form
    assert "self::is_group_order_interest( $interest )" in form
    for base_field in (
        "contact_name",
        "organisation",
        "email",
        "phone",
        "message",
        "consent",
    ):
        assert base_field in input_names

    semantic_fields = {
        "headcount": {
            "headcount",
            "group_size",
            "guest_count",
            "diners",
            "people_count",
        },
        "requested date": {
            "requested_date",
            "service_date",
            "meal_date",
            "event_date",
            "delivery_date",
        },
        "meal window": {
            "meal_window",
            "service_window",
            "serving_time",
            "service_time",
            "meal_time",
            "delivery_time",
        },
        "pickup or delivery": {
            "fulfilment",
            "fulfillment",
            "delivery_method",
            "handover_method",
            "pickup_delivery",
        },
        "meal style or packaging": {
            "meal_style",
            "meal_format",
            "service_style",
            "packaging",
            "packaging_preference",
        },
        "budget": {
            "budget",
            "budget_per_person",
            "budget_range",
        },
        "group food preferences": {
            "group_preferences",
            "food_preferences",
            "dietary_summary",
            "dietary_preferences",
            "preference_summary",
        },
    }
    selected_names: dict[str, str] = {}
    for meaning, aliases in semantic_fields.items():
        present = input_names & aliases
        assert present, f"Missing explicit group-order field for {meaning}"
        selected_names[meaning] = sorted(present)[0]

    for meaning, field_name in selected_names.items():
        assert f"$_POST['{field_name}']" in handler, (
            f"{meaning} is rendered but not read by the form handler"
        )
        assert re.search(
            rf"['_\"]_c99_[a-z0-9_]*{re.escape(field_name)}[a-z0-9_]*['\"]",
            handler,
        ), f"{meaning} is read but not persisted under private lead metadata"

    assert re.search(
        r"Do not include[^.]+(?:medical diagnoses|personal health information)",
        form,
    )
    assert re.search(r"אין ל(?:כתוב|כלול)[^.]+רפוא", form)
    assert "aggregate quantities only" in form


def test_store_is_published_for_catalog_ready_and_redirected_when_catalog_unready() -> None:
    frontend = _read(FRONTEND)
    consumer = _read(CONSUMER)
    commerce = _read(COMMERCE)

    assert (
        "add_action( 'template_redirect', array( __CLASS__, "
        "'maybe_redirect_unready_store' ), 1 )"
    ) in frontend
    redirect = _php_method(frontend, "maybe_redirect_unready_store")
    for marker in (
        "Complete99_Commerce::catalog_is_ready()",
        "Complete99_Commerce::can_preview_commerce()",
        "'store' !== Complete99_Content::translation_group_for_post",
        "Complete99_Content::route_url( 'dishes', $lang )",
        "wp_safe_redirect( $destination, 302",
        "exit;",
    ):
        assert marker in redirect

    preview = _php_method(commerce, "can_preview_commerce")
    assert "self::OPTION_PREVIEW" in preview
    assert "self::can_govern_commerce()" in preview

    for method_name in ("render_header", "render_footer"):
        method = _php_method(consumer, method_name)
        readiness_gate = method.find("Complete99_Commerce::catalog_is_ready()")
        store_entry = method.find("array( 'store'")
        assert readiness_gate >= 0, method_name
        assert store_entry > readiness_gate, method_name
        assert "Complete99_Commerce::can_preview_commerce()" in method

    teaser = _php_method(consumer, "render_pantry_teaser")
    readiness_gate = teaser.find("Complete99_Commerce::catalog_is_ready()")
    store_route = teaser.find("self::route( 'store', $lang )")
    assert readiness_gate >= 0
    assert store_route > readiness_gate
    assert "Complete99_Commerce::can_preview_commerce()" in teaser


def load_tests(
    loader: unittest.TestLoader,
    tests: unittest.TestSuite,
    pattern: str | None,
) -> unittest.TestSuite:
    """Register this module's function contracts with unittest discovery."""

    del loader, tests, pattern
    suite = unittest.TestSuite()
    for name, candidate in sorted(globals().items()):
        if name.startswith("test_") and callable(candidate):
            suite.addTest(unittest.FunctionTestCase(candidate, description=name))
    return suite
