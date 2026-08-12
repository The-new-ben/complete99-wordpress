from __future__ import annotations

import json
import re
import shutil
import subprocess
from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
PLACEMENT_JS = PLUGIN / "assets" / "js" / "campaign-placement.js"
PLACEMENT_CSS = PLUGIN / "assets" / "css" / "campaign-placement.css"
CAMPAIGNS = PLUGIN / "includes" / "class-complete99-campaigns.php"
LAUNCH_CONTENT = PLUGIN / "data" / "launch-content.php"
CONSUMER_CONTENT = PLUGIN / "data" / "consumer-content.php"

TOKEN = "a" * 64
PLACEMENT_ID = "plc_" + "b" * 48
PUBLIC_DIGEST = "c" * 64
PURPOSE = "campaign_banner_interactions_v1"
CHOICE_SCHEMA = "complete99-campaign-measurement-choice/v2"
CHOICE_KEY = f"complete99_campaign_measurement_choice_v2:{PURPOSE}:{TOKEN}"
EVENT_KEY = f"complete99_campaign_impression_event_v2:{TOKEN}"
GRANTED = json.dumps(
    {"schemaVersion": CHOICE_SCHEMA, "decision": "granted", "purpose": PURPOSE},
    separators=(",", ":"),
)
DENIED = json.dumps(
    {"schemaVersion": CHOICE_SCHEMA, "decision": "denied", "purpose": PURPOSE},
    separators=(",", ":"),
)
VALID_EVENT_ID = "1" * 32


NODE_HARNESS = r"""
const fs = require('fs');
const vm = require('vm');

const source = fs.readFileSync(process.argv[1], 'utf8');
const scenario = JSON.parse(process.argv[2]);
const defaultToken = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
const token = scenario.token === undefined ? defaultToken : scenario.token;
const defaultEndpoint = 'https://complete99.test/wp-json/complete99/v1/campaign-events/' + defaultToken;
const trace = [];
const fetches = [];

class Element {
  constructor(name, attributes, inert) {
    this.name = name;
    this.attributes = Object.assign({}, attributes || {});
    this.listeners = Object.create(null);
    this.hidden = !!inert;
    this.disabled = !!inert;
    this.textContent = '';
  }
  getAttribute(name) {
    return Object.prototype.hasOwnProperty.call(this.attributes, name) ? String(this.attributes[name]) : null;
  }
  setAttribute(name, value) {
    this.attributes[name] = String(value);
  }
  removeAttribute(name) {
    delete this.attributes[name];
  }
  addEventListener(type, listener) {
    if (!this.listeners[type]) this.listeners[type] = [];
    this.listeners[type].push(listener);
  }
  dispatch(type) {
    const event = {type, target: this, currentTarget: this};
    (this.listeners[type] || []).slice().forEach((listener) => listener.call(this, event));
  }
  listenerCount(type) {
    return (this.listeners[type] || []).length;
  }
}

function storage(name, initial, faults) {
  const data = Object.assign({}, initial || {});
  const operations = [];
  let mismatch = false;
  return {
    getItem(key) {
      operations.push({op: 'get', key});
      trace.push(name + ':get:' + key);
      if (faults && faults.get) throw new Error(name + '-get');
      if (mismatch) return 'tampered-after-write';
      return Object.prototype.hasOwnProperty.call(data, key) ? data[key] : null;
    },
    setItem(key, value) {
      operations.push({op: 'set', key, value: String(value)});
      trace.push(name + ':set:' + key);
      if (faults && faults.set) throw new Error(name + '-set');
      if (faults && faults.deniedSet && String(value).includes('"decision":"denied"')) throw new Error(name + '-denied-set');
      data[key] = String(value);
      mismatch = !!(faults && faults.readbackMismatch);
    },
    removeItem(key) {
      operations.push({op: 'remove', key});
      trace.push(name + ':remove:' + key);
      if (faults && faults.remove) throw new Error(name + '-remove');
      if (faults && faults.eventRemove && key.includes('impression_event')) throw new Error(name + '-event-remove');
      delete data[key];
      mismatch = false;
    },
    operations,
    data
  };
}

const statusMessages = {
  'data-c99-status-unavailable': 'לא זמין / Unavailable',
  'data-c99-status-undecided': 'לא נשלח דבר עד לבחירה. / Nothing is sent until you choose.',
  'data-c99-status-granted': 'אושר לבאנר זה. / Allowed for this banner.',
  'data-c99-status-denied': 'המדידה כבויה. / Measurement is off.',
  'data-c99-status-privacy-signal': 'אות הפרטיות משאיר מדידה כבויה. / Privacy signal keeps measurement off.',
  'data-c99-status-error': 'לא ניתן לשמור; המדידה כבויה. / Could not store the choice; measurement is off.'
};

function buildPlacement(index) {
  const attributes = {
    'data-c99-placement-id': scenario.placementId === undefined ? 'plc_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb' : scenario.placementId,
    'data-c99-public-digest': scenario.publicDigest === undefined ? 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc' : scenario.publicDigest,
    'data-c99-campaign-event-endpoint': scenario.endpoint === undefined ? defaultEndpoint : scenario.endpoint,
    'data-c99-campaign-event-token': token,
    'data-c99-consent-basis': scenario.consentBasis === undefined ? 'public_contextual' : scenario.consentBasis,
    'data-c99-measurement-mode': scenario.mode === undefined ? 'explicit_opt_in' : scenario.mode,
    'data-c99-measurement-purpose': scenario.purpose === undefined ? 'campaign_banner_interactions_v1' : scenario.purpose,
    'data-c99-measurement-scope': scenario.scope === undefined ? token : scenario.scope
  };
  Object.keys(attributes).forEach((key) => {
    if (attributes[key] === null) delete attributes[key];
  });

  const box = new Element('box-' + index, attributes, false);
  const inert = !scenario.controlsInitiallyActive;
  const grant = scenario.missingGrant ? null : new Element('grant-' + index, {}, inert);
  const decline = scenario.missingDecline ? null : new Element('decline-' + index, {}, inert);
  const change = scenario.missingChange ? null : new Element('change-' + index, {}, inert);
  const link = new Element('link-' + index, {}, false);
  const conversion = new Element('conversion-' + index, {}, false);
  const statusAttributes = Object.assign({role: 'status', 'aria-live': 'polite'}, statusMessages);
  if (scenario.missingStatusMessage) delete statusAttributes['data-c99-status-' + scenario.missingStatusMessage];
  const status = scenario.missingStatus ? null : new Element('status-' + index, statusAttributes, false);
  if (status) status.textContent = statusMessages['data-c99-status-unavailable'];

  box.querySelector = (selector) => {
    if (selector === '[data-c99-measurement-grant]') return grant;
    if (selector === '[data-c99-measurement-decline]') return decline;
    if (selector === '[data-c99-measurement-change]') return change;
    if (selector === '[data-c99-measurement-status]') return status;
    if (selector === '[data-c99-campaign-click]') return link;
    return null;
  };
  return {box, grant, decline, change, link, conversion, status};
}

const placementCount = scenario.placementCount === undefined ? 1 : scenario.placementCount;
const placements = [];
for (let index = 0; index < placementCount; index += 1) placements.push(buildPlacement(index));
const primary = placements[0] || buildPlacement(0);
const document = {
  querySelectorAll(selector) {
    return selector === '.c99-public-campaign' ? placements.map((placement) => placement.box) : [];
  }
};

const localStorage = storage('local', scenario.localInitial, scenario.localFaults);
const sessionStorage = storage('session', scenario.sessionInitial, scenario.sessionFaults);
let uuidCounter = 0;
let browserCrypto;
if (scenario.cryptoMode === 'none') {
  browserCrypto = undefined;
} else if (scenario.cryptoMode === 'getRandomValues') {
  browserCrypto = {
    getRandomValues(bytes) {
      for (let index = 0; index < bytes.length; index += 1) bytes[index] = index;
      return bytes;
    }
  };
} else {
  browserCrypto = {
    randomUUID() {
      uuidCounter += 1;
      return '00000000-0000-4000-8000-' + String(uuidCounter).padStart(12, '0');
    }
  };
}

const browserWindow = {
  URL,
  Uint8Array,
  location: {href: scenario.pageUrl || 'https://complete99.test/'},
  navigator: {
    globalPrivacyControl: scenario.gpc,
    doNotTrack: scenario.dnt,
    msDoNotTrack: scenario.msDnt
  },
  doNotTrack: scenario.windowDnt,
  crypto: browserCrypto,
  localStorage,
  sessionStorage
};

if (!scenario.noFetch) {
  browserWindow.fetch = (url, options) => {
    fetches.push({url, options});
    trace.push('fetch:' + JSON.parse(options.body).event);
    if (scenario.fetchThrows) throw new Error('fetch-throw');
    if (scenario.fetchRejects) return Promise.reject(new Error('fetch-reject'));
    return Promise.resolve({ok: true});
  };
}

const context = {
  window: browserWindow,
  document,
  URL,
  Uint8Array,
  JSON,
  String,
  Promise
};
vm.runInNewContext(source, context, {filename: process.argv[1]});

for (const action of scenario.actions || []) {
  if (action === 'grant' && primary.grant) primary.grant.dispatch('click');
  if (action === 'decline' && primary.decline) primary.decline.dispatch('click');
  if (action === 'change' && primary.change) primary.change.dispatch('click');
  if (action === 'click') primary.link.dispatch('click');
  if (action === 'conversion') primary.conversion.dispatch('click');
  if (action === 'customConversion') primary.box.dispatch('complete99:campaign-conversion');
  if (action === 'tamperMode') primary.box.setAttribute('data-c99-measurement-mode', 'tampered');
  if (action === 'setGpc') browserWindow.navigator.globalPrivacyControl = true;
}

function controlState(control) {
  if (!control) return null;
  return {hidden: control.hidden, disabled: control.disabled, clicks: control.listenerCount('click')};
}

process.stdout.write(JSON.stringify({
  state: primary.box.getAttribute('data-c99-measurement-state'),
  statusText: primary.status ? primary.status.textContent : null,
  fetches,
  trace,
  localOperations: localStorage.operations,
  sessionOperations: sessionStorage.operations,
  localData: localStorage.data,
  sessionData: sessionStorage.data,
  grant: controlState(primary.grant),
  decline: controlState(primary.decline),
  change: controlState(primary.change),
  linkListeners: primary.link.listenerCount('click'),
  conversionListeners: primary.conversion.listenerCount('click'),
  customConversionListeners: primary.box.listenerCount('complete99:campaign-conversion')
}));
"""


class Complete99CampaignMeasurementConsentContracts(unittest.TestCase):
    def test_source_is_scoped_explicit_cookie_free_and_has_no_conversion_surface(self) -> None:
        source = PLACEMENT_JS.read_text(encoding="utf-8")
        for token in (
            CHOICE_SCHEMA,
            PURPOSE,
            "data-c99-measurement-purpose",
            "data-c99-measurement-scope",
            "data-c99-measurement-change",
            "data-c99-measurement-status",
            "globalPrivacyControl === true",
            "credentials: 'omit'",
            "mode: 'same-origin'",
            "referrerPolicy: 'no-referrer'",
            "anonymous_unverified",
        ):
            self.assertIn(token, source)
        for forbidden in (
            "document.cookie",
            "navigator.userAgent",
            "navigator.appVersion",
            "localStorage.getItem",
            "localStorage.setItem",
            "Math.random",
            "Date.now",
            "geolocation",
            "conversion",
            "complete99:campaign-conversion",
            ".padStart(",
        ):
            self.assertNotIn(forbidden, source)

    def test_server_markup_is_inert_without_js_and_exposes_accessible_localized_status(self) -> None:
        php = CAMPAIGNS.read_text(encoding="utf-8")
        for selector in (
            "data-c99-measurement-grant",
            "data-c99-measurement-decline",
            "data-c99-measurement-change",
        ):
            self.assertRegex(
                php,
                rf"{selector}\s+hidden\s+disabled\s+aria-disabled=\"true\"\s+tabindex=\"-1\"",
            )
        self.assertRegex(
            php,
            r'data-c99-measurement-status\s+role="status"\s+aria-live="polite"',
        )
        for attribute in (
            "data-c99-status-unavailable",
            "data-c99-status-undecided",
            "data-c99-status-granted",
            "data-c99-status-denied",
            "data-c99-status-privacy-signal",
            "data-c99-status-error",
        ):
            self.assertIn(attribute, php)
        self.assertIn('data-c99-measurement-purpose="<?php echo esc_attr( self::WEBSITE_MEASUREMENT_PURPOSE ); ?>"', php)
        self.assertIn('data-c99-measurement-scope="<?php echo esc_attr( $row[\'readback_token\'] ); ?>"', php)

    def test_css_keeps_initial_choices_symmetric_and_has_keyboard_focus_fallback(self) -> None:
        css = PLACEMENT_CSS.read_text(encoding="utf-8")
        self.assertIn("min-height: 44px", css)
        self.assertIn("[data-c99-measurement-grant],", css)
        self.assertIn("[data-c99-measurement-decline]", css)
        self.assertIn("a:focus,", css)
        self.assertIn("button:focus", css)
        self.assertIn("[hidden]", css)
        self.assertNotIn(":is(", css)
        grant_rule = re.search(
            r"\[data-c99-measurement-grant\],\s*.*?\[data-c99-measurement-decline\]\s*\{(?P<body>.*?)\}",
            css,
            re.S,
        )
        self.assertIsNotNone(grant_rule)
        self.assertIn("background: #fff", grant_rule.group("body"))

    def test_both_privacy_sources_disclose_the_live_campaign_measurement_boundary(self) -> None:
        for path in (LAUNCH_CONTENT, CONSUMER_CONTENT):
            with self.subTest(path=path.name):
                content = path.read_text(encoding="utf-8")
                for token in (
                    "Optional campaign measurement",
                    "explicit choice for the placement rendered on the current page",
                    "only an impression event and a click on the call to action",
                    "up to five hours",
                    "90 days",
                    "Campaign storage uses no cookies",
                    "no IP address, User-Agent string, contact data or payment data",
                    "Global Privacy Control or Do Not Track disables",
                    "change the choice or withdraw permission",
                    "Separate hosting and security logs",
                    "מדידת קמפיין אופציונלית",
                ):
                    self.assertIn(token, content)
                self.assertNotIn("If optional measurement, advertising or non-essential cookies are added later", content)
                self.assertNotIn("אם יתווספו בעתיד כלי מדידה", content)

    @unittest.skipUnless(shutil.which("node"), "Node.js is required")
    def test_undecided_user_has_no_optional_write_network_or_click_listener(self) -> None:
        result = self.run_browser(
            {"actions": ["click", "conversion", "customConversion"]}
        )
        self.assertEqual("undecided", result["state"])
        self.assertEqual([], result["fetches"])
        self.assertEqual([], result["localOperations"])
        self.assertEqual([], self.operations(result, "set"))
        self.assertEqual([], self.operations(result, "remove"))
        self.assertEqual(0, result["linkListeners"])
        self.assertEqual(0, result["conversionListeners"])
        self.assertEqual(0, result["customConversionListeners"])
        self.assertFalse(result["grant"]["hidden"])
        self.assertFalse(result["decline"]["hidden"])
        self.assertTrue(result["change"]["hidden"])
        self.assertIn("Nothing is sent until you choose", result["statusText"])

    @unittest.skipUnless(shutil.which("node"), "Node.js is required")
    def test_explicit_page_grant_stores_scoped_state_then_sends_impression_and_click_only(self) -> None:
        result = self.run_browser(
            {"actions": ["click", "conversion", "grant", "click", "conversion", "customConversion"]}
        )
        self.assertEqual(["impression", "click"], self.events(result))
        self.assertEqual(GRANTED, result["sessionData"][CHOICE_KEY])
        self.assertRegex(result["sessionData"][EVENT_KEY], r"^[A-Za-z0-9_-]{20,80}$")
        self.assertEqual([], result["localOperations"])
        self.assertLess(
            result["trace"].index(f"session:set:{CHOICE_KEY}"),
            result["trace"].index(f"session:set:{EVENT_KEY}"),
        )
        self.assertLess(
            result["trace"].index(f"session:set:{EVENT_KEY}"),
            result["trace"].index("fetch:impression"),
        )
        self.assertTrue(result["grant"]["hidden"])
        self.assertFalse(result["change"]["hidden"])

        for request in result["fetches"]:
            self.assertEqual(
                f"https://complete99.test/wp-json/complete99/v1/campaign-events/{TOKEN}",
                request["url"],
            )
            options = request["options"]
            self.assertEqual("POST", options["method"])
            self.assertEqual("same-origin", options["mode"])
            self.assertEqual("omit", options["credentials"])
            self.assertEqual("no-referrer", options["referrerPolicy"])
            self.assertTrue(options["keepalive"])
            payload = json.loads(options["body"])
            self.assertEqual({"event", "event_id"}, set(payload))
            self.assertIn(payload["event"], {"impression", "click"})
            self.assertRegex(payload["event_id"], r"^[A-Za-z0-9_-]{20,80}$")

    @unittest.skipUnless(shutil.which("node"), "Node.js is required")
    def test_decline_stores_only_scoped_denial_and_never_measures(self) -> None:
        result = self.run_browser(
            {"actions": ["decline", "click", "conversion", "customConversion"]}
        )
        self.assertEqual("denied", result["state"])
        self.assertEqual([], result["fetches"])
        self.assertEqual({CHOICE_KEY: DENIED}, result["sessionData"])
        self.assertEqual([], result["localOperations"])
        self.assertFalse(result["change"]["hidden"])
        self.assertIn("Measurement is off", result["statusText"])

    @unittest.skipUnless(shutil.which("node"), "Node.js is required")
    def test_stale_grant_is_cleared_and_never_authorizes_a_reload(self) -> None:
        result = self.run_browser(
            {
                "sessionInitial": {CHOICE_KEY: GRANTED, EVENT_KEY: VALID_EVENT_ID},
                "localInitial": {"complete99_campaign_measurement_consent_v1": "granted"},
                "actions": ["click", "conversion", "customConversion"],
            }
        )
        self.assertEqual("undecided", result["state"])
        self.assertEqual([], result["fetches"])
        self.assertEqual({}, result["sessionData"])
        self.assertEqual(0, result["linkListeners"])
        self.assertEqual([], result["localOperations"])

    @unittest.skipUnless(shutil.which("node"), "Node.js is required")
    def test_stored_denial_is_safe_and_change_choice_requires_a_new_explicit_grant(self) -> None:
        denied = self.run_browser(
            {"sessionInitial": {CHOICE_KEY: DENIED}, "actions": ["click"]}
        )
        self.assertEqual("denied", denied["state"])
        self.assertEqual([], denied["fetches"])
        self.assertFalse(denied["change"]["hidden"])

        changed = self.run_browser(
            {"sessionInitial": {CHOICE_KEY: DENIED}, "actions": ["change", "click"]}
        )
        self.assertEqual("undecided", changed["state"])
        self.assertEqual([], changed["fetches"])
        self.assertFalse(changed["grant"]["hidden"])
        self.assertFalse(changed["decline"]["hidden"])

    @unittest.skipUnless(shutil.which("node"), "Node.js is required")
    def test_gpc_and_dnt_clear_scoped_state_and_remove_every_grant_path(self) -> None:
        cases = (
            {"gpc": True},
            {"dnt": "1"},
            {"dnt": " yes "},
            {"dnt": "YES"},
            {"msDnt": "1"},
            {"windowDnt": "yes"},
        )
        for signals in cases:
            with self.subTest(signals=signals):
                scenario = dict(signals)
                scenario.update(
                    {
                        "sessionInitial": {CHOICE_KEY: GRANTED, EVENT_KEY: VALID_EVENT_ID},
                        "actions": ["grant", "change", "click", "customConversion"],
                    }
                )
                result = self.run_browser(scenario)
                self.assertEqual("privacy-signal", result["state"])
                self.assertEqual([], result["fetches"])
                self.assertEqual({}, result["sessionData"])
                self.assertTrue(result["grant"]["hidden"])
                self.assertTrue(result["change"]["hidden"])
                self.assertEqual(0, result["grant"]["clicks"])
                self.assertIn("Privacy signal", result["statusText"])

    @unittest.skipUnless(shutil.which("node"), "Node.js is required")
    def test_missing_tampered_or_non_inert_contract_fails_before_storage(self) -> None:
        cases = (
            {"mode": None},
            {"mode": "public_contextual"},
            {"consentBasis": None},
            {"consentBasis": "legitimate_interest"},
            {"purpose": None},
            {"purpose": "campaign_banner_interactions_v2"},
            {"scope": "b" * 64},
            {"token": "a" * 63},
            {"placementId": "placement_bad"},
            {"publicDigest": "c" * 63},
            {"endpoint": f"https://evil.test/wp-json/complete99/v1/campaign-events/{TOKEN}"},
            {"endpoint": f"https://complete99.test/unexpected-prefix/wp-json/complete99/v1/campaign-events/{TOKEN}"},
            {"endpoint": f"https://complete99.test/wp-json/complete99/v1/campaign-events/{TOKEN}/"},
            {"endpoint": f"https://complete99.test/wp-json/complete99/v1/campaign-events/{TOKEN}?x=1"},
            {"missingGrant": True},
            {"missingDecline": True},
            {"missingChange": True},
            {"missingStatus": True},
            {"missingStatusMessage": "error"},
            {"controlsInitiallyActive": True},
            {"placementCount": 2},
            {"noFetch": True},
        )
        for scenario in cases:
            with self.subTest(scenario=scenario):
                scenario = dict(scenario)
                scenario["actions"] = ["grant", "decline", "change", "click"]
                result = self.run_browser(scenario)
                self.assertEqual([], result["fetches"])
                self.assertEqual([], result["sessionOperations"])
                self.assertEqual([], result["localOperations"])
                self.assertEqual("disabled", result["state"])

    @unittest.skipUnless(shutil.which("node"), "Node.js is required")
    def test_storage_and_crypto_failures_never_fall_back_to_measurement(self) -> None:
        cases = (
            {"sessionFaults": {"get": True}},
            {"sessionFaults": {"set": True}, "actions": ["grant"]},
            {"sessionFaults": {"readbackMismatch": True}, "actions": ["grant"]},
            {"cryptoMode": "none", "actions": ["grant"]},
            {
                "sessionInitial": {CHOICE_KEY: GRANTED, EVENT_KEY: "tampered"},
                "sessionFaults": {"remove": True},
            },
        )
        for scenario in cases:
            with self.subTest(scenario=scenario):
                result = self.run_browser(scenario)
                self.assertEqual([], result["fetches"])
                self.assertIn(result["state"], {"disabled", "error"})

    @unittest.skipUnless(shutil.which("node"), "Node.js is required")
    def test_get_random_values_fallback_uses_manual_bounded_hex(self) -> None:
        result = self.run_browser(
            {"cryptoMode": "getRandomValues", "actions": ["grant", "click"]}
        )
        self.assertEqual(["impression", "click"], self.events(result))
        self.assertEqual("000102030405060708090a0b0c0d0e0f", result["sessionData"][EVENT_KEY])
        for request in result["fetches"]:
            self.assertRegex(json.loads(request["options"]["body"])["event_id"], r"^[a-f0-9]{32}$")

    @unittest.skipUnless(shutil.which("node"), "Node.js is required")
    def test_contract_tampering_after_grant_blocks_click_and_clears_session(self) -> None:
        result = self.run_browser(
            {"actions": ["grant", "tamperMode", "click", "customConversion"]}
        )
        self.assertEqual(["impression"], self.events(result))
        self.assertEqual("disabled", result["state"])
        self.assertEqual({}, result["sessionData"])

    @unittest.skipUnless(shutil.which("node"), "Node.js is required")
    def test_change_after_grant_revokes_before_any_later_click(self) -> None:
        result = self.run_browser(
            {"actions": ["grant", "change", "click", "customConversion"]}
        )
        self.assertEqual(["impression"], self.events(result))
        self.assertEqual("undecided", result["state"])
        self.assertEqual({CHOICE_KEY: DENIED}, result["sessionData"])
        self.assertTrue(result["change"]["hidden"])

    @unittest.skipUnless(shutil.which("node"), "Node.js is required")
    def test_denial_storage_failure_cannot_resume_stale_grant_on_reload(self) -> None:
        failed_denial = self.run_browser(
            {"sessionFaults": {"deniedSet": True}, "actions": ["grant", "change", "click"]}
        )
        self.assertEqual(["impression"], self.events(failed_denial))
        self.assertEqual("error", failed_denial["state"])
        self.assertEqual(GRANTED, failed_denial["sessionData"].get(CHOICE_KEY))
        self.assertNotIn(EVENT_KEY, failed_denial["sessionData"])

        reloaded = self.run_browser({"sessionInitial": failed_denial["sessionData"], "actions": ["click"]})
        self.assertEqual("undecided", reloaded["state"])
        self.assertEqual([], reloaded["fetches"])
        self.assertEqual({}, reloaded["sessionData"])
        self.assertEqual(0, reloaded["linkListeners"])

        double_failure = self.run_browser(
            {
                "sessionFaults": {"deniedSet": True, "eventRemove": True},
                "actions": ["grant", "change", "click"],
            }
        )
        self.assertEqual(["impression"], self.events(double_failure))
        retry_reload = self.run_browser({"sessionInitial": double_failure["sessionData"]})
        self.assertEqual("undecided", retry_reload["state"])
        self.assertEqual([], retry_reload["fetches"])
        self.assertEqual({}, retry_reload["sessionData"])

    @unittest.skipUnless(shutil.which("node"), "Node.js is required")
    def test_change_choice_revokes_grant_and_requires_another_explicit_grant(self) -> None:
        result = self.run_browser(
            {"actions": ["grant", "change", "click", "conversion", "customConversion"]}
        )
        self.assertEqual(["impression"], self.events(result))
        self.assertEqual("undecided", result["state"])
        self.assertFalse(result["grant"]["hidden"])
        self.assertFalse(result["decline"]["hidden"])
        self.assertTrue(result["change"]["hidden"])

    @unittest.skipUnless(shutil.which("node"), "Node.js is required")
    def test_new_privacy_signal_after_grant_revokes_and_clears_without_click(self) -> None:
        result = self.run_browser({"actions": ["grant", "setGpc", "click"]})
        self.assertEqual(["impression"], self.events(result))
        self.assertEqual("privacy-signal", result["state"])
        self.assertEqual({}, result["sessionData"])
        self.assertTrue(result["grant"]["hidden"])
        self.assertTrue(result["change"]["hidden"])

    @unittest.skipUnless(shutil.which("node"), "Node.js is required")
    def test_fetch_failure_is_swallowed_without_blocking_choice_or_navigation(self) -> None:
        for failure in ({"fetchThrows": True}, {"fetchRejects": True}):
            with self.subTest(failure=failure):
                scenario = dict(failure)
                scenario["actions"] = ["grant", "click"]
                result = self.run_browser(scenario)
                self.assertEqual("granted", result["state"])
                self.assertEqual(["impression", "click"], self.events(result))

    @staticmethod
    def events(result: dict) -> list[str]:
        return [json.loads(request["options"]["body"])["event"] for request in result["fetches"]]

    @staticmethod
    def operations(result: dict, operation: str) -> list[dict]:
        return [entry for entry in result["sessionOperations"] if entry["op"] == operation]

    @staticmethod
    def run_browser(scenario: dict) -> dict:
        completed = subprocess.run(
            ["node", "-e", NODE_HARNESS, str(PLACEMENT_JS), json.dumps(scenario)],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=20,
            check=False,
        )
        if completed.returncode != 0:
            raise AssertionError(completed.stdout + completed.stderr)
        return json.loads(completed.stdout)


if __name__ == "__main__":
    unittest.main()
