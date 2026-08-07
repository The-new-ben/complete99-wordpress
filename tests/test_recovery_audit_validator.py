from __future__ import annotations

import copy
import hashlib
import importlib.util
import json
import sys
import tempfile
import unittest
from pathlib import Path
from typing import Callable


ROOT = Path(__file__).resolve().parents[1]


def load_module(name: str, path: Path):
    spec = importlib.util.spec_from_file_location(name, path)
    if spec is None or spec.loader is None:
        raise RuntimeError(f"Could not load {path}")
    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module
    spec.loader.exec_module(module)
    return module


VALIDATOR = load_module(
    "complete99_recovery_audit_validator",
    ROOT / "scripts" / "validate-recovery-audit.py",
)


FAILED_ID = "c99-prod-31171940371-1"
PRIOR_ID = "c99-prod-31158626196-1"
PROBE_ID = "c99-recovery-probe-40000000000-1"
PROOF_SHA = "a" * 64
PLUGIN_SHA = "b" * 64
DATABASE_SHA = "c" * 64
ROBOTS_SHA = "d" * 64
RECEIPT_SHA = "e" * 64
BODY_SHA = "f" * 64


def cleanup_record() -> dict[str, object]:
    return {
        "snippet_deleted": True,
        "snippet_active": False,
        "row_absence_verified": True,
        "route_404": True,
    }


def observation_cleanup_record() -> dict[str, object]:
    return {**cleanup_record(), "removed_ids": []}


def bootstrap_cleanup_record() -> dict[str, object]:
    return {
        "exact_name": "c99-deploy-bootstrap",
        "known_id": 5,
        "known_id_matched": False,
        "removed_ids": [],
        "row_absence_verified": True,
    }


def finalize_record() -> dict[str, object]:
    return {
        "finalized": True,
        "lock_released": True,
        "state_removed": True,
    }


def write_reviewed_proof(repository_root: Path) -> tuple[Path, dict[str, object], str]:
    proof: dict[str, object] = {
        "failed_run": {
            "artifact_sha256": PROOF_SHA,
            "audit_sha256": "1" * 64,
            "candidate_database_fingerprint": "2" * 64,
            "candidate_plugin_sha256": "3" * 64,
            "candidate_version": "1.17.0",
            "commit": "4" * 40,
            "deployment_id": FAILED_ID,
            "run_id": 31171940371,
        },
        "prior_run": {
            "active": True,
            "audit_sha256": "5" * 64,
            "commit": "6" * 40,
            "database_fingerprint": DATABASE_SHA,
            "database_version": "1.16.0",
            "deployment_id": PRIOR_ID,
            "plugin_sha256": PLUGIN_SHA,
            "robots_exists": True,
            "robots_sha256": ROBOTS_SHA,
            "run_id": 31158626196,
            "sync_configured": True,
            "version": "1.16.0",
        },
    }
    canonical = json.dumps(
        proof,
        ensure_ascii=False,
        separators=(",", ":"),
        sort_keys=True,
    ).encode("utf-8")
    digest = hashlib.sha256(canonical).hexdigest()
    envelope = {
        "schema": "complete99-orphaned-rollback-proof/v1",
        "proof": proof,
        "proof_sha256": digest,
    }
    path = repository_root / "docs" / "recovery-proofs" / f"{FAILED_ID}.json"
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(envelope), encoding="utf-8")
    return path, envelope, digest


def orphaned_audit(proof_digest: str) -> dict[str, object]:
    return {
        "deployment_id": FAILED_ID,
        "decision": "finish_orphaned_rollback_cleanup",
        "discovery": {
            "probe_id": PROBE_ID,
            "owner_deployment_id": FAILED_ID,
            "owner_phase": "rolling_back",
            "result": "owner-discovered",
            "cleanup": cleanup_record(),
        },
        "initial_status": {
            "phase": "rolling_back",
            "state_exists": False,
            "lock_owned": True,
            "recovery_ready": True,
        },
        "orphaned_rollback_proof": {
            "path": f"docs/recovery-proofs/{FAILED_ID}.json",
            "proof_sha256": proof_digest,
        },
        "orphaned_rollback_reconciliation": {
            "evidence_directory_exists": False,
            "evidence_directory_sha256": "",
            "lock_retained": True,
            "marker_corrected": True,
            "phase": "committed",
            "proof_sha256": proof_digest,
            "receipt_sha256": RECEIPT_SHA,
        },
        "reconciled_status": {
            "phase": "committed",
            "state_exists": False,
            "lock_owned": True,
        },
        "health": {
            "component": "complete99-platform",
            "database_version": "1.16.0",
            "deployment_id": PRIOR_ID,
            "status": "ok",
            "sync_configured": True,
            "version": "1.16.0",
        },
        "rendered_home": {
            "body_sha256": BODY_SHA,
            "deployment_id": PRIOR_ID,
            "exact_path": "/",
            "version": "1.16.0",
        },
        "orphaned_rollback_robots": {
            "sha256": ROBOTS_SHA,
            "status": 200,
        },
        "finalize": finalize_record(),
        "cleanup": cleanup_record(),
        "result": "recovered",
    }


def orphaned_receipt_resume_audit(proof_digest: str) -> dict[str, object]:
    audit = orphaned_audit(proof_digest)
    del audit["orphaned_rollback_reconciliation"]
    del audit["reconciled_status"]
    audit["initial_status"] = {
        "phase": "committed",
        "state_exists": False,
        "lock_owned": True,
        "recovery_ready": True,
    }
    audit["initial_orphaned_rollback_receipt"] = {
        "phase": "committed",
        "state_exists": False,
        "lock_owned": True,
        "committed_outcome": "rolled_back",
        "committed_expected_active": True,
        "committed_expected_absent": False,
        "committed_expected_version": "1.16.0",
        "committed_expected_deployment": PRIOR_ID,
        "committed_expected_plugin_sha256": PLUGIN_SHA,
        "committed_expected_database_fingerprint": DATABASE_SHA,
        "committed_expected_robots_exists": True,
        "committed_expected_robots_sha256": ROBOTS_SHA,
        "committed_expected_sync_configured": True,
        "orphaned_recovery_proof_sha256": proof_digest,
        "orphaned_recovery_receipt_sha256": RECEIPT_SHA,
        "orphaned_reconciled_from": "rolling_back",
        "orphaned_observed_deployment": FAILED_ID,
        "orphaned_recovery_evidence_exists": False,
        "orphaned_recovery_evidence_sha256": "",
    }
    return audit


def database_manifest() -> tuple[dict[str, object], str]:
    manifest: dict[str, object] = {
        "schema": "complete99-database-snapshot-manifest/v1",
        "sync_secret_existed": True,
        "sync_secret_configured": True,
    }
    for index, component in enumerate(
        (
            "options_without_deployment_marker",
            "posts",
            "postmeta",
            "seed_ids",
            "evaluation_ids",
        ),
        start=1,
    ):
        manifest[f"{component}_count"] = index
        manifest[f"{component}_sha256"] = format(index, "x") * 64
    canonical = json.dumps(
        manifest,
        ensure_ascii=False,
        separators=(",", ":"),
        sort_keys=True,
    ).encode("utf-8")
    return manifest, hashlib.sha256(canonical).hexdigest()


def orphaned_observation_audit(proof_digest: str) -> dict[str, object]:
    manifest, manifest_sha256 = database_manifest()
    current_database = "7" * 64
    projected_database = "8" * 64
    return {
        "bootstrap_cleanup": bootstrap_cleanup_record(),
        "bridge_site_identity": {
            "home_host": "complete99.co.il",
            "rest_host": "complete99.co.il",
            "siteurl_host": "complete99.co.il",
        },
        "deployment_id": FAILED_ID,
        "decision": "observe_orphaned_rollback",
        "discovery": {
            "bootstrap_cleanup": bootstrap_cleanup_record(),
            "probe_id": PROBE_ID,
            "owner_deployment_id": FAILED_ID,
            "owner_phase": "rolling_back",
            "result": "owner-discovered",
            "cleanup": observation_cleanup_record(),
        },
        "finished_at": "2026-08-07T12:00:01Z",
        "identity": {
            "id": 1,
            "roles": ["administrator"],
            "site_identity": {
                "home": "https://complete99.co.il",
                "url": "https://complete99.co.il",
            },
        },
        "initial_status": {
            "phase": "rolling_back",
            "state_exists": False,
            "lock_owned": True,
            "recovery_ready": True,
            "process_lock_available": True,
            "database_fingerprint": current_database,
            "projected_deployment_id": PRIOR_ID,
            "projected_database_fingerprint": projected_database,
            "database_manifest_sha256": manifest_sha256,
            "database_storage": {"engine": "INNODB", "tables": 3},
        },
        "local_test": False,
        "orphaned_rollback_proof": {
            "path": f"docs/recovery-proofs/{FAILED_ID}.json",
            "proof_sha256": proof_digest,
        },
        "orphaned_rollback_observation": {
            "schema": "complete99-orphaned-rollback-observation/v1",
            "deployment_id": FAILED_ID,
            "proof_sha256": proof_digest,
            "phase": "rolling_back",
            "state_exists": False,
            "lock_owned": True,
            "recovery_ready": True,
            "process_lock_available": True,
            "current_version": "1.16.0",
            "current_database_version": "1.16.0",
            "current_active": True,
            "current_plugin_sha256": PLUGIN_SHA,
            "current_deployment": FAILED_ID,
            "current_database_fingerprint": current_database,
            "projected_deployment_id": PRIOR_ID,
            "projected_database_fingerprint": projected_database,
            "historical_baseline_database_fingerprint": DATABASE_SHA,
            "historical_baseline_matches_projection": False,
            "current_sync_configured": True,
            "current_robots_sha256": ROBOTS_SHA,
            "database_manifest": manifest,
            "database_manifest_sha256": manifest_sha256,
            "database_storage": {"engine": "INNODB", "tables": 3},
            "failed_candidate_database_fingerprint": "2" * 64,
        },
        "cleanup": observation_cleanup_record(),
        "result": "orphaned-rollback-observed",
        "started_at": "2026-08-07T12:00:00Z",
    }


def write_audit(audit_root: Path, audit: dict[str, object]) -> Path:
    audit_root.mkdir(parents=True, exist_ok=True)
    path = audit_root / f"{audit['deployment_id']}.json"
    path.write_text(json.dumps(audit), encoding="utf-8")
    return path.resolve()


class RecoveryAuditValidatorTests(unittest.TestCase):
    def validate(
        self,
        repository_root: Path,
        audit_root: Path,
        audit: dict[str, object],
        proof_path: str,
        *,
        expect_observation: bool = False,
    ) -> dict[str, object]:
        audit_path = write_audit(audit_root, audit)
        summary = json.dumps(
            {
                "audit": str(audit_path),
                "deployment_id": audit["deployment_id"],
                "result": audit["result"],
            }
        )
        return VALIDATOR.validate_recovery_audit(
            summary,
            proof_path,
            audit_root,
            PROBE_ID,
            repository_root=repository_root,
            expect_observation=expect_observation,
        )

    def test_exact_orphaned_recovery_proof_and_audit_pass(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, _, proof_digest = write_reviewed_proof(repository_root)
            result = self.validate(
                repository_root,
                repository_root / "recovery-audit",
                orphaned_audit(proof_digest),
                str(proof_path.relative_to(repository_root)),
            )
        self.assertEqual(
            {
                "deployment_id": FAILED_ID,
                "proof_consumed": True,
                "result": "recovered",
                "validated": True,
            },
            result,
        )

    def test_exact_durable_receipt_resume_proof_and_audit_pass(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, _, proof_digest = write_reviewed_proof(repository_root)
            result = self.validate(
                repository_root,
                repository_root / "recovery-audit",
                orphaned_receipt_resume_audit(proof_digest),
                str(proof_path.relative_to(repository_root)),
            )
        self.assertTrue(result["proof_consumed"])
        self.assertEqual("recovered", result["result"])

    def test_exact_orphaned_observation_passes_without_consuming_proof(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, _, proof_digest = write_reviewed_proof(repository_root)
            result = self.validate(
                repository_root,
                repository_root / "recovery-audit",
                orphaned_observation_audit(proof_digest),
                str(proof_path.relative_to(repository_root)),
                expect_observation=True,
            )
        self.assertEqual(
            {
                "deployment_id": FAILED_ID,
                "proof_consumed": False,
                "proof_observed": True,
                "result": "orphaned-rollback-observed",
                "validated": True,
            },
            result,
        )

    def test_observation_rejects_manifest_tampering_and_mutation_fields(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, _, proof_digest = write_reviewed_proof(repository_root)
            proof_argument = str(proof_path.relative_to(repository_root))
            audit_root = repository_root / "recovery-audit"

            tampered = orphaned_observation_audit(proof_digest)
            tampered["orphaned_rollback_observation"]["database_manifest"][
                "posts_count"
            ] = 99
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "digest does not match",
            ):
                self.validate(
                    repository_root,
                    audit_root,
                    tampered,
                    proof_argument,
                    expect_observation=True,
                )

            mutated = orphaned_observation_audit(proof_digest)
            mutated["finalize"] = finalize_record()
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "unexpected or missing fields",
            ):
                self.validate(
                    repository_root,
                    audit_root,
                    mutated,
                    proof_argument,
                    expect_observation=True,
                )

            false_comparison = orphaned_observation_audit(proof_digest)
            false_comparison["orphaned_rollback_observation"][
                "historical_baseline_matches_projection"
            ] = True
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "does not match its fingerprints",
            ):
                self.validate(
                    repository_root,
                    audit_root,
                    false_comparison,
                    proof_argument,
                    expect_observation=True,
                )

            extra_field = orphaned_observation_audit(proof_digest)
            extra_field["rollback"] = {"raw": "must-not-be-certified"}
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "unexpected or missing fields",
            ):
                self.validate(
                    repository_root,
                    audit_root,
                    extra_field,
                    proof_argument,
                    expect_observation=True,
                )

            local = orphaned_observation_audit(proof_digest)
            local["local_test"] = True
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "not production-only",
            ):
                self.validate(
                    repository_root,
                    audit_root,
                    local,
                    proof_argument,
                    expect_observation=True,
                )

            nontransactional = orphaned_observation_audit(proof_digest)
            nontransactional["orphaned_rollback_observation"][
                "database_storage"
            ]["engine"] = "MYISAM"
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "not fully transactional",
            ):
                self.validate(
                    repository_root,
                    audit_root,
                    nontransactional,
                    proof_argument,
                    expect_observation=True,
                )

    def test_orphaned_proof_requires_exactly_one_consumption_shape(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, _, proof_digest = write_reviewed_proof(repository_root)
            proof_argument = str(proof_path.relative_to(repository_root))
            audit_root = repository_root / "recovery-audit"

            both = orphaned_audit(proof_digest)
            both["initial_orphaned_rollback_receipt"] = (
                orphaned_receipt_resume_audit(proof_digest)[
                    "initial_orphaned_rollback_receipt"
                ]
            )
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "exactly one orphaned rollback consumption path",
            ):
                self.validate(repository_root, audit_root, both, proof_argument)

            neither = orphaned_audit(proof_digest)
            del neither["orphaned_rollback_reconciliation"]
            del neither["reconciled_status"]
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "exactly one orphaned rollback consumption path",
            ):
                self.validate(repository_root, audit_root, neither, proof_argument)

    def test_proof_rejects_wrong_stage_decision_and_cleanup(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, _, proof_digest = write_reviewed_proof(repository_root)
            proof_argument = str(proof_path.relative_to(repository_root))
            audit_root = repository_root / "recovery-audit"
            cases: list[tuple[str, Callable[[dict[str, object]], None]]] = [
                (
                    "wrong decision",
                    lambda value: value.__setitem__("decision", "already_finalized"),
                ),
                (
                    "wrong orphan phase",
                    lambda value: value["initial_status"].__setitem__(
                        "phase", "committed"
                    ),
                ),
                (
                    "state unexpectedly exists",
                    lambda value: value["initial_status"].__setitem__(
                        "state_exists", True
                    ),
                ),
                (
                    "reconciliation lost lock",
                    lambda value: value["orphaned_rollback_reconciliation"].__setitem__(
                        "lock_retained", False
                    ),
                ),
                (
                    "finalization retained lock",
                    lambda value: value["finalize"].__setitem__(
                        "lock_released", False
                    ),
                ),
                (
                    "snippet row remained",
                    lambda value: value["cleanup"].__setitem__(
                        "row_absence_verified", False
                    ),
                ),
            ]
            for label, mutate in cases:
                with self.subTest(label=label):
                    audit = copy.deepcopy(orphaned_audit(proof_digest))
                    mutate(audit)
                    with self.assertRaises(VALIDATOR.AuditValidationError):
                        self.validate(
                            repository_root,
                            audit_root,
                            audit,
                            proof_argument,
                        )

    def test_supplied_proof_rejects_no_owner_instead_of_succeeding(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, _, _ = write_reviewed_proof(repository_root)
            audit = {
                "deployment_id": PROBE_ID,
                "discovery": {
                    "probe_id": PROBE_ID,
                    "result": "no-owner",
                    "finalize": finalize_record(),
                    "cleanup": cleanup_record(),
                },
                "result": "no-recovery-needed",
            }
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "exact failed deployment was not recovered",
            ):
                self.validate(
                    repository_root,
                    repository_root / "recovery-audit",
                    audit,
                    str(proof_path.relative_to(repository_root)),
                )

    def test_no_proof_accepts_only_exact_no_owner_probe_with_full_cleanup(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            audit = {
                "deployment_id": PROBE_ID,
                "discovery": {
                    "probe_id": PROBE_ID,
                    "result": "no-owner",
                    "finalize": finalize_record(),
                    "cleanup": cleanup_record(),
                },
                "result": "no-recovery-needed",
            }
            result = self.validate(
                repository_root,
                repository_root / "recovery-audit",
                audit,
                "",
            )
            self.assertFalse(result["proof_consumed"])

            audit["discovery"]["cleanup"]["route_404"] = False
            with self.assertRaises(VALIDATOR.AuditValidationError):
                self.validate(
                    repository_root,
                    repository_root / "recovery-audit",
                    audit,
                    "",
                )

    def test_tampered_or_escaped_proof_and_wrong_audit_path_fail(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, envelope, proof_digest = write_reviewed_proof(repository_root)
            audit_root = repository_root / "recovery-audit"
            audit = orphaned_audit(proof_digest)
            proof_argument = str(proof_path.relative_to(repository_root))

            escaped = repository_root / "outside.json"
            escaped.write_text(json.dumps(envelope), encoding="utf-8")
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "under docs/recovery-proofs",
            ):
                self.validate(repository_root, audit_root, audit, str(escaped))

            envelope["proof"]["prior_run"]["version"] = "1.15.0"
            proof_path.write_text(json.dumps(envelope), encoding="utf-8")
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "digest does not match",
            ):
                self.validate(
                    repository_root,
                    audit_root,
                    audit,
                    proof_argument,
                )

            _, _, proof_digest = write_reviewed_proof(repository_root)
            audit = orphaned_audit(proof_digest)
            audit_path = write_audit(audit_root, audit)
            wrong_path = repository_root / "other" / audit_path.name
            wrong_path.parent.mkdir()
            wrong_path.write_text(audit_path.read_text(encoding="utf-8"), encoding="utf-8")
            summary = json.dumps(
                {
                    "audit": str(wrong_path.resolve()),
                    "deployment_id": FAILED_ID,
                    "result": "recovered",
                }
            )
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "wrong audit file",
            ):
                VALIDATOR.validate_recovery_audit(
                    summary,
                    proof_argument,
                    audit_root,
                    PROBE_ID,
                    repository_root=repository_root,
                )

    def test_proof_schema_types_and_duplicate_keys_are_strict(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            repository_root = Path(temporary)
            proof_path, envelope, _ = write_reviewed_proof(repository_root)

            duplicate = (
                '{"schema":"complete99-orphaned-rollback-proof/v1",'
                '"schema":"complete99-orphaned-rollback-proof/v1",'
                f'"proof":{json.dumps(envelope["proof"])},'
                f'"proof_sha256":"{envelope["proof_sha256"]}"}}'
            )
            proof_path.write_text(duplicate, encoding="utf-8")
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "duplicate key",
            ):
                VALIDATOR.load_reviewed_proof(str(proof_path), repository_root)

            cases: list[tuple[str, Callable[[dict[str, object]], None]]] = [
                (
                    "boolean run ID",
                    lambda value: value["proof"]["failed_run"].__setitem__(
                        "run_id", True
                    ),
                ),
                (
                    "numeric digest",
                    lambda value: value["proof"]["prior_run"].__setitem__(
                        "plugin_sha256", 123
                    ),
                ),
                (
                    "unexpected identity field",
                    lambda value: value["proof"]["prior_run"].__setitem__(
                        "unexpected", "field"
                    ),
                ),
            ]
            for label, mutate in cases:
                with self.subTest(label=label):
                    strict_envelope = copy.deepcopy(envelope)
                    mutate(strict_envelope)
                    canonical = json.dumps(
                        strict_envelope["proof"],
                        ensure_ascii=False,
                        separators=(",", ":"),
                        sort_keys=True,
                    ).encode("utf-8")
                    strict_envelope["proof_sha256"] = hashlib.sha256(
                        canonical
                    ).hexdigest()
                    proof_path.write_text(
                        json.dumps(strict_envelope),
                        encoding="utf-8",
                    )
                    with self.assertRaises(VALIDATOR.AuditValidationError):
                        VALIDATOR.load_reviewed_proof(
                            str(proof_path),
                            repository_root,
                        )

    def test_exact_stage_matrix_validates_success_and_recovered_failure(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            deploy_audits = root / "deploy-audit"
            recovery_audits = root / "recovery-audit"
            commerce_audits = root / "commerce-audit"
            dry_id = "c99-dry-40000000000-1"
            production_id = "c99-prod-40000000000-1"
            commerce_id = "c99-commerce-40000000000-1"
            write_audit(
                deploy_audits,
                {
                    "deployment_id": dry_id,
                    "result": "dry-run-passed",
                    "cleanup": cleanup_record(),
                },
            )
            write_audit(
                deploy_audits,
                {
                    "deployment_id": production_id,
                    "result": "deployed",
                    "cleanup": cleanup_record(),
                },
            )
            write_audit(
                commerce_audits,
                {
                    "deployment_id": commerce_id,
                    "result": "verified",
                    "cleanup": cleanup_record(),
                },
            )
            success = VALIDATOR.validate_stage_outcomes(
                preflight_outcome="success",
                production_outcome="success",
                mutation_outcome="skipped",
                mutation_started="",
                recovery_outcome="skipped",
                commerce_outcome="success",
                commerce_recovery_outcome="skipped",
                dry_run_id=dry_id,
                production_id=production_id,
                commerce_id=commerce_id,
                deploy_audit_root=deploy_audits,
                recovery_audit_root=recovery_audits,
                commerce_audit_root=commerce_audits,
            )
            self.assertTrue(success["validated"])

            write_audit(
                deploy_audits,
                {
                    "deployment_id": production_id,
                    "result": "failed",
                },
            )
            write_audit(
                recovery_audits,
                {
                    "deployment_id": production_id,
                    "decision": "rollback_interrupted_mutation",
                    "result": "recovered",
                    "cleanup": cleanup_record(),
                },
            )
            (commerce_audits / f"{commerce_id}.json").unlink()
            recovered = VALIDATOR.validate_stage_outcomes(
                preflight_outcome="success",
                production_outcome="failure",
                mutation_outcome="success",
                mutation_started="true",
                recovery_outcome="success",
                commerce_outcome="skipped",
                commerce_recovery_outcome="skipped",
                dry_run_id=dry_id,
                production_id=production_id,
                commerce_id=commerce_id,
                deploy_audit_root=deploy_audits,
                recovery_audit_root=recovery_audits,
                commerce_audit_root=commerce_audits,
            )
            self.assertTrue(recovered["mutation_started"])

            write_audit(
                deploy_audits,
                {
                    "deployment_id": production_id,
                    "result": "deployed",
                    "cleanup": cleanup_record(),
                },
            )
            (recovery_audits / f"{production_id}.json").unlink()
            write_audit(
                commerce_audits,
                {
                    "deployment_id": commerce_id,
                    "result": "failed",
                },
            )
            commerce_recovery = {
                "audit_id": f"{commerce_id}-recovery",
                "deployment_id": commerce_id,
                "result": "verified",
                "cleanup": cleanup_record(),
            }
            commerce_recovery_path = commerce_audits / f"{commerce_id}-recovery.json"
            commerce_recovery_path.write_text(
                json.dumps(commerce_recovery),
                encoding="utf-8",
            )
            commerce_recovered = VALIDATOR.validate_stage_outcomes(
                preflight_outcome="success",
                production_outcome="success",
                mutation_outcome="skipped",
                mutation_started="",
                recovery_outcome="skipped",
                commerce_outcome="failure",
                commerce_recovery_outcome="success",
                dry_run_id=dry_id,
                production_id=production_id,
                commerce_id=commerce_id,
                deploy_audit_root=deploy_audits,
                recovery_audit_root=recovery_audits,
                commerce_audit_root=commerce_audits,
            )
            self.assertEqual("success", commerce_recovered["commerce_recovery_outcome"])

    def test_exact_stage_matrix_rejects_missing_or_impossible_audits(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            deploy_audits = root / "deploy-audit"
            recovery_audits = root / "recovery-audit"
            commerce_audits = root / "commerce-audit"
            dry_id = "c99-dry-40000000000-1"
            production_id = "c99-prod-40000000000-1"
            commerce_id = "c99-commerce-40000000000-1"
            write_audit(
                deploy_audits,
                {
                    "deployment_id": dry_id,
                    "result": "dry-run-passed",
                    "cleanup": cleanup_record(),
                },
            )
            base = {
                "preflight_outcome": "success",
                "production_outcome": "success",
                "mutation_outcome": "skipped",
                "mutation_started": "",
                "recovery_outcome": "skipped",
                "commerce_outcome": "success",
                "commerce_recovery_outcome": "skipped",
                "dry_run_id": dry_id,
                "production_id": production_id,
                "commerce_id": commerce_id,
                "deploy_audit_root": deploy_audits,
                "recovery_audit_root": recovery_audits,
                "commerce_audit_root": commerce_audits,
            }
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "production audit is missing",
            ):
                VALIDATOR.validate_stage_outcomes(**base)

            write_audit(
                deploy_audits,
                {
                    "deployment_id": production_id,
                    "result": "deployed",
                    "cleanup": cleanup_record(),
                },
            )
            write_audit(
                commerce_audits,
                {
                    "deployment_id": commerce_id,
                    "result": "verified",
                    "cleanup": cleanup_record(),
                },
            )
            impossible = dict(base)
            impossible["recovery_outcome"] = "success"
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "Recovery ran after successful production",
            ):
                VALIDATOR.validate_stage_outcomes(**impossible)

            write_audit(
                deploy_audits,
                {
                    "deployment_id": production_id,
                    "result": "failed",
                },
            )
            missing_recovery = dict(base)
            missing_recovery.update(
                {
                    "production_outcome": "failure",
                    "mutation_outcome": "success",
                    "mutation_started": "true",
                    "recovery_outcome": "success",
                    "commerce_outcome": "skipped",
                }
            )
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "production recovery audit is missing",
            ):
                VALIDATOR.validate_stage_outcomes(**missing_recovery)

    def test_observation_stage_matrix_requires_every_mutation_stage_skipped(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            arguments = {
                "observation_only": True,
                "preflight_outcome": "success",
                "production_outcome": "skipped",
                "mutation_outcome": "skipped",
                "mutation_started": "",
                "recovery_outcome": "skipped",
                "commerce_outcome": "skipped",
                "commerce_recovery_outcome": "skipped",
                "dry_run_id": "c99-dry-40000000000-1",
                "production_id": "c99-prod-40000000000-1",
                "commerce_id": "c99-commerce-40000000000-1",
                "deploy_audit_root": root / "deploy-audit",
                "recovery_audit_root": root / "recovery-audit",
                "commerce_audit_root": root / "commerce-audit",
            }
            result = VALIDATOR.validate_stage_outcomes(**arguments)
            self.assertTrue(result["observation_only"])

            crossed = dict(arguments)
            crossed["production_outcome"] = "success"
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "crossed the platform mutation path",
            ):
                VALIDATOR.validate_stage_outcomes(**crossed)

            for field in (
                "production_outcome",
                "mutation_outcome",
                "recovery_outcome",
                "commerce_outcome",
                "commerce_recovery_outcome",
            ):
                with self.subTest(cancelled_field=field):
                    cancelled = dict(arguments)
                    cancelled[field] = "cancelled"
                    with self.assertRaisesRegex(
                        VALIDATOR.AuditValidationError,
                        "crossed the platform mutation path",
                    ):
                        VALIDATOR.validate_stage_outcomes(**cancelled)

            marker = dict(arguments)
            marker["mutation_started"] = "false"
            with self.assertRaisesRegex(
                VALIDATOR.AuditValidationError,
                "crossed the platform mutation path",
            ):
                VALIDATOR.validate_stage_outcomes(**marker)


if __name__ == "__main__":
    unittest.main()
