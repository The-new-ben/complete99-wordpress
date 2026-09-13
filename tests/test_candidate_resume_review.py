"""Resume a completed repair without rebasing or rerunning its historic proof."""
from __future__ import annotations

import copy
import hashlib
import importlib.util
import json
import shutil
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path
from unittest import mock

ROOT = Path(__file__).resolve().parents[1]
PROOF = "docs/recovery-proofs/c99-prod-31620203121-1-resume-v1.json"
ORIGINAL = "docs/recovery-proofs/c99-prod-31620203121-1-v4.json"


def module(name, filename):
    spec = importlib.util.spec_from_file_location(name, ROOT / "scripts" / filename)
    result = importlib.util.module_from_spec(spec)
    sys.modules[name] = result
    spec.loader.exec_module(result)
    return result


DEPLOY = module("resume_deploy", "deploy-wordpress.py")
RECOVER = module("resume_recover", "recover-wordpress.py")
AUDIT = module("resume_audit", "validate-recovery-audit.py")


def receipt(loaded):
    repair = loaded["proof"]["forward_adoption"]["candidate_repair"]
    return {
        **{key: value for key, value in repair.items() if key != "table_suffix"},
        "table": "wp_c99_campaign_provider_receipts",
        "proof_sha256": loaded["proof_sha256"],
        "charset": "utf8mb4", "collation": "utf8mb4_unicode_ci", "completed_at": 1786564923,
    }


class CandidateResumeReviewTests(unittest.TestCase):
    def load(self):
        return RECOVER.load_interrupted_forward_proof(DEPLOY, PROOF)

    def test_review_preserves_original_proof_and_uses_exact_attempt_two(self):
        loaded = self.load()
        independent = AUDIT.load_interrupted_forward_proof(PROOF, ROOT)
        original = RECOVER.load_interrupted_forward_proof(DEPLOY, ORIGINAL)
        self.assertEqual(original["proof"], loaded["proof"])
        self.assertEqual(original["proof_sha256"], loaded["proof_sha256"])
        self.assertEqual(loaded, independent)
        safe = loaded["reviewed_resume_observation"]["safe_status"]
        self.assertEqual("531084099c88823a0af3e07e0e7eab1cac67f9b16b797f94499665aafda38f44", safe["database_fingerprint"])
        fields = RECOVER.interrupted_forward_bridge_fields(loaded)
        self.assertEqual(safe, fields["reviewed_safe_status"])
        rendered = DEPLOY.render_bridge("t" * 64, loaded["proof"]["failed_run"]["deployment_id"], 40000000, False, **fields)
        self.assertIn(loaded["resume_review_sha256"], rendered)
        self.assertNotIn("__C99_CANDIDATE_RESUME_REVIEW_SHA256__", rendered)

    def test_preflight_requires_matching_current_data_and_durable_receipt(self):
        loaded = self.load()
        status = copy.deepcopy(loaded["reviewed_resume_observation"]["safe_status"])
        status.update(candidate_repair_started=True, candidate_repair_no_rollback=True, candidate_repair_receipt=receipt(loaded))
        checkpoint = RECOVER.validate_interrupted_forward_candidate_repair_status(DEPLOY, status, loaded)
        self.assertEqual(loaded["reviewed_resume_observation"], checkpoint["observation"])
        self.assertEqual(receipt(loaded), checkpoint["repair_receipt"])
        changes = [
            lambda s: s.update(database_fingerprint="0" * 64),
            lambda s: s.update(candidate_repair_started=False),
            lambda s: s.update(candidate_repair_no_rollback=False),
            lambda s: s.update(interrupted_forward_proof_sha256="0" * 64),
            lambda s: s["candidate_repair_receipt"].update(to_type="varchar(24)"),
            lambda s: s["candidate_repair_receipt"].update(proof_sha256="0" * 64),
            lambda s: s.update(candidate_repair_receipt={}),
        ]
        for change in changes:
            with self.subTest(change=changes.index(change)):
                modified = copy.deepcopy(status)
                change(modified)
                with self.assertRaises(DEPLOY.DeployError):
                    RECOVER.validate_interrupted_forward_candidate_repair_status(DEPLOY, modified, loaded)

    def test_review_metadata_and_bound_audit_tampering_rejected_by_both_loaders(self):
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            shutil.copytree(ROOT / "docs/recovery-proofs", root / "docs/recovery-proofs")
            path = root / PROOF
            original = json.loads(path.read_text(encoding="utf-8"))
            changes = [
                lambda e: e["resume_review"].update(observation_run_attempt=1),
                lambda e: e["resume_review"].update(observation_run_attempt=True),
                lambda e: e["resume_review"].update(observation_commit="0" * 40),
                lambda e: e["resume_review"].update(original_proof_sha256="0" * 64),
                lambda e: e["resume_review"].update(observation_audit_sha256="0" * 64),
                lambda e: e["resume_review"].update(observation_run_id=1),
                lambda e: e["resume_review"].update(extra=True),
                lambda e: e.update(schema="complete99-interrupted-forward-proof/v3"),
            ]
            for change in changes:
                modified = copy.deepcopy(original)
                change(modified)
                modified["resume_review_sha256"] = RECOVER.canonical_proof_sha256(modified["resume_review"])
                path.write_text(json.dumps(modified), encoding="utf-8")
                with self.subTest(change=changes.index(change)), mock.patch.object(RECOVER, "ROOT", root):
                    with self.assertRaises(DEPLOY.DeployError):
                        RECOVER.load_interrupted_forward_proof(DEPLOY, PROOF)
                    with self.assertRaises(AUDIT.AuditValidationError):
                        AUDIT.load_interrupted_forward_proof(PROOF, root)

    def test_current_review_cannot_be_attached_to_ordinary_deployment(self):
        with self.assertRaises(DEPLOY.DeployError):
            DEPLOY.render_bridge("t" * 64, "c99-test-resume", 40000000, False, candidate_resume_review_sha256="f" * 64)

    def test_resume_skips_repair_route_and_keeps_original_authority(self):
        loaded = self.load()
        initial = copy.deepcopy(loaded["reviewed_resume_observation"]["safe_status"])
        initial.update(candidate_repair_started=True, candidate_repair_no_rollback=True, candidate_repair_receipt=receipt(loaded))
        with mock.patch.object(DEPLOY, "bridge_call", side_effect=[initial, {"continued": True}, {}]) as calls, mock.patch.object(RECOVER, "validate_candidate_repair_continue_response", side_effect=lambda d, value, p: value), mock.patch.object(RECOVER, "validate_candidate_repair_adoption_status", return_value={}):
            result = RECOVER.adopt_interrupted_forward(DEPLOY, object(), "token", loaded["proof"]["failed_run"]["deployment_id"], loaded)
        self.assertEqual(["status", "continue-activation", "status"], [call.args[1] for call in calls.call_args_list])
        self.assertEqual(loaded["proof"]["failed_run"]["deployment_id"], calls.call_args_list[0].kwargs["projected_deployment_id"])
        self.assertEqual(loaded["proof_sha256"], calls.call_args_list[1].kwargs["interrupted_forward_proof_sha256"])
        self.assertEqual(receipt(loaded), result["repair"]["receipt"])
        self.assertTrue(result["repair"]["idempotent"])

    def test_fresh_driver_restart_reaches_continuation_after_committed_changes(self):
        loaded = self.load()
        safe = copy.deepcopy(loaded["reviewed_resume_observation"]["safe_status"])
        durable = {
            "schema": "complete99-candidate-resume-durable-checkpoint/v1",
            "review_sha256": loaded["resume_review_sha256"], "proof_sha256": loaded["proof_sha256"],
            "database_fingerprint": safe["database_fingerprint"], "journal_valid": True, "activation_started": True,
        }
        safe.update(database_fingerprint="c" * 64, current_deployment=loaded["proof"]["failed_run"]["deployment_id"], migration_invariants_valid=True, robots_applied=True, robots_managed_sha256=safe["current_robots_sha256"])
        safe.update(candidate_repair_started=True, candidate_repair_no_rollback=True, candidate_repair_receipt=receipt(loaded), candidate_resume_checkpoint=durable)
        checkpoint = RECOVER.validate_interrupted_forward_candidate_repair_status(DEPLOY, safe, loaded)
        self.assertEqual("complete99-candidate-repair-resume-checkpoint/v2", checkpoint["schema"])
        with mock.patch.object(DEPLOY, "bridge_call", side_effect=[safe, {"continued": True}, {}]) as calls, mock.patch.object(RECOVER, "validate_candidate_repair_continue_response", side_effect=lambda d, v, p: v), mock.patch.object(RECOVER, "validate_candidate_repair_adoption_status", return_value={}):
            RECOVER.adopt_interrupted_forward(DEPLOY, object(), "token", loaded["proof"]["failed_run"]["deployment_id"], loaded)
        self.assertEqual(["status", "continue-activation", "status"], [c.args[1] for c in calls.call_args_list])
        changes = [
            lambda s: s.update(candidate_resume_checkpoint={}),
            lambda s: s["candidate_resume_checkpoint"].update(journal_valid=False),
            lambda s: s["candidate_resume_checkpoint"].update(review_sha256="f" * 64),
            lambda s: s["candidate_resume_checkpoint"].update(database_fingerprint="f" * 64),
            lambda s: s.update(lock_owned=False),
            lambda s: s.update(current_plugin_sha256="f" * 64),
            lambda s: s.update(current_deployment="c99-unrelated-deployment"),
            lambda s: s.update(robots_managed_sha256="f" * 64),
        ]
        for index, change in enumerate(changes):
            modified = copy.deepcopy(safe)
            change(modified)
            with self.subTest(change=index), self.assertRaises(DEPLOY.DeployError):
                RECOVER.validate_interrupted_forward_candidate_repair_status(DEPLOY, modified, loaded)

    @unittest.skipUnless(shutil.which("php"), "PHP required for executable backup guard")
    def test_executable_php_resume_backup_guard(self):
        bridge = (ROOT / "deploy/temporary-bridge.php").read_text(encoding="utf-8")
        block = bridge.split("$resume_review_sha256 =", 1)[1].split("if ( ! empty( $state['candidate_prior_active'] ) )", 1)[0]
        guard = "$resume_review_sha256 =" + block
        # Execute the actual production guard with controlled persistence failures.
        setup = r'''<?php
class WP_Error { public function __construct(public $code, public $message, public $data=[]) {} }
function is_wp_error($v) { return $v instanceof WP_Error; }
function wp_json_encode($v, $flags=0) { return json_encode($v, $flags); }
class MemoryFS { public $state; public function get_contents($path) { return json_encode($this->state); } }
$case = $argv[1]; $wp_filesystem = new MemoryFS();
$state = ['database_journal'=>['original'=>'never replace']];
$snapshot = ['posts'=>['editor-owned'], 'postmeta'=>['new-edit']];
$fingerprint = hash('sha256', json_encode($snapshot));
$manifest = ['sha'=>'manifest']; $manifest_sha = hash('sha256', json_encode($manifest));
$storage = ['engine'=>'INNODB','tables'=>3];
$proof_sha256 = str_repeat('a',64); $review = str_repeat('b',64);
$reviewed = ['interrupted_forward_proof_sha256'=>$proof_sha256,'database_fingerprint'=>$fingerprint,'database_manifest_sha256'=>$manifest_sha,'database_manifest'=>$manifest,'database_storage'=>$storage];
$interrupted = ['candidate_resume_review_sha256'=>$review,'reviewed_safe_status'=>$reviewed,'reviewed_safe_status_sha256'=>hash('sha256',json_encode($reviewed,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))];
$phase='candidate_activation_pending'; $repair_continuation=true; $state_dir='/memory'; $state_path='/memory/state.json'; $deployment_id='c99-fixture'; $writes=0;
if (in_array($case,['retry','rebind','corrupt','postcommit','notstarted'])) {
  $state += ['candidate_resume_database_journal'=>['encrypted'=>$snapshot], 'candidate_resume_database_fingerprint'=>$fingerprint,'candidate_resume_review_sha256'=>$review,'candidate_resume_activation_started'=>true];
  if ($case==='rebind') $state['candidate_resume_review_sha256']=str_repeat('c',64);
  if ($case==='corrupt') $state['candidate_resume_database_journal']=['broken'=>true];
  if ($case==='notstarted') $state['candidate_resume_activation_started']=false;
}
$wp_filesystem->state=$state;
$capture_database_state_consistent=fn()=>(in_array($case,['drift','postcommit'])?['posts'=>['changed by committed migration']]:$snapshot);
$campaign_snapshot_coherent=fn($v)=>true;
$database_snapshot_manifest=fn($v)=>['manifest'=>$manifest,'manifest_sha256'=>$manifest_sha];
$database_snapshot_manifest_valid=fn($v,$h)=>true;
$verify_transactional_storage=fn()=>($case==='storage'?['engine'=>'MYISAM','tables'=>3]:$storage);
$encrypt_database_state=fn($v)=>($case==='encrypt'?new WP_Error('encrypt','test'):['encrypted'=>$v]);
$decrypt_database_state=fn($v)=>$v['encrypted']??new WP_Error('decrypt','test');
$set_state_phase=function($dir,$id,$phase,$extra) use($wp_filesystem,$case,&$writes) {
  $writes++; $wp_filesystem->state=array_merge($wp_filesystem->state,$extra);
  if ($case==='readback') $wp_filesystem->state['candidate_resume_database_journal']=['broken'=>true];
  if ($case==='original') $wp_filesystem->state['database_journal']=['changed'=>true];
  return $wp_filesystem->state;
};
$run = function() use(&$state,$wp_filesystem,$interrupted,$proof_sha256,$phase,$repair_continuation,$state_dir,$state_path,$deployment_id,$capture_database_state_consistent,$campaign_snapshot_coherent,$database_snapshot_manifest,$database_snapshot_manifest_valid,$verify_transactional_storage,$encrypt_database_state,$decrypt_database_state,$set_state_phase) {
'''
        end = "return true; }; $result=$run(); echo json_encode(['result'=>is_wp_error($result)?$result->code:$result,'writes'=>$writes,'original'=>$state['database_journal']]);"
        cases = {"ok": (True, 1), "retry": (True, 0), "postcommit": (True, 0), "notstarted": ("c99_candidate_resume_rebinding", 0), "drift": ("c99_candidate_resume_database_changed", 0), "storage": ("c99_candidate_resume_database_changed", 0), "rebind": ("c99_candidate_resume_rebinding", 0), "corrupt": ("c99_candidate_resume_backup_readback", 0), "encrypt": ("encrypt", 0), "readback": ("c99_candidate_resume_backup_readback", 1), "original": ("c99_candidate_resume_backup_readback", 1)}
        with tempfile.TemporaryDirectory() as directory:
            fixture = Path(directory) / "guard.php"
            fixture.write_text(setup + guard + end, encoding="utf-8")
            for case, expected in cases.items():
                with self.subTest(case=case):
                    output = subprocess.run(["php", str(fixture), case], capture_output=True, text=True, check=True)
                    result = json.loads(output.stdout)
                    self.assertEqual(expected, (result["result"], result["writes"]))
                    self.assertEqual({"original": "never replace"}, result["original"])

    @unittest.skipUnless(shutil.which("php"), "PHP required for executable status attestation")
    def test_status_attestation_requires_authenticated_resume_journal(self):
        bridge = (ROOT / "deploy/temporary-bridge.php").read_text(encoding="utf-8")
        block = "$resume_checkpoint =" + bridge.split("$resume_checkpoint =", 1)[1].split("$status = array(", 1)[0]
        setup = r'''<?php
class WP_Error {}
function is_wp_error($v) { return $v instanceof WP_Error; }
function wp_json_encode($v) { return json_encode($v); }
$snapshot=['private'=>'must-not-leak']; $hash=hash('sha256',json_encode($snapshot));
$review=str_repeat('b',64); $proof=str_repeat('a',64);
$status_interrupted_config=['candidate_resume_review_sha256'=>$review,'proof_sha256'=>$proof,'reviewed_safe_status'=>['database_fingerprint'=>$hash]];
$state=['candidate_resume_activation_started'=>true,'candidate_resume_review_sha256'=>$review,'candidate_resume_database_fingerprint'=>$hash,'interrupted_forward_proof_sha256'=>$proof,'candidate_resume_database_journal'=>['snapshot'=>$snapshot]];
$decrypt_database_state=fn($v)=>$v['snapshot']??new WP_Error();
$case=$argv[1];
if ($case==='flag') $state['candidate_resume_activation_started']=false;
if ($case==='review') $state['candidate_resume_review_sha256']=str_repeat('c',64);
if ($case==='proof') $state['interrupted_forward_proof_sha256']=str_repeat('c',64);
if ($case==='hash') $state['candidate_resume_database_fingerprint']=str_repeat('c',64);
if ($case==='corrupt') $state['candidate_resume_database_journal']=[];
'''
        with tempfile.TemporaryDirectory() as directory:
            fixture = Path(directory) / "attestation.php"
            fixture.write_text(setup + block + "echo json_encode($resume_checkpoint);", encoding="utf-8")
            for case in ("ok", "flag", "review", "proof", "hash", "corrupt"):
                with self.subTest(case=case):
                    result = subprocess.run(["php", str(fixture), case], check=True, text=True, capture_output=True)
                    data = json.loads(result.stdout)
                    self.assertNotIn("must-not-leak", result.stdout)
                    if case == "ok":
                        self.assertEqual({"schema", "review_sha256", "proof_sha256", "database_fingerprint", "journal_valid", "activation_started"}, set(data))
                        self.assertTrue(data["journal_valid"])
                    else:
                        self.assertEqual([], data)

    def test_php_backup_guard_precedes_activation_and_preserves_original_journal(self):
        bridge = (ROOT / "deploy/temporary-bridge.php").read_text(encoding="utf-8")
        continuation = bridge.split("$route_prefix . '/continue-activation'", 1)[1].split("$route_prefix . '/rollback'", 1)[0]
        markers = ["$acquire_worker_fence()", "$candidate_repair_receipt_valid( $state, true )", "$resume_snapshot = $capture_database_state_consistent()", "$resume_journal = $encrypt_database_state( $resume_snapshot )", "'candidate_resume_database_journal' => $resume_journal", "$resume_readback = $decrypt_database_state(", "$activation = Complete99_Platform::recover_active_upgrade()"]
        positions = [continuation.index(marker) for marker in markers]
        self.assertEqual(sorted(positions), positions)
        self.assertIn("c99_candidate_resume_database_changed", continuation)
        self.assertIn("c99_candidate_resume_rebinding", continuation)
        self.assertIn("( $state['database_journal'] ?? null ) !== ( $resume_state['database_journal'] ?? null )", continuation)


if __name__ == "__main__":
    unittest.main()
