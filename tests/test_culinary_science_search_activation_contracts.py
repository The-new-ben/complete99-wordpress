import json
import subprocess
from pathlib import Path

import pytest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
SCIENCE_CLASS = PLUGIN / "includes" / "class-complete99-culinary-science.php"
SCIENCE_DATA = PLUGIN / "data" / "culinary-science-pilot.php"
SEARCH_POLICY = PLUGIN / "data" / "culinary-science-search-activation.php"
SITEMAP_CLASS = (
    PLUGIN / "includes" / "class-complete99-culinary-museum-sitemap-provider.php"
)

REGISTRY_SHA256 = "677273756cc55f6f2e941c9aa411c522de28dc3da0c6a26bc1f8b6bc2661cc54"
POLICY_SHA256 = "0b191bef1612e56f2e97c1e4e5d15ab4f651d8e658e2eb742aea72cc2a2ac6e7"
INDEXABLE_OWNER_IDS = {
    "cuisine-japanese-washoku",
    "cuisine-lebanese-regional",
    "cuisine-syrian-regional",
    "equipment-wasabi-grater",
    "guide-umami-synergy",
    "guide-wasabi-aitc",
    "hub-japanese-foundations-lab",
    "ingredient-fresh-dutch-wasabi",
    "ingredient-fresh-wasabi",
    "ingredient-hon-mirin",
    "ingredient-katsuobushi",
    "ingredient-kioke-shoyu",
    "ingredient-koji-starter-culture",
    "ingredient-kombu",
    "ingredient-kome-koji",
    "ingredient-koshihikari-rice",
    "ingredient-kito-yuzu",
    "museum-culinary-science",
}


def _php_path(path: Path, *, directory: bool = False) -> str:
    value = path.as_posix().replace("'", "\\'")
    if directory and not value.endswith("/"):
        value += "/"
    return value


def _wp_stub() -> str:
    return r"""
class WP_Error {
    private $code;
    private $message;
    private $data;
    public function __construct($code, $message, $data = array()) {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }
    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
    public function get_error_data() { return $this->data; }
}
function is_wp_error($value) { return $value instanceof WP_Error; }
function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
function home_url($path = '') { return 'https://complete99.test' . $path; }
function wp_parse_url($url, $component = -1) {
    return -1 === $component ? parse_url($url) : parse_url($url, $component);
}
function wp_sitemaps_get_max_urls($object_type) { return 100; }
function add_action($hook, $callback) {}
abstract class WP_Sitemaps_Provider {
    protected $name = '';
    protected $object_type = '';
    abstract public function get_url_list($page_num, $object_subtype = '');
    abstract public function get_max_num_pages($object_subtype = '');
}
"""


def _run_php(script: str) -> dict:
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=60,
    )
    return json.loads(completed.stdout)


@pytest.fixture(scope="module")
def activation_payload() -> dict:
    script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '{_php_path(PLUGIN, directory=True)}');
define('COMPLETE99_PLATFORM_URL', 'https://complete99.test/wp-content/plugins/complete99-platform/');
{_wp_stub()}
require '{_php_path(SCIENCE_CLASS)}';
require '{_php_path(SITEMAP_CLASS)}';
$registry = Complete99_Culinary_Science::registry(true);
$policy = require '{_php_path(SEARCH_POLICY)}';
$validation = Complete99_Culinary_Science::validate_search_activation_policy($policy, $registry, true);
$logical_validation = Complete99_Culinary_Science::validate_search_activation_policy($policy, $registry, false);

$appended_section = $policy;
$appended_section['activation']['owner_ids'][] = 'technique-dashi-extraction';
$appended_section['activation']['owner_count'] = 19;
$appended_section['activation']['route_count'] = 38;

$dashi_swap = $policy;
$dashi_swap['activation']['owner_ids'][17] = 'preparation-ichiban-dashi';
sort($dashi_swap['activation']['owner_ids'], SORT_STRING);

$wrong_registry_digest = $policy;
$wrong_registry_digest['registry_contract']['payload_sha256'] = str_repeat('0', 64);

$wrong_authority = $policy;
$wrong_authority['authorization']['basis'] = 'editorial_assumption';

$invariant_error = '';
try {{
    Complete99_Culinary_Science::assert_invariants();
}} catch (Throwable $error) {{
    $invariant_error = $error->getMessage();
}}

$case = static function($candidate, $pinned = false) use ($registry) {{
    $result = Complete99_Culinary_Science::validate_search_activation_policy($candidate, $registry, $pinned);
    return array(
        'valid' => true === $result,
        'code' => is_wp_error($result) ? $result->get_error_code() : '',
        'path' => is_wp_error($result) ? $result->get_error_data()['path'] : '',
    );
}};
$sitemap_provider = new Complete99_Culinary_Museum_Sitemap_Provider();

echo json_encode(array(
    'registry' => $registry,
    'status' => Complete99_Culinary_Science::status(),
    'policy' => $policy,
    'policy_digest' => hash('sha256', wp_json_encode((function($value) {{
        $canonical = function($item) use (&$canonical) {{
            if (!is_array($item)) {{ return $item; }}
            if (array_is_list($item)) {{ return array_map($canonical, $item); }}
            ksort($item, SORT_STRING);
            foreach ($item as $key => $nested) {{ $item[$key] = $canonical($nested); }}
            return $item;
        }};
        return $canonical($value);
    }})($policy), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
    'validation' => true === $validation,
    'logical_validation' => true === $logical_validation,
    'invariant_error' => $invariant_error,
    'tamper' => array(
        'appended_section' => $case($appended_section),
        'dashi_swap' => $case($dashi_swap),
        'wrong_registry_digest' => $case($wrong_registry_digest),
        'wrong_authority' => $case($wrong_authority),
        'pinned_payload' => $case($wrong_authority, true),
    ),
    'indexable' => Complete99_Culinary_Science::public_indexable_page_projections(),
    'sitemap_pages' => $sitemap_provider->get_max_num_pages(),
    'sitemap_urls' => $sitemap_provider->get_url_list(1),
    'dashi' => Complete99_Culinary_Science::public_page_bundle_for_path('/knowledge/ichiban-dashi/'),
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
"""
    return _run_php(script)


def test_canonical_v20_registry_is_immutable_and_policy_is_digest_pinned(
    activation_payload: dict,
) -> None:
    registry = activation_payload["registry"]
    status = activation_payload["status"]
    assert registry["version"] == "culinary-science-2026.08.08.v20"
    assert status["digest"] == REGISTRY_SHA256
    assert status["search_activation"]["registry_digest"] == REGISTRY_SHA256
    assert activation_payload["policy_digest"] == POLICY_SHA256
    assert status["search_activation"]["policy_digest"] == POLICY_SHA256
    assert activation_payload["validation"] is True
    assert activation_payload["logical_validation"] is True
    assert activation_payload["invariant_error"] == ""

    public_entities = [
        entity
        for entity in registry["entities"]
        if entity["publication"]["state"] == "approved_public"
    ]
    assert len(public_entities) == 27
    assert all(entity["publication"]["search_index"] is False for entity in public_entities)
    assert all(
        entity["index_policy"] == "noindex_until_longform_review"
        for entity in public_entities
    )


def test_effective_activation_is_exactly_18_owners_and_36_bilingual_routes(
    activation_payload: dict,
) -> None:
    policy = activation_payload["policy"]
    search_status = activation_payload["status"]["search_activation"]
    assert policy["activation"]["owner_ids"] == sorted(INDEXABLE_OWNER_IDS)
    assert policy["activation"]["owner_count"] == 18
    assert policy["activation"]["route_count"] == 36
    assert search_status["ready"] is True
    assert search_status["effective_index_state"] == "active"
    assert search_status["owner_count"] == 18
    assert search_status["route_count"] == 36

    records = activation_payload["indexable"]
    assert len(records) == 36
    assert {record["entity"]["id"] for record in records} == INDEXABLE_OWNER_IDS
    assert {record["language"] for record in records} == {"he", "en"}
    assert all(record["entity"]["search_index"] is True for record in records)
    assert all(record["entity"]["index_policy"] == "index" for record in records)
    assert len({record["canonical_url"] for record in records}) == 36

    sitemap_urls = activation_payload["sitemap_urls"]
    assert activation_payload["sitemap_pages"] == 1
    assert len(sitemap_urls) == 36
    assert {entry["loc"] for entry in sitemap_urls} == {
        record["canonical_url"] for record in records
    }
    assert all("?" not in entry["loc"] and "#" not in entry["loc"] for entry in sitemap_urls)
    assert all("lastmod" in entry for entry in sitemap_urls)
    assert all("ichiban-dashi" not in entry["loc"] for entry in sitemap_urls)


def test_dashi_sections_and_tampered_policy_stay_fail_closed(
    activation_payload: dict,
) -> None:
    dashi = activation_payload["dashi"]
    assert dashi["entity"]["id"] == "preparation-ichiban-dashi"
    assert dashi["indexable"] is False
    assert dashi["entity"]["search_index"] is False
    assert dashi["entity"]["index_policy"] == "noindex_until_longform_review"
    assert activation_payload["policy"]["exclusions"] == {
        "owner_ids": ["preparation-ichiban-dashi"],
        "owner_reason": "culinary_test_not_verified",
        "section_state": "owner_canonical_only",
        "query_state": "noindex_follow",
        "nonpublic_state": "excluded",
    }

    for result in activation_payload["tamper"].values():
        assert result["valid"] is False
        assert result["code"] == "complete99_science_search_activation_invalid"
        assert result["path"].startswith("search_activation.")


@pytest.mark.parametrize("mode", ("missing", "tampered"))
def test_missing_or_tampered_runtime_policy_yields_zero_indexable_routes(
    tmp_path: Path, mode: str
) -> None:
    data_dir = tmp_path / "data"
    data_dir.mkdir()
    registry_path = _php_path(SCIENCE_DATA)
    (data_dir / "culinary-science-pilot.php").write_text(
        f"<?php\nreturn require '{registry_path}';\n", encoding="utf-8"
    )
    if mode == "tampered":
        policy_text = SEARCH_POLICY.read_text(encoding="utf-8")
        policy_text = policy_text.replace(
            "'owner_count' => 18", "'owner_count' => 17", 1
        )
        (data_dir / "culinary-science-search-activation.php").write_text(
            policy_text, encoding="utf-8"
        )

    script = f"""
define('ABSPATH', __DIR__);
define('COMPLETE99_PLATFORM_DIR', '{_php_path(tmp_path, directory=True)}');
define('COMPLETE99_PLATFORM_URL', 'https://complete99.test/wp-content/plugins/complete99-platform/');
{_wp_stub()}
require '{_php_path(SCIENCE_CLASS)}';
$invariant_error = '';
try {{
    Complete99_Culinary_Science::assert_invariants();
}} catch (Throwable $error) {{
    $invariant_error = $error->getMessage();
}}
echo json_encode(array(
    'invariant_error' => $invariant_error,
    'status' => Complete99_Culinary_Science::status(),
    'indexable' => Complete99_Culinary_Science::public_indexable_page_projections(),
    'museum' => Complete99_Culinary_Science::public_page_bundle_for_path('/museum/'),
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
"""
    result = _run_php(script)
    assert result["invariant_error"] == "culinary-science-search-activation-invariants"
    assert result["status"]["ready"] is True
    assert result["status"]["digest"] == REGISTRY_SHA256
    assert result["status"]["search_activation"]["ready"] is False
    assert result["status"]["search_activation"]["effective_index_state"] == "fail_closed"
    assert result["status"]["search_activation"]["owner_count"] == 0
    assert result["status"]["search_activation"]["route_count"] == 0
    assert result["indexable"] == []
    assert result["museum"]["indexable"] is False
    assert result["museum"]["entity"]["search_index"] is False
    assert result["museum"]["entity"]["index_policy"] == "noindex_until_longform_review"
