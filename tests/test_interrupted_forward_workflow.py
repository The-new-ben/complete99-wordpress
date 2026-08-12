from __future__ import annotations

import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
WORKFLOW = ROOT / ".github" / "workflows" / "wordpress-deploy.yml"


def workflow_text() -> str:
    return WORKFLOW.read_text(encoding="utf-8")


def between(text: str, start: str, end: str) -> str:
    return text.split(start, 1)[1].split(end, 1)[0]


class InterruptedForwardWorkflowTests(unittest.TestCase):
    def test_dispatch_inputs_and_fail_closed_mode_guard_are_explicit(self) -> None:
        workflow = workflow_text()
        interrupted_proof = between(
            workflow,
            "      interrupted_forward_proof:\n",
            "      interrupted_forward_observe_only:\n",
        )
        interrupted_observation = between(
            workflow,
            "      interrupted_forward_observe_only:\n",
            "      recovery_only:\n",
        )
        recovery_only = between(
            workflow,
            "      recovery_only:\n",
            "permissions:\n",
        )

        self.assertIn("required: false", interrupted_proof)
        self.assertIn("default: ''", interrupted_proof)
        self.assertIn("type: string", interrupted_proof)
        for boolean_input in (interrupted_observation, recovery_only):
            self.assertIn("required: true", boolean_input)
            self.assertIn("default: false", boolean_input)
            self.assertIn("type: boolean", boolean_input)

        guard = between(
            workflow,
            "- name: Validate mutually exclusive recovery inputs before any live request",
            "- name: Require the 1.3.1 rollback and identical-artifact redeploy exercise",
        )
        for mapping in (
            "COMPLETE99_ORPHANED_ROLLBACK_PROOF: ${{ inputs.orphaned_rollback_proof }}",
            "COMPLETE99_INTERRUPTED_FORWARD_PROOF: ${{ inputs.interrupted_forward_proof }}",
            "COMPLETE99_ORPHANED_ROLLBACK_OBSERVE_ONLY: ${{ inputs.orphaned_rollback_observe_only }}",
            "COMPLETE99_INTERRUPTED_FORWARD_OBSERVE_ONLY: ${{ inputs.interrupted_forward_observe_only }}",
            "COMPLETE99_RECOVERY_ONLY: ${{ inputs.recovery_only }}",
        ):
            self.assertIn(mapping, guard)
        for condition in (
            "$hasOrphanedProof -and $hasInterruptedProof",
            "$orphanedObservation -and $interruptedObservation",
            "$orphanedObservation -and -not $hasOrphanedProof",
            "$interruptedObservation -and -not $hasInterruptedProof",
            "$hasInterruptedProof -and -not ($interruptedObservation -or $recoveryOnly)",
            "$recoveryOnly -and -not $hasInterruptedProof",
            "$recoveryOnly -and ($orphanedObservation -or $interruptedObservation)",
        ):
            self.assertIn(condition, guard)

    def test_preflight_passes_and_validates_interrupted_forward_authority(self) -> None:
        workflow = workflow_text()
        preflight = between(
            workflow,
            "- name: Run live recovery probe and dry-run acceptance",
            "- name: Deploy the exact CI artifact with independent verification",
        )

        self.assertIn(
            "COMPLETE99_INTERRUPTED_FORWARD_PROOF: "
            "${{ inputs.interrupted_forward_proof }}",
            preflight,
        )
        self.assertGreaterEqual(preflight.count("--dist"), 2)
        self.assertGreaterEqual(preflight.count("$interruptedForwardDist"), 4)
        self.assertIn("$interruptedForwardDist = $releaseDir", preflight)
        self.assertIn(
            '"plugin-dist/complete99-platform-$proofVersion.zip"', preflight
        )
        self.assertIn(
            '"plugin-dist/complete99-platform-$proofVersion-integrity.json"',
            preflight,
        )
        self.assertIn(
            'Join-Path $interruptedForwardDist "complete99-platform-integrity.json"',
            preflight,
        )
        self.assertIn("$proofRelative.Contains('\\')", preflight)
        self.assertIn("$invalidProofSegments.Count -ne 0", preflight)
        self.assertIn(
            '"--interrupted-forward-proof",\n'
            "              $env:COMPLETE99_INTERRUPTED_FORWARD_PROOF,\n"
            '              "--dist",\n'
            "              $interruptedForwardDist",
            preflight,
        )
        recovery_invocation = between(
            preflight,
            "$recoveryOutput = @(& python scripts/recover-wordpress.py",
            "$recoveryExitCode = $LASTEXITCODE",
        )
        self.assertNotIn("--dist $releaseDir", recovery_invocation)
        self.assertNotIn("--dist $interruptedForwardDist", recovery_invocation)
        self.assertIn("--dist $interruptedForwardDist", preflight)
        self.assertGreaterEqual(preflight.count("--interrupted-forward-proof"), 2)
        self.assertIn("--interrupted-forward-observe-only", preflight)
        self.assertIn("--recovery-only", preflight)
        self.assertIn("--expect-interrupted-forward", preflight)
        self.assertIn("--expect-observation", preflight)
        self.assertIn("ConvertFrom-Json -ErrorAction Stop", preflight)
        self.assertIn('$recoveryRecord.result -in @(', preflight)
        self.assertIn(
            '"interrupted_forward_database_mismatch_observed"', preflight
        )
        self.assertIn(
            '"interrupted_forward_mismatch_diagnostic_observed"', preflight
        )
        self.assertIn(
            '$recoveryRecord.proof_consumed -ne $false',
            preflight,
        )
        self.assertIn(
            'Write-Error "A mismatch observation must explicitly preserve its v1 proof as unconsumed."',
            preflight,
        )
        self.assertIn(
            '$recoveryRecord.result -in @("recovered", "already-recovered")',
            preflight,
        )
        self.assertEqual(1, preflight.count("platform_recovered=true"))

        interrupted_exit = between(
            preflight,
            'if ("${{ inputs.interrupted_forward_observe_only }}" -eq "true")',
            '$dryId = "c99-dry-$env:GITHUB_RUN_ID-$env:GITHUB_RUN_ATTEMPT"',
        )
        self.assertIn("exit 0", interrupted_exit)
        diagnostic_position = preflight.index(
            '"interrupted_forward_mismatch_diagnostic_observed"'
        )
        dry_run_position = preflight.index(
            '$dryId = "c99-dry-$env:GITHUB_RUN_ID-$env:GITHUB_RUN_ATTEMPT"'
        )
        self.assertLess(diagnostic_position, dry_run_position)
        self.assertIn("exit 0", preflight[diagnostic_position:dry_run_position])

        output_guard = between(
            preflight,
            "if (-not [string]::IsNullOrWhiteSpace($env:COMPLETE99_INTERRUPTED_FORWARD_PROOF) -and",
            'if ("${{ inputs.orphaned_rollback_observe_only }}" -eq "true")',
        )
        self.assertIn(
            '$recoveryRecord.result -in @("recovered", "already-recovered")',
            output_guard,
        )
        self.assertIn('"platform_recovered=true" >> $env:GITHUB_OUTPUT', output_guard)

    def test_mode_conditions_preserve_recovery_only_commerce(self) -> None:
        workflow = workflow_text()
        production = between(
            workflow,
            "- name: Deploy the exact CI artifact with independent verification",
            "- name: Detect whether the production mutation edge was crossed",
        )
        mutation = between(
            workflow,
            "- name: Detect whether the production mutation edge was crossed",
            "- name: Recover any interrupted mutation with a recreated temporary bridge",
        )
        recovery = between(
            workflow,
            "- name: Recover any interrupted mutation with a recreated temporary bridge",
            "- name: Remove the runner-local mutation marker",
        )
        commerce = between(
            workflow,
            "- name: Install pinned WooCommerce and materialize the live catalog",
            "- name: Recover an interrupted WooCommerce bridge",
        )
        stage_validation = between(
            workflow,
            "- name: Validate exact deployment-stage audit outcomes",
            "- name: Fail closed after an audited commerce failure",
        )

        for block in (production, mutation, recovery):
            self.assertIn("inputs.orphaned_rollback_observe_only != true", block)
            self.assertIn("inputs.interrupted_forward_observe_only != true", block)
            self.assertIn("inputs.recovery_only != true", block)
        self.assertIn("inputs.orphaned_rollback_observe_only != true", commerce)
        self.assertIn("inputs.interrupted_forward_observe_only != true", commerce)
        self.assertNotIn("inputs.recovery_only != true", commerce)

        self.assertIn(
            "COMPLETE99_OBSERVATION_ONLY: "
            "${{ inputs.orphaned_rollback_observe_only || "
            "inputs.interrupted_forward_observe_only }}",
            stage_validation,
        )
        self.assertIn(
            "COMPLETE99_RECOVERY_ONLY: ${{ inputs.recovery_only }}",
            stage_validation,
        )
        self.assertIn(
            "COMPLETE99_PLATFORM_RECOVERED: "
            "${{ steps.preflight.outputs.platform_recovered }}",
            stage_validation,
        )


if __name__ == "__main__":
    unittest.main()
