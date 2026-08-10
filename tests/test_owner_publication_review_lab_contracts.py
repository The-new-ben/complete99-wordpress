import json
import subprocess
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
REVIEW = PLUGIN / "includes" / "class-complete99-review-lab.php"
SCIENCE = PLUGIN / "data" / "culinary-science-pilot.php"
APPROVALS = PLUGIN / "data" / "culinary-science-publication-approvals.php"


def run_php(code: str) -> str:
    completed = subprocess.run(
        ["php", "-r", code],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=60,
    )
    return completed.stdout


def php_path(path: Path) -> str:
    return path.as_posix().replace("'", "\\'")


def test_review_lab_exposes_only_a_bounded_read_only_owner_queue() -> None:
    review_path = php_path(REVIEW)
    plugin_path = php_path(PLUGIN) + "/"
    payload = run_php(
        f"""
        define('ABSPATH', __DIR__);
        define('COMPLETE99_PLATFORM_DIR', '{plugin_path}');
        function sanitize_file_name($value) {{
            return preg_replace('/[^A-Za-z0-9._-]/', '', (string) $value);
        }}
        function sanitize_key($value) {{
            return strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $value));
        }}
        class Complete99_Platform {{
            public static function evaluation_catalog_status() {{
                return array('ready' => false, 'receipt' => array(), 'materialized' => array());
            }}
        }}
        class Complete99_Culinary_Science {{
            public static function editorial_snapshot() {{
                $registry = require COMPLETE99_PLATFORM_DIR . 'data/culinary-science-pilot.php';
                return array('digest' => '', 'registry' => $registry);
            }}
        }}
        require '{review_path}';
        echo json_encode(
            Complete99_Review_Lab::snapshot()['owner_publication_approvals'],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
        );
        """
    )
    queue = json.loads(payload)

    assert queue["schema"] == "complete99-review-lab-owner-publication-queue/v2"
    assert queue["registry_valid"] is True
    assert queue["status_valid"] is True
    assert queue["candidate_count"] == 12
    assert queue["held_count"] == 12
    assert queue["approved_count"] == 0
    assert queue["owner_pending_count"] == 12
    assert queue["delivery_pending_count"] == 0
    assert queue["trusted_owner_key_count"] == 0
    assert queue["receipt_count"] == 0
    assert len(queue["candidates"]) == 12
    assert all(candidate["source_evidence_file_count"] == 1 for candidate in queue["candidates"])
    assert all(candidate["delivery_file_count"] == 4 for candidate in queue["candidates"])
    assert all(candidate["approved"] is False for candidate in queue["candidates"])
    assert all(
        candidate["state"] == "held_pending_owner_approval"
        for candidate in queue["candidates"]
    )
    assert all(
        candidate["reason"] == "missing_owner_receipt"
        for candidate in queue["candidates"]
    )
    assert all(
        candidate["delivery_validation"] == "not_evaluated"
        for candidate in queue["candidates"]
    )
    serialized = json.dumps(queue, sort_keys=True)
    assert "owner_display_name" not in serialized
    assert "public_key_base64" not in serialized
    assert "signature_base64" not in serialized


def test_review_lab_recomputes_authority_and_rejects_cache_poison_and_pre_gate_subset() -> None:
    review_path = php_path(REVIEW)
    science_path = php_path(SCIENCE)
    approvals_path = php_path(APPROVALS)
    plugin_path = php_path(PLUGIN) + "/"
    payload = run_php(
        f"""
        define('ABSPATH', __DIR__);
        define('COMPLETE99_PLATFORM_DIR', '{plugin_path}');
        function sanitize_file_name($value) {{
            return preg_replace('/[^A-Za-z0-9._-]/', '', (string) $value);
        }}
        function sanitize_key($value) {{
            return strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $value));
        }}
        class Complete99_Platform {{
            public static function evaluation_catalog_status() {{
                return array('ready' => false, 'receipt' => array(), 'materialized' => array());
            }}
        }}
        $GLOBALS['review_science_registry'] = require '{science_path}';
        class Complete99_Culinary_Science {{
            public static function editorial_snapshot() {{
                return array('digest' => '', 'registry' => $GLOBALS['review_science_registry']);
            }}
        }}
        $registry = require '{approvals_path}';
        $entity_id = 'ingredient-shoyu-koji';
        $poison = complete99_owner_publication_cached_status();
        $poison['decisions'][$entity_id]['approved'] = true;
        $poison['decisions'][$entity_id]['state'] = 'owner_approved_publication';
        $poison['decisions'][$entity_id]['reason'] = 'exact_owner_receipt_and_delivery_verified';
        $poison['decisions'][$entity_id]['receipt_id'] = 'owner-publication-receipt-review-lab-poison';
        $poison['decisions'][$entity_id]['receipt_sha256'] = 'sha256:' . str_repeat('1', 64);
        $poison['decisions'][$entity_id]['approved_at'] = '2026-08-08T09:00:00+03:00';
        $poison['decisions'][$entity_id]['delivery_validation'] = 'exact';
        $poison['approved_entity_ids'] = array($entity_id);
        $poison['approved_count'] = 1;
        $poison['held_count'] = 11;
        $poison['status_sha256'] = complete99_owner_publication_status_digest($poison);
        $poison_valid = complete99_owner_publication_status_is_valid($poison, $registry['required_entity_ids']);
        complete99_owner_publication_cached_status($poison);

        require '{review_path}';
        $full_queue = Complete99_Review_Lab::snapshot()['owner_publication_approvals'];
        $find_candidate = static function($queue) use ($entity_id) {{
            foreach ($queue['candidates'] as $candidate) {{
                if ($entity_id === $candidate['entity_id']) {{
                    return $candidate;
                }}
            }}
            return array();
        }};
        $full_candidate = $find_candidate($full_queue);

        $pre_gate = complete99_owner_publication_cached_pre_gate_entities();
        complete99_owner_publication_cached_pre_gate_entities(array($entity_id => $pre_gate[$entity_id]));
        complete99_owner_publication_cached_status($poison);
        $subset_queue = Complete99_Review_Lab::snapshot()['owner_publication_approvals'];
        $subset_candidate = $find_candidate($subset_queue);

        echo json_encode(array(
            'poison_valid' => $poison_valid,
            'full' => array(
                'status_valid' => $full_queue['status_valid'],
                'approved_count' => $full_queue['approved_count'],
                'held_count' => $full_queue['held_count'],
                'candidate' => $full_candidate,
            ),
            'subset' => array(
                'status_valid' => $subset_queue['status_valid'],
                'approved_count' => $subset_queue['approved_count'],
                'held_count' => $subset_queue['held_count'],
                'candidate' => $subset_candidate,
            ),
        ), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        """
    )
    result = json.loads(payload)

    assert result["poison_valid"] is True
    assert result["full"]["status_valid"] is True
    assert result["full"]["approved_count"] == 0
    assert result["full"]["held_count"] == 12
    assert result["full"]["candidate"]["approved"] is False
    assert result["full"]["candidate"]["state"] == "held_pending_owner_approval"
    assert result["full"]["candidate"]["reason"] == "missing_owner_receipt"

    assert result["subset"]["status_valid"] is False
    assert result["subset"]["approved_count"] == 0
    assert result["subset"]["held_count"] == 12
    assert result["subset"]["candidate"]["approved"] is False
    assert result["subset"]["candidate"]["state"] == "held_pending_owner_approval"
    assert result["subset"]["candidate"]["reason"] == "status_unavailable"


def test_review_lab_is_observational_and_reports_real_decision_counts() -> None:
    source = REVIEW.read_text(encoding="utf-8")

    assert "Owner publication queue" in source
    assert "This screen cannot enroll a signing key" in source
    assert "complete99_owner_publication_cached_status()" not in source
    assert "complete99_owner_publication_cached_pre_gate_entities()" in source
    assert "complete99_owner_publication_registry_status(" in source
    assert "complete99_owner_publication_status_is_valid" in source
    assert "cross_domain_bindings['decision_overlay']" in source
    assert "['decision_count']" in source
    assert "['recognized_reviewer_authority_count']" in source
    assert "Binding subjects" in source
    assert "Binding decisions" in source
    assert "Trusted owner signing keys" in source

    for mutation in (
        "sodium_crypto_sign_detached",
        "update_option(",
        "update_post_meta(",
        "wp_insert_post(",
        "wp_update_post(",
        "file_put_contents(",
    ):
        assert mutation not in source


def test_private_loader_fails_closed_on_throwing_data() -> None:
    source = REVIEW.read_text(encoding="utf-8")
    loader = source.split("private static function load_data_file", 1)[1].split(
        "private static function commerce_readiness", 1
    )[0]

    assert "try {" in loader
    assert "catch ( Throwable $error )" in loader
    assert "return array();" in loader
