from __future__ import annotations

import hashlib
import json
import shutil
import subprocess
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
SCIENCE = PLUGIN / "data" / "culinary-science-pilot.php"
APPROVALS = PLUGIN / "data" / "culinary-science-publication-approvals.php"
MANIFEST = PLUGIN / "data" / "generated-asset-manifest.php"

HELD_IDS = (
    "region-syria-aleppo",
    "hub-aleppine-kibbeh-family",
    "ingredient-syrian-bulgur",
    "ingredient-syrian-red-meat",
    "technique-syrian-bulgur-hydration",
    "technique-syrian-kibbeh-cooking",
    "tradition-aleppan-jewish-foodways",
    "ingredient-shoyu-koji",
    "equipment-kioke",
    "guide-koji-hydrolysis",
    "reaction-koji-enzymatic-hydrolysis",
    "standard-jas-shoyu-1703",
)


def _php_path(path: Path) -> str:
    return path.as_posix().replace("'", "\\'")


def _run_php(script: str) -> dict:
    completed = subprocess.run(
        ["php", "-r", script],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
        encoding="utf-8",
        timeout=90,
    )
    return json.loads(completed.stdout)


def _sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def test_current_registry_is_fail_closed_and_preserves_exact_staged_candidates():
    payload = _run_php(
        f"""
define('ABSPATH', __DIR__);
$science = require '{_php_path(SCIENCE)}';
$approvals = require '{_php_path(APPROVALS)}';
$manifest = require '{_php_path(MANIFEST)}';
$status = complete99_owner_publication_cached_status();
$held_lookup = array_fill_keys($approvals['required_entity_ids'], true);
$public_ids = array();
$standalone_ids = array();
$held = array();
$public_link_violations = array();
foreach ($science['entities'] as $entity) {{
    if ($entity['publication']['public_page']) {{
        $public_ids[] = $entity['id'];
        if ('standalone' === $entity['seo']['route_mode']) {{
            $standalone_ids[] = $entity['id'];
        }}
        foreach ($entity['seo']['semantic_entity_ids'] as $target_id) {{
            if (isset($held_lookup[$target_id])) {{
                $public_link_violations[] = $entity['id'] . ':semantic:' . $target_id;
            }}
        }}
        foreach ($entity['seo']['link_plan'] as $link) {{
            if ($link['public_safe'] && isset($held_lookup[$link['target_id']])) {{
                $public_link_violations[] = $entity['id'] . ':link:' . $link['target_id'];
            }}
        }}
    }}
    if (isset($held_lookup[$entity['id']])) {{
        $held[$entity['id']] = array(
            'surface_class' => $entity['surface_class'],
            'index_policy' => $entity['index_policy'],
            'publication' => $entity['publication'],
            'route_mode' => $entity['seo']['route_mode'],
            'public_link_count' => count(array_filter($entity['seo']['link_plan'], static function($link) {{ return $link['public_safe']; }})),
            'public_fact_count' => count(array_filter($entity['facts'], static function($fact) {{ return $fact['public_safe']; }})),
            'public_relation_count' => count(array_filter($entity['relations'], static function($relation) {{ return $relation['public_safe']; }})),
            'public_compliance_count' => count(array_filter($entity['compliance'], static function($note) {{ return $note['public_safe']; }})),
            'public_taxonomy' => array(
                $entity['taxonomy']['public_category_path'],
                $entity['taxonomy']['public_attribute_keys'],
                $entity['taxonomy']['public_tags'],
            ),
        );
    }}
}}
$assets = array();
foreach ($manifest['science_assets'] as $asset) {{
    $assets[$asset['related_entity_code']] = array(
        'review_state' => $asset['review_state'],
        'usage_state' => $asset['usage_state'],
        'presentation_scope' => $asset['presentation_scope'],
        'approval_state' => $asset['publication_approval_state'],
        'approval_receipt_id' => $asset['publication_approval_receipt_id'],
        'rights_receipt_digest' => $asset['rights_receipt_digest'],
        'files' => $asset['files'],
    );
}}
echo json_encode(array(
    'approvals' => $approvals,
    'shape_valid' => complete99_owner_publication_registry_shape_is_valid($approvals, $approvals['required_entity_ids']),
    'status_valid' => complete99_owner_publication_status_is_valid($status, $approvals['required_entity_ids']),
    'status' => $status,
    'public_ids' => $public_ids,
    'standalone_ids' => $standalone_ids,
    'indexable_count' => count(array_filter($science['entities'], static function($entity) {{ return $entity['publication']['search_index']; }})),
    'held' => $held,
    'public_link_violations' => $public_link_violations,
    'assets' => $assets,
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
"""
    )

    approvals = payload["approvals"]
    assert set(approvals) == {
        "schema",
        "generated_at",
        "required_locales",
        "required_entity_ids",
        "trusted_owner_keys",
        "candidates",
        "receipts",
    }
    assert approvals["schema"] == "complete99-owner-publication-approval-registry/v2"
    assert approvals["required_locales"] == ["he", "en"]
    assert approvals["required_entity_ids"] == list(HELD_IDS)
    assert approvals["trusted_owner_keys"] == []
    assert approvals["receipts"] == []
    assert payload["shape_valid"] is True
    assert payload["status_valid"] is True

    status = payload["status"]
    assert status["schema"] == "complete99-owner-publication-approval-status/v2"
    assert status["candidate_count"] == 12
    assert status["approved_count"] == 0
    assert status["held_count"] == 12
    assert status["approved_entity_ids"] == []
    assert status["status_sha256"].startswith("sha256:")
    assert set(status["decisions"]) == set(HELD_IDS)
    for entity_id, decision in status["decisions"].items():
        assert decision["entity_id"] == entity_id
        assert decision["approved"] is False
        assert decision["state"] == "held_pending_owner_approval"
        assert decision["reason"] == "missing_owner_receipt"
        assert decision["receipt_id"] == ""
        assert decision["receipt_sha256"] == ""
        assert decision["approved_at"] == ""
        assert decision["delivery_validation"] == "not_evaluated"
        assert decision["candidate_sha256"] == approvals["candidates"][entity_id][
            "candidate_sha256"
        ]
        assert decision["current_content_sha256"] == decision[
            "candidate_content_sha256"
        ]

    assert len(payload["public_ids"]) == 27
    assert not (set(payload["public_ids"]) & set(HELD_IDS))
    assert len(payload["standalone_ids"]) == 19
    assert 2 * len(payload["standalone_ids"]) == 38
    assert payload["indexable_count"] == 0
    assert payload["public_link_violations"] == []

    assert set(payload["held"]) == set(HELD_IDS)
    for held in payload["held"].values():
        assert held["surface_class"] == "editorial_draft"
        assert held["index_policy"] == "noindex_private"
        assert held["publication"] == {
            "state": "private_preview",
            "public_api": False,
            "public_page": False,
            "search_index": False,
            "approved_at": "",
        }
        assert held["route_mode"] == "private"
        assert held["public_link_count"] == 0
        assert held["public_fact_count"] == 0
        assert held["public_relation_count"] == 0
        assert held["public_compliance_count"] == 0
        assert held["public_taxonomy"] == [[], [], []]

    science_source = SCIENCE
    for entity_id, candidate in approvals["candidates"].items():
        assert candidate["schema"] == "complete99-owner-publication-candidate/v2"
        assert candidate["entity_id"] == entity_id
        assert candidate["state"] == "held_pending_owner_approval"
        assert candidate["bilingual_content"]["canonicalization"] == (
            "complete99-owner-publication-pre-gate-content/v1"
        )
        assert candidate["bilingual_content"]["locales"] == ["he", "en"]
        assert candidate["bilingual_content"]["sha256"] == status["decisions"][
            entity_id
        ]["current_content_sha256"]
        assert set(candidate["delivery_files"]) == {
            "webp",
            "avif",
            "webp_768",
            "avif_768",
        }
        assert candidate["deployment_policy"] == {
            "source_asset": "source_tree_only",
            "held_delivery_files": "must_be_absent",
            "approved_delivery_files": "must_match_exactly",
        }
        source_receipt = candidate["registry_source"]
        assert source_receipt["relative_path"] == "data/culinary-science-pilot.php"
        assert source_receipt["bytes"] == science_source.stat().st_size
        assert source_receipt["sha256"] == f"sha256:{_sha256(science_source)}"
        source_asset = candidate["source_asset"]
        source_delivery = PLUGIN / source_asset["relative_path"]
        assert source_asset["relative_path"].endswith(".png")
        assert source_asset["bytes"] == source_delivery.stat().st_size
        assert source_asset["sha256"] == f"sha256:{_sha256(source_delivery)}"
        for receipt in candidate["delivery_files"].values():
            delivery = PLUGIN / receipt["relative_path"]
            assert delivery.is_file()
            assert receipt["bytes"] == delivery.stat().st_size
            assert receipt["sha256"] == f"sha256:{_sha256(delivery)}"

    assert set(payload["assets"]) == set(HELD_IDS)
    for entity_id, asset in payload["assets"].items():
        assert asset["review_state"] == "evaluation"
        assert asset["usage_state"] == "held"
        assert asset["presentation_scope"] == "illustrative_evaluation_only"
        assert asset["approval_state"] == "held_pending_owner_approval"
        assert asset["approval_receipt_id"] == ""
        assert asset["rights_receipt_digest"] == ""
        candidate = approvals["candidates"][entity_id]
        source_receipt = candidate["source_asset"]
        manifest_source = asset["files"]["png"]
        assert source_receipt["relative_path"] == manifest_source["relative_path"]
        assert source_receipt["bytes"] == manifest_source["bytes"]
        assert source_receipt["sha256"] == f"sha256:{manifest_source['sha256']}"
        candidate_files = candidate["delivery_files"]
        for key, candidate_receipt in candidate_files.items():
            manifest_receipt = asset["files"][key]
            assert candidate_receipt["relative_path"] == manifest_receipt[
                "relative_path"
            ]
            assert candidate_receipt["bytes"] == manifest_receipt["bytes"]
            assert candidate_receipt["sha256"] == (
                f"sha256:{manifest_receipt['sha256']}"
            )


def test_signed_owner_receipt_is_the_only_promotion_path_and_tamper_fails_closed():
    payload = _run_php(
        f"""
define('ABSPATH', __DIR__);
require '{_php_path(SCIENCE)}';
$registry = require '{_php_path(APPROVALS)}';
$entities = complete99_owner_publication_cached_pre_gate_entities();
$entity_id = 'ingredient-shoyu-koji';
$entity = $entities[$entity_id];
$candidate = $registry['candidates'][$entity_id];
$keypair = sodium_crypto_sign_keypair();
$secret_key = sodium_crypto_sign_secretkey($keypair);
$public_key = sodium_crypto_sign_publickey($keypair);
$key_id = 'owner-key-contract-test';
$registry['trusted_owner_keys'][$key_id] = array(
    'schema' => 'complete99-owner-signing-key/v1',
    'key_id' => $key_id,
    'owner_account_id' => 'contract.owner',
    'owner_display_name' => 'Contract Test Owner',
    'owner_role' => 'complete99_owner',
    'algorithm' => 'ed25519',
    'public_key_base64' => base64_encode($public_key),
    'public_key_sha256' => 'sha256:' . hash('sha256', $public_key),
    'status' => 'active',
    'enrolled_at' => '2026-08-08T08:00:00+03:00',
);
$receipt = array(
    'schema' => 'complete99-owner-publication-approval-receipt/v2',
    'receipt_id' => 'owner-publication-receipt-contract-test',
    'candidate_id' => $candidate['candidate_id'],
    'candidate_sha256' => $candidate['candidate_sha256'],
    'entity_id' => $entity_id,
    'decision' => 'approve_publication',
    'source_asset' => $candidate['source_asset'],
    'delivery_files' => $candidate['delivery_files'],
    'deployment_policy' => $candidate['deployment_policy'],
    'bilingual_content' => $candidate['bilingual_content'],
    'registry_source' => $candidate['registry_source'],
    'promotion_scope' => $candidate['promotion_scope'],
    'owner' => array(
        'account_id' => 'contract.owner',
        'display_name' => 'Contract Test Owner',
        'role' => 'complete99_owner',
        'human_confirmation' => true,
        'signing_key_id' => $key_id,
        'signature_base64' => '',
    ),
    'approval_statement' => 'I approve the exact bound source evidence, deployable asset variants and bilingual content for Complete99 public discovery.',
    'approved_at' => '2026-08-08T09:00:00+03:00',
    'receipt_sha256' => '',
);
$receipt['receipt_sha256'] = complete99_owner_publication_receipt_digest($receipt);
$receipt['owner']['signature_base64'] = base64_encode(
    sodium_crypto_sign_detached($receipt['receipt_sha256'], $secret_key)
);
$registry['receipts'][$entity_id] = $receipt;
$decide = static function($candidate_registry, $candidate_entity) use ($entity_id) {{
    return complete99_owner_publication_decision(
        $candidate_registry,
        $candidate_registry['required_entity_ids'],
        $candidate_entity,
        '{_php_path(PLUGIN)}'
    );
}};
$results = array('valid' => $decide($registry, $entity));

$tampered = $registry;
$tampered['receipts'] = array();
$results['missing_receipt'] = $decide($tampered, $entity);

$tampered = $registry;
$tampered['receipts'][$entity_id] = array();
$results['empty_receipt'] = $decide($tampered, $entity);

$tampered = $registry;
$tampered['trusted_owner_keys'] = array();
$results['unrecognized_owner_key'] = $decide($tampered, $entity);

$tampered = $registry;
$tampered['receipts'][$entity_id]['owner']['signature_base64'] = base64_encode(str_repeat("\\0", 64));
$results['signature'] = $decide($tampered, $entity);

$tampered = $registry;
$tampered['receipts'][$entity_id]['owner']['display_name'] = 'Forged Owner Name';
$tampered['receipts'][$entity_id]['receipt_sha256'] = complete99_owner_publication_receipt_digest($tampered['receipts'][$entity_id]);
$results['forged_owner_with_self_digest'] = $decide($tampered, $entity);

$tampered = $registry;
$tampered['receipts'][$entity_id]['receipt_sha256'] = 'sha256:' . str_repeat('0', 64);
$results['receipt_digest'] = $decide($tampered, $entity);

$tampered = $registry;
$tampered['receipts'][$entity_id]['delivery_files']['avif_768']['sha256'] = 'sha256:' . str_repeat('0', 64);
$tampered['receipts'][$entity_id]['receipt_sha256'] = complete99_owner_publication_receipt_digest($tampered['receipts'][$entity_id]);
$results['receipt_asset_binding'] = $decide($tampered, $entity);

$tampered = $registry;
$tampered['candidates'][$entity_id]['delivery_files']['webp']['sha256'] = 'sha256:' . str_repeat('0', 64);
$tampered['candidates'][$entity_id]['candidate_sha256'] = complete99_owner_publication_candidate_digest($tampered['candidates'][$entity_id]);
$results['candidate_asset'] = $decide($tampered, $entity);

$tampered = $registry;
$tampered['candidates'][$entity_id]['registry_source']['sha256'] = 'sha256:' . str_repeat('0', 64);
$tampered['candidates'][$entity_id]['candidate_sha256'] = complete99_owner_publication_candidate_digest($tampered['candidates'][$entity_id]);
$results['registry_source'] = $decide($tampered, $entity);

$tampered = $registry;
$tampered['candidates'][$entity_id]['bilingual_content']['sha256'] = 'sha256:' . str_repeat('0', 64);
$tampered['candidates'][$entity_id]['candidate_sha256'] = complete99_owner_publication_candidate_digest($tampered['candidates'][$entity_id]);
$results['candidate_content'] = $decide($tampered, $entity);

$tampered_entity = $entity;
$tampered_entity['seo']['query_variants']['en'][] = 'tampered localized list value';
$results['localized_list_content'] = $decide($registry, $tampered_entity);

$tampered = $registry;
$tampered['candidates'][$entity_id]['promotion_scope']['search_index'] = true;
$tampered['candidates'][$entity_id]['candidate_sha256'] = complete99_owner_publication_candidate_digest($tampered['candidates'][$entity_id]);
$results['scope'] = $decide($tampered, $entity);

$tampered = $registry;
$tampered['unexpected'] = true;
$results['extra_registry_field'] = $decide($tampered, $entity);

$tampered = $registry;
$tampered['receipts'][$entity_id]['unexpected'] = true;
$results['extra_receipt_field'] = $decide($tampered, $entity);

echo json_encode($results, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
"""
    )

    valid = payload.pop("valid")
    assert valid["approved"] is True
    assert valid["state"] == "owner_approved_publication"
    assert valid["reason"] == "exact_owner_receipt_and_delivery_verified"
    assert valid["delivery_validation"] == "exact"
    assert valid["receipt_sha256"].startswith("sha256:")
    assert valid["receipt_id"] == "owner-publication-receipt-contract-test"
    assert valid["approved_at"] == "2026-08-08T09:00:00+03:00"

    assert set(payload) == {
        "missing_receipt",
        "empty_receipt",
        "unrecognized_owner_key",
        "signature",
        "forged_owner_with_self_digest",
        "receipt_digest",
        "receipt_asset_binding",
        "candidate_asset",
        "registry_source",
        "candidate_content",
        "localized_list_content",
        "scope",
        "extra_registry_field",
        "extra_receipt_field",
    }
    for case, decision in payload.items():
        assert decision["approved"] is False, case
        assert decision["state"] == "held_pending_owner_approval", case
        assert decision["receipt_id"] == "", case

    assert payload["missing_receipt"]["reason"] == "missing_owner_receipt"
    assert payload["candidate_asset"]["reason"] == "invalid_owner_receipt"
    assert payload["registry_source"]["reason"] == "registry_source_mismatch"
    assert payload["candidate_content"]["reason"] == "bilingual_content_mismatch"
    assert payload["localized_list_content"]["reason"] == (
        "bilingual_content_mismatch"
    )


def test_stripped_install_requires_only_four_exact_delivery_files_after_receipt(tmp_path):
    stripped_plugin = tmp_path / "complete99-platform"
    (stripped_plugin / "data").mkdir(parents=True)
    shutil.copy2(SCIENCE, stripped_plugin / "data" / SCIENCE.name)

    payload = _run_php(
        f"""
define('ABSPATH', __DIR__);
require '{_php_path(SCIENCE)}';
$registry = require '{_php_path(APPROVALS)}';
$entities = complete99_owner_publication_cached_pre_gate_entities();
$entity_id = 'ingredient-shoyu-koji';
$entity = $entities[$entity_id];
$candidate = $registry['candidates'][$entity_id];
$stripped_plugin = '{_php_path(stripped_plugin)}';
$decide = static function($candidate_registry) use ($entity, $stripped_plugin) {{
    return complete99_owner_publication_decision(
        $candidate_registry,
        $candidate_registry['required_entity_ids'],
        $entity,
        $stripped_plugin
    );
}};
$missing_receipt = $decide($registry);

$keypair = sodium_crypto_sign_keypair();
$secret_key = sodium_crypto_sign_secretkey($keypair);
$public_key = sodium_crypto_sign_publickey($keypair);
$key_id = 'owner-key-stripped-test';
$registry['trusted_owner_keys'][$key_id] = array(
    'schema' => 'complete99-owner-signing-key/v1',
    'key_id' => $key_id,
    'owner_account_id' => 'stripped.owner',
    'owner_display_name' => 'Stripped Test Owner',
    'owner_role' => 'complete99_owner',
    'algorithm' => 'ed25519',
    'public_key_base64' => base64_encode($public_key),
    'public_key_sha256' => 'sha256:' . hash('sha256', $public_key),
    'status' => 'active',
    'enrolled_at' => '2026-08-08T08:00:00+03:00',
);
$receipt = array(
    'schema' => 'complete99-owner-publication-approval-receipt/v2',
    'receipt_id' => 'owner-publication-receipt-stripped-test',
    'candidate_id' => $candidate['candidate_id'],
    'candidate_sha256' => $candidate['candidate_sha256'],
    'entity_id' => $entity_id,
    'decision' => 'approve_publication',
    'source_asset' => $candidate['source_asset'],
    'delivery_files' => $candidate['delivery_files'],
    'deployment_policy' => $candidate['deployment_policy'],
    'bilingual_content' => $candidate['bilingual_content'],
    'registry_source' => $candidate['registry_source'],
    'promotion_scope' => $candidate['promotion_scope'],
    'owner' => array(
        'account_id' => 'stripped.owner',
        'display_name' => 'Stripped Test Owner',
        'role' => 'complete99_owner',
        'human_confirmation' => true,
        'signing_key_id' => $key_id,
        'signature_base64' => '',
    ),
    'approval_statement' => 'I approve the exact bound source evidence, deployable asset variants and bilingual content for Complete99 public discovery.',
    'approved_at' => '2026-08-08T09:00:00+03:00',
    'receipt_sha256' => '',
);
$receipt['receipt_sha256'] = complete99_owner_publication_receipt_digest($receipt);
$receipt['owner']['signature_base64'] = base64_encode(sodium_crypto_sign_detached($receipt['receipt_sha256'], $secret_key));
$registry['receipts'][$entity_id] = $receipt;
$delivery_missing = $decide($registry);
$delivery_status = complete99_owner_publication_registry_status(
    $registry,
    $registry['required_entity_ids'],
    array_values($entities),
    $stripped_plugin
);
$invalid_registry = $registry;
$invalid_registry['receipts'][$entity_id]['owner']['signature_base64'] = base64_encode(str_repeat("\\0", 64));
$invalid_receipt = $decide($invalid_registry);

foreach ($candidate['delivery_files'] as $file_receipt) {{
    $target = '{_php_path(stripped_plugin)}/' . $file_receipt['relative_path'];
    if (!is_dir(dirname($target))) {{ mkdir(dirname($target), 0777, true); }}
    copy('{_php_path(PLUGIN)}/' . $file_receipt['relative_path'], $target);
}}
$delivery_exact = $decide($registry);
$corrupt_path = '{_php_path(stripped_plugin)}/' . $candidate['delivery_files']['webp']['relative_path'];
file_put_contents($corrupt_path, 'x', FILE_APPEND);
$delivery_mismatch = $decide($registry);

echo json_encode(array(
    'missing_receipt' => $missing_receipt,
    'delivery_missing' => $delivery_missing,
    'delivery_status_valid' => complete99_owner_publication_status_is_valid($delivery_status, $registry['required_entity_ids']),
    'delivery_status_decision' => $delivery_status['decisions'][$entity_id],
    'invalid_receipt' => $invalid_receipt,
    'delivery_exact' => $delivery_exact,
    'delivery_mismatch' => $delivery_mismatch,
    'source_png_installed' => is_file('{_php_path(stripped_plugin)}/' . $candidate['source_asset']['relative_path']),
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
"""
    )

    assert payload["source_png_installed"] is False
    assert payload["missing_receipt"]["reason"] == "missing_owner_receipt"
    assert payload["missing_receipt"]["delivery_validation"] == "not_evaluated"
    assert payload["delivery_missing"]["state"] == "held_pending_exact_asset_delivery"
    assert payload["delivery_missing"]["reason"] == "approved_delivery_bundle_missing"
    assert payload["delivery_missing"]["delivery_validation"] == "missing"
    assert payload["delivery_status_valid"] is True
    assert payload["delivery_status_decision"] == payload["delivery_missing"]
    assert payload["invalid_receipt"]["state"] == "held_pending_owner_approval"
    assert payload["invalid_receipt"]["reason"] == "invalid_owner_receipt"
    assert payload["invalid_receipt"]["delivery_validation"] == "not_evaluated"
    assert payload["delivery_exact"]["approved"] is True
    assert payload["delivery_exact"]["delivery_validation"] == "exact"
    assert payload["delivery_mismatch"]["state"] == "held_pending_exact_asset_delivery"
    assert payload["delivery_mismatch"]["reason"] == "approved_delivery_bundle_mismatch"
    assert payload["delivery_mismatch"]["delivery_validation"] == "mismatch"


def test_manifest_recomputes_authority_and_rejects_coherent_cached_status_poison():
    payload = _run_php(
        f"""
define('ABSPATH', __DIR__);
require '{_php_path(SCIENCE)}';
$registry = require '{_php_path(APPROVALS)}';
$entity_id = 'ingredient-shoyu-koji';
$poison = complete99_owner_publication_cached_status();
$poison['decisions'][$entity_id]['approved'] = true;
$poison['decisions'][$entity_id]['state'] = 'owner_approved_publication';
$poison['decisions'][$entity_id]['reason'] = 'exact_owner_receipt_and_delivery_verified';
$poison['decisions'][$entity_id]['receipt_id'] = 'owner-publication-receipt-coherent-poison';
$poison['decisions'][$entity_id]['receipt_sha256'] = 'sha256:' . str_repeat('1', 64);
$poison['decisions'][$entity_id]['approved_at'] = '2026-08-08T09:00:00+03:00';
$poison['decisions'][$entity_id]['delivery_validation'] = 'exact';
$poison['approved_entity_ids'] = array($entity_id);
$poison['approved_count'] = 1;
$poison['held_count'] = 11;
$poison['status_sha256'] = complete99_owner_publication_status_digest($poison);
$poison_valid = complete99_owner_publication_status_is_valid($poison, $registry['required_entity_ids']);
complete99_owner_publication_cached_status($poison);
set_error_handler(static function($severity, $message, $file, $line) {{
    throw new ErrorException($message, 0, $severity, $file, $line);
}});
$manifest = require '{_php_path(MANIFEST)}';
restore_error_handler();
$recomputed_status = complete99_owner_publication_cached_status();
$asset = array();
foreach ($manifest['science_assets'] as $candidate_asset) {{
    if ($entity_id === $candidate_asset['related_entity_code']) {{
        $asset = $candidate_asset;
        break;
    }}
}}
echo json_encode(array(
    'poison_valid' => $poison_valid,
    'review_state' => $asset['review_state'] ?? '',
    'usage_state' => $asset['usage_state'] ?? '',
    'approval_state' => $asset['publication_approval_state'] ?? '',
    'receipt_id' => $asset['publication_approval_receipt_id'] ?? '',
    'rights_digest' => $asset['rights_receipt_digest'] ?? '',
    'cached_approved_count' => $recomputed_status['approved_count'] ?? -1,
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
"""
    )

    assert payload == {
        "poison_valid": True,
        "review_state": "evaluation",
        "usage_state": "held",
        "approval_state": "held_pending_owner_approval",
        "receipt_id": "",
        "rights_digest": "",
        "cached_approved_count": 0,
    }


def test_v2_candidate_metadata_validation_is_pure_and_exact():
    payload = _run_php(
        f"""
define('ABSPATH', __DIR__);
$registry = require '{_php_path(APPROVALS)}';
$entity_id = 'ingredient-shoyu-koji';
$check = static function($candidate) use ($registry, $entity_id) {{
    $candidate['candidate_sha256'] = complete99_owner_publication_candidate_digest($candidate);
    $candidate_registry = $registry;
    $candidate_registry['candidates'][$entity_id] = $candidate;
    return complete99_owner_publication_registry_shape_is_valid(
        $candidate_registry,
        $candidate_registry['required_entity_ids']
    );
}};
$base = $registry['candidates'][$entity_id];
$cases = array();
$candidate = $base;
$candidate['source_asset']['relative_path'] = 'assets/images/science/../escape.png';
$cases['parent_segment'] = $check($candidate);
$candidate = $base;
$candidate['delivery_files']['webp']['relative_path'] = 'assets/images/science/c99-science-other-stem-v01.webp';
$cases['mixed_stem'] = $check($candidate);
$candidate = $base;
$candidate['delivery_files']['webp_768']['relative_path'] = str_replace('-768.webp', '.webp', $candidate['delivery_files']['webp_768']['relative_path']);
$cases['wrong_suffix'] = $check($candidate);
$candidate = $base;
$candidate['delivery_files']['avif']['bytes'] = (string) $candidate['delivery_files']['avif']['bytes'];
$cases['non_integer_bytes'] = $check($candidate);
$candidate = $base;
$candidate['source_asset']['sha256'] = strtoupper($candidate['source_asset']['sha256']);
$cases['uppercase_digest'] = $check($candidate);
$candidate = $base;
$candidate['deployment_policy']['held_delivery_files'] = 'may_be_present';
$cases['deployment_policy'] = $check($candidate);
$candidate = $base;
$candidate['registry_source']['relative_path'] = 'data/other.php';
$cases['registry_source_path'] = $check($candidate);
$candidate = $base;
$candidate['unexpected'] = true;
$cases['extra_candidate_field'] = $check($candidate);
echo json_encode($cases, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
"""
    )

    assert payload
    assert not any(payload.values())
