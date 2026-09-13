from __future__ import annotations

import json
import shutil
import subprocess
from html.parser import HTMLParser
from pathlib import Path

import pytest

ROOT = Path(__file__).resolve().parents[1]
PHP = shutil.which("php")
FIXTURE = ROOT / "tests/fixtures/lead-form-contract.php"


def invoke(*args, payload=None):
    assert PHP, "PHP is required for actual lead renderer/handler contracts"
    return subprocess.run([PHP, str(FIXTURE), *args], input=json.dumps(payload) if payload else None,
                          capture_output=True, text=True, encoding="utf-8", check=True).stdout


class FormFields(HTMLParser):
    def __init__(self, html):
        super().__init__()
        self.fields = {}
        self.feed(html)

    def handle_starttag(self, tag, attributes):
        attrs = dict(attributes)
        if tag in {"input", "textarea", "select"} and "name" in attrs:
            self.fields[attrs["name"]] = attrs


@pytest.mark.parametrize("language", ["he", "en"])
@pytest.mark.parametrize("interest", ["group-order", "group-meals", "workplace-meals"])
def test_actual_group_renderer_makes_only_redundant_fields_optional(language, interest):
    fields = FormFields(invoke("render", language, interest)).fields
    assert "required" not in fields["organisation"]
    assert "required" not in fields["message"]
    for name in ("contact_name", "email", "group_size", "requested_date", "service_window", "fulfilment", "packaging", "consent"):
        assert "required" in fields[name]
    assert fields["organisation"]["maxlength"] == "160"
    assert fields["message"]["maxlength"] == "3000"


def group_payload():
    return dict(complete99_lead_nonce="local-test-only", consent="1", contact_name="Fixture only",
                email="fixture@example.invalid", organisation="", message="", interest="group-order",
                language="he", group_size="20", requested_date="2026-09-20",
                service_window="lunch", fulfilment="pickup", packaging="discuss")


def test_real_handler_accepts_group_without_organisation_or_extra_message():
    assert json.loads(invoke("validate", payload=group_payload()))["result"] == "validation_passed_no_storage"


@pytest.mark.parametrize("field,value", [("contact_name", ""), ("email", "bad"), ("group_size", "0"),
    ("requested_date", "2020-01-01"), ("service_window", "bad"), ("fulfilment", "bad"),
    ("packaging", "bad"), ("consent", "0"), ("complete99_lead_nonce", "invalid")])
def test_required_details_and_existing_protections_remain_enforced(field, value):
    payload = group_payload()
    payload[field] = value
    result = json.loads(invoke("validate", payload=payload))["result"]
    assert result == ("rejected:403" if field == "complete99_lead_nonce" else "rejected:400")


@pytest.mark.parametrize("language", ["he", "en"])
def test_institutional_form_still_requires_organisation_and_message(language):
    fields = FormFields(invoke("render", language, "institutional-service")).fields
    assert "required" in fields["organisation"] and "required" in fields["message"]
    payload = group_payload()
    payload["interest"] = "institutional-service"
    assert json.loads(invoke("validate", payload=payload))["result"] == "rejected:400"
