"""Execute the read-only PHP drift classifier and validate its log projection."""
from __future__ import annotations

import copy
import importlib.util
import json
from pathlib import Path
import shutil
import subprocess

import pytest

ROOT = Path(__file__).resolve().parents[1]
SPEC = importlib.util.spec_from_file_location("drift_recover", ROOT / "scripts/recover-wordpress.py")
RECOVER = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(RECOVER)


def php_diagnostic(case):
    if not shutil.which("php"):
        pytest.skip("PHP required")
    bridge = (ROOT / "deploy/temporary-bridge.php").read_text(encoding="utf-8")
    block = "$candidate_resume_diagnostic =" + bridge.split("$candidate_resume_diagnostic =", 1)[1].split("$candidate_resume_commit_valid =", 1)[0]
    setup = r'''<?php
class WP_Error {}
function is_wp_error($v) { return $v instanceof WP_Error; }
function wp_json_encode($v) { return json_encode($v); }
$decrypt_database_state = fn($v) => isset($v['fixture']) ? $v['fixture'] : new WP_Error();
$baseline = ['posts'=>[['ID'=>'1','post_content'=>'PRIVATE_BODY','post_modified'=>'old']], 'options'=>['rewrite_rules'=>['option_value'=>'PRIVATE_ROUTES']], 'postmeta'=>[], 'ops_tables'=>[], 'campaign_tables'=>[]];
$snapshot = $baseline;
$state = ['candidate_resume_database_journal'=>['fixture'=>$baseline], 'candidate_resume_database_fingerprint'=>hash('sha256',json_encode($baseline))];
'''
    changes = {
        "same": "",
        "timestamp": "$snapshot['posts'][0]['post_modified']='new';",
        "content": "$snapshot['posts'][0]['post_content']='PRIVATE_NEW_BODY';",
        "option": "$snapshot['options']['rewrite_rules']['option_value']='PRIVATE_NEW_ROUTES';",
        "added": "$snapshot['posts'][]=['ID'=>'2','post_content'=>'PRIVATE_NEW_BODY'];",
        "corrupt": "$state['candidate_resume_database_fingerprint']=str_repeat('0',64);",
        "missing": "$state=[];",
        "duplicate": "$snapshot['posts'][]=$snapshot['posts'][0];",
        "committed": "$state['candidate_resume_committed_journal']=['encrypted'=>'PRIVATE'];",
    }
    finish = "\n$before=serialize([$state,$snapshot]); $result=$candidate_resume_diagnostic($state,$snapshot); if ($before!==serialize([$state,$snapshot])) { exit(5); } echo json_encode($result);"
    result = subprocess.run(["php"], input=setup + changes[case] + block + finish, text=True, capture_output=True, check=True)
    assert "PRIVATE" not in result.stdout
    return json.loads(result.stdout)


@pytest.mark.parametrize("case", ["same", "timestamp", "content", "option", "added", "corrupt", "missing", "duplicate", "committed"])
def test_actual_php_classifier_and_projection(case):
    diagnostic = php_diagnostic(case)
    projected = RECOVER.bounded_observation_diagnostics({"candidate_resume_diagnostic": diagnostic})
    assert projected["recovery_authority"] is False
    observed = projected["candidate_resume_drift"]
    if case in {"corrupt", "missing", "duplicate"}:
        assert observed["available"] is False
        assert observed["checks"] == {}
        return
    assert observed["available"] is True
    assert set(observed["checks"]) == RECOVER.CANDIDATE_RESUME_DIAGNOSTIC_KEYS
    changed = {key for key, value in observed["checks"].items() if value}
    assert changed == {
        "same": set(), "committed": set(),
        "timestamp": {"posts_changed", "post_modified_changed"},
        "content": {"posts_changed", "post_content_changed"},
        "option": {"options_changed", "option_rewrite_rules_changed"},
        "added": {"posts_changed", "post_membership_changed"},
    }[case]
    assert observed["committed_journal_present"] is (case == "committed")


def test_projection_rejects_unknown_keys_non_booleans_and_missing_fields():
    valid = php_diagnostic("same")
    for mutation in ("unknown", "nonboolean", "missing", "invalidjournal"):
        value = copy.deepcopy(valid)
        if mutation == "unknown":
            value["checks"]["PRIVATE_PAYLOAD"] = "SECRET"
        elif mutation == "nonboolean":
            value["checks"]["posts_changed"] = "SECRET"
        elif mutation == "missing":
            del value["checks"]["posts_changed"]
        else:
            value["journal_valid"] = False
        projected = RECOVER.bounded_observation_diagnostics({"candidate_resume_diagnostic": value})
        assert projected["candidate_resume_drift"]["available"] is False
        assert "SECRET" not in json.dumps(projected)


def test_diagnostic_does_not_change_resume_authority_or_signed_projection():
    bridge = (ROOT / "deploy/temporary-bridge.php").read_text(encoding="utf-8")
    block = bridge.split("$candidate_resume_diagnostic =", 1)[1].split("$candidate_resume_commit_valid =", 1)[0]
    for forbidden in ("update_option(", "$set_state_phase(", "$encrypt_database_state(", "wp_remote", "$wpdb", "file_put_contents("):
        assert forbidden not in block
    assert "c99_candidate_resume_unproven_drift" in bridge
    assert "candidate_resume_diagnostic" not in RECOVER.INTERRUPTED_FORWARD_SAFE_STATUS_KEYS
