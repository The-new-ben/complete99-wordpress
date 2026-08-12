from __future__ import annotations

from pathlib import Path
import re
import unittest


ROOT = Path(__file__).resolve().parents[1]
DEPLOY_WORKFLOW = ROOT / ".github" / "workflows" / "wordpress-deploy.yml"
MONITOR_WORKFLOW = ROOT / ".github" / "workflows" / "wordpress-campaign-monitor.yml"


class Complete99DeployCampaignWorkerContracts(unittest.TestCase):
    def test_final_job_gates_normal_deployments_on_the_dedicated_monitor(self) -> None:
        workflow = DEPLOY_WORKFLOW.read_text(encoding="utf-8")
        marker = "\n  verify-campaign-worker:\n"
        self.assertIn(marker, workflow)
        job = workflow.split(marker, 1)[1]

        self.assertIn("always()", job)
        self.assertIn("needs: deploy", job)
        self.assertIn(
            "runs-on: [self-hosted, Windows, X64, complete99-monitor]", job
        )
        self.assertNotIn("complete99-deploy", job)
        self.assertIn("environment: campaign-monitor", job)
        self.assertIn('if ($env:COMPLETE99_DEPLOY_OUTCOME -ne "success")', job)
        for recovery_input in (
            "inputs.orphaned_rollback_observe_only != true",
            "inputs.interrupted_forward_observe_only != true",
            "inputs.recovery_only != true",
        ):
            self.assertIn(recovery_input, job)
        self.assertNotIn("monitor_required", job)

        self.assertIn("final identical-artifact redeploy", job)
        self.assertNotIn("1.21", job)
        self.assertIn("python scripts/monitor-campaign-worker.py", job)
        self.assertIn("persist-credentials: false", job)
        self.assertIn("fetch-depth: 1", job)
        self.assertIn("sparse-checkout: |\n            scripts/monitor-campaign-worker.py", job)
        self.assertIn("sparse-checkout-cone-mode: false", job)
        self.assertNotRegex(job, r"(?m)^\s+filter:")
        revisions = re.findall(r"uses:\s*[^@\s]+@([^\s]+)", job)
        self.assertEqual(1, len(revisions))
        self.assertRegex(revisions[0], r"^[0-9a-f]{40}$")

    def test_final_job_exposes_only_dedicated_worker_secrets(self) -> None:
        workflow = DEPLOY_WORKFLOW.read_text(encoding="utf-8")
        job = workflow.split("\n  verify-campaign-worker:\n", 1)[1]
        self.assertEqual(
            {
                "WP_BASE_URL",
                "WP_CAMPAIGN_WORKER_USER",
                "WP_CAMPAIGN_WORKER_APP_PASSWORD",
                "WP_ALLOWED_MONITOR_HOSTS",
            },
            set(re.findall(r"secrets\.([A-Z0-9_]+)", job)),
        )
        self.assertNotIn("WP_DEPLOY_USER", job)
        self.assertNotRegex(job, r"(?m)^\s+WP_APP_PASSWORD:")
        monitor = MONITOR_WORKFLOW.read_text(encoding="utf-8")
        self.assertIn("python scripts/monitor-campaign-worker.py", monitor)


if __name__ == "__main__":
    unittest.main()
