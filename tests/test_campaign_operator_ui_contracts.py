"""Executable browser-contract tests for the private Campaign operator UI."""

from __future__ import annotations

import subprocess
import tempfile
import textwrap
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SCRIPT = ROOT / "plugin/complete99-platform/assets/js/campaigns.js"


class Complete99CampaignOperatorUiContracts(unittest.TestCase):
    maxDiff = None

    @classmethod
    def setUpClass(cls) -> None:
        cls.javascript = SCRIPT.read_text(encoding="utf-8")

    def test_javascript_is_parse_clean(self) -> None:
        completed = subprocess.run(
            ["node", "--check", str(SCRIPT)],
            cwd=ROOT,
            capture_output=True,
            text=True,
            encoding="utf-8",
            timeout=30,
        )
        self.assertEqual(0, completed.returncode, completed.stderr)

    def test_static_fail_closed_authority_and_rendering_contract(self) -> None:
        js = self.javascript
        for selector in (
            "data-c99-evidence-inventory",
            "data-c99-evidence-select",
            "data-c99-evidence-reload",
            "data-c99-evidence-access",
            "data-c99-evidence-hold-set",
            "data-c99-evidence-hold-release",
            "data-c99-evidence-dispose",
            "data-c99-evidence-status",
        ):
            self.assertIn(selector, js)
        for token in (
            "data.items.length > 100",
            "data.schemaVersion !== 1",
            "eligibleToDispose === true",
            "canDispose === true",
            "canSetLegalHold === true",
            "canReleaseLegalHold === true",
            "typeof legalHold !== 'boolean'",
            "'/legal-hold'",
            "Idempotency-Key",
            "error.storedOrPending",
            "sameOriginPrivateUrl",
            "exactHttpsUrl",
            "noopener noreferrer",
            "campaignLink",
            "Complete action history",
            "Final outcome provenance",
            "complete99-campaign-moderation-outcome/v1",
            "human_attested_operator_record",
            "moderationReasonMaximum = 500",
            "moderationOutcomeMaximum = 1000",
            "privateEvidenceMaximumBytes = 8388608",
            "summary.attachmentDigest",
            "privateEvidenceMimeTypes.indexOf(mimeType)",
            "'upload_abandoned'",
            "eligibilityReasons[0] !== 'upload_abandoned'",
            "terminal read-only",
            "No evidence bytes were retained",
            "new Idempotency-Key",
            "if (action === 'outcome')",
            "textContent",
        ):
            self.assertIn(token, js)
        self.assertNotIn("summary.sha256 || summary.attachmentDigest", js)
        self.assertNotIn("finalOutcomeProvenance", js)
        self.assertNotIn("outcomeProvenance", js)
        self.assertNotIn("action === 'resolve' || action === 'outcome'", js)
        self.assertNotIn("innerHTML", js)
        self.assertNotIn("insertAdjacentHTML", js)
        self.assertNotIn("Date.parse", js)
        self.assertNotIn("eval(", js)

    def test_executable_inventory_proof_redaction_and_moderation_lifecycle(self) -> None:
        harness = textwrap.dedent(
            r"""
            'use strict';
            const assert = require('assert');
            const fs = require('fs');
            const vm = require('vm');
            const source = fs.readFileSync(process.argv[2], 'utf8');

            function dataKey(attribute) {
                return attribute.slice(5).replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
            }

            class Element {
                constructor(tagName) {
                    this.tagName = String(tagName || 'div').toUpperCase();
                    this.children = [];
                    this.parentNode = null;
                    this.dataset = Object.create(null);
                    this.attributes = Object.create(null);
                    this.listeners = Object.create(null);
                    this._text = '';
                    this.hidden = false;
                    this.disabled = false;
                    this.value = '';
                    this.files = [];
                    this.className = '';
                    this.type = '';
                    this.href = '';
                    this.target = '';
                    this.rel = '';
                    this.referrerPolicy = '';
                    this.download = '';
                    this.clickCount = 0;
                }
                set textContent(value) {
                    this._text = value === null || value === undefined ? '' : String(value);
                    this.children.forEach((child) => { child.parentNode = null; });
                    this.children = [];
                }
                get textContent() {
                    return this._text + this.children.map((child) => child.textContent).join('');
                }
                appendChild(child) {
                    if (!(child instanceof Element)) throw new Error('Only elements are supported in this contract DOM.');
                    child.parentNode = this;
                    this.children.push(child);
                    return child;
                }
                append(...children) { children.forEach((child) => this.appendChild(child)); }
                remove() {
                    if (!this.parentNode) return;
                    this.parentNode.children = this.parentNode.children.filter((child) => child !== this);
                    this.parentNode = null;
                }
                setAttribute(name, value) {
                    this.attributes[name] = String(value);
                    if (name.startsWith('data-')) this.dataset[dataKey(name)] = String(value);
                }
                removeAttribute(name) {
                    delete this.attributes[name];
                    if (name.startsWith('data-')) delete this.dataset[dataKey(name)];
                    if (name === 'href') this.href = '';
                    if (name === 'target') this.target = '';
                    if (name === 'rel') this.rel = '';
                }
                toggleAttribute(name, force) {
                    if (force) this.attributes[name] = '';
                    else delete this.attributes[name];
                }
                addEventListener(type, listener) {
                    (this.listeners[type] || (this.listeners[type] = [])).push(listener);
                }
                matches(selector) {
                    const match = /^\[data-([a-z0-9-]+)\]$/.exec(selector);
                    return !!match && Object.prototype.hasOwnProperty.call(this.dataset, dataKey('data-' + match[1]));
                }
                closest(selector) {
                    let node = this;
                    while (node) {
                        if (node.matches(selector)) return node;
                        node = node.parentNode;
                    }
                    return null;
                }
                async fire(type) {
                    const event = {
                        type,
                        target: this,
                        currentTarget: this,
                        defaultPrevented: false,
                        preventDefault() { this.defaultPrevented = true; },
                    };
                    let node = this;
                    while (node) {
                        event.currentTarget = node;
                        for (const listener of node.listeners[type] || []) listener.call(node, event);
                        node = node.parentNode;
                    }
                    return event;
                }
                click() { this.clickCount += 1; return this.fire('click'); }
            }

            function descendants(node) {
                const found = [];
                for (const child of node.children) {
                    found.push(child, ...descendants(child));
                }
                return found;
            }
            function findAll(node, predicate) { return descendants(node).filter(predicate); }
            function findButton(node, key, value) {
                return findAll(node, (item) => item.tagName === 'BUTTON' && item.dataset[key] === value)[0] || null;
            }
            async function settle(turns = 45) {
                for (let index = 0; index < turns; index += 1) {
                    await new Promise((resolve) => setImmediate(resolve));
                }
            }
            function clone(value) { return JSON.parse(JSON.stringify(value)); }

            const root = new Element('section');
            const direct = Object.create(null);
            function add(selector, tagName = 'div') {
                const node = new Element(tagName);
                direct[selector] = node;
                root.appendChild(node);
                return node;
            }

            const status = add('[data-c99-campaign-status]', 'p');
            const list = add('[data-c99-campaign-list]', 'div');
            const searchForm = add('[data-c99-campaign-search-form]', 'form');
            const searchInput = add('[data-c99-campaign-search]', 'input');
            const previousPage = add('[data-c99-campaign-prev]', 'button');
            const nextPage = add('[data-c99-campaign-next]', 'button');
            const pageStatus = add('[data-c99-campaign-page]', 'span');
            const form = add('[data-c99-campaign-form]', 'form');
            for (const field of ['campaignId', 'name', 'locationId', 'primaryChannel', 'campaignJson']) {
                form[field] = new Element(field === 'campaignJson' ? 'textarea' : 'input');
                form.appendChild(form[field]);
            }
            const actionStatus = add('[data-c99-campaign-actions]', 'div');
            const packagePreview = add('[data-c99-package-preview]', 'pre');
            const packageCopy = add('[data-c99-package-copy]', 'button');
            const packageDownload = add('[data-c99-package-download]', 'button');
            const evidenceFile = add('[data-c99-evidence-file]', 'input');
            const evidenceUpload = add('[data-c99-evidence-upload]', 'button');
            const evidenceInventory = add('[data-c99-evidence-inventory]', 'section');
            const evidenceSelect = add('[data-c99-evidence-select]', 'select');
            const evidenceReload = add('[data-c99-evidence-reload]', 'button');
            const evidenceAccess = add('[data-c99-evidence-access]', 'a');
            const evidenceDispose = add('[data-c99-evidence-dispose]', 'button');
            const evidenceHoldSet = add('[data-c99-evidence-hold-set]', 'button');
            const evidenceHoldRelease = add('[data-c99-evidence-hold-release]', 'button');
            const evidenceStatus = add('[data-c99-evidence-status]', 'p');
            const receiptsPanel = add('[data-c99-provider-receipts]', 'section');
            const resultsPanel = add('[data-c99-results]', 'section');
            const signalsPanel = add('[data-c99-signals]', 'section');
            const moderationPanel = add('[data-c99-moderation]', 'section');

            const actionButtons = Object.create(null);
            for (const action of ['approve', 'prepare-package', 'schedule', 'cancel', 'provider-receipts', 'results', 'moderation-issues']) {
                const button = new Element('button');
                button.dataset.c99Action = action;
                actionButtons[action] = button;
                root.appendChild(button);
            }
            root.querySelector = (selector) => direct[selector] || null;
            root.querySelectorAll = (selector) => {
                if (selector === '[data-c99-action]') return Object.values(actionButtons);
                if (selector === 'button,input,select,textarea') {
                    return descendants(root).filter((node) => ['BUTTON', 'INPUT', 'SELECT', 'TEXTAREA'].includes(node.tagName));
                }
                return [];
            };

            const document = {
                body: new Element('body'),
                querySelector(selector) { return selector === '[data-c99-campaign-studio]' ? root : null; },
                createElement(tagName) { return new Element(tagName); },
            };

            const endpoint = '/wp-json/complete99/v1/campaigns';
            const origin = 'https://admin.example.test';
            const digestA = 'a'.repeat(64);
            const digestB = 'b'.repeat(64);
            const digestC = 'c'.repeat(64);
            const campaigns = {
                campaign_a1: {
                    campaignId: 'campaign_a1', locationId: 17, name: 'Campaign A', primaryChannel: 'social',
                    governance: {successMetric: 'qualified_clicks', moderationOwnerUserId: 31, escalationOwnerUserId: 41},
                    runtime: {version: 7, lifecycleState: 'approved', externalState: 'none', jobState: 'idle'},
                },
                campaign_b2: {
                    campaignId: 'campaign_b2', locationId: 18, name: 'Campaign B', primaryChannel: 'website',
                    governance: {successMetric: 'clicks', moderationOwnerUserId: 32, escalationOwnerUserId: 42},
                    runtime: {version: 4, lifecycleState: 'approved', externalState: 'none', jobState: 'idle'},
                },
            };
            let issue = null;
            let detailBDeferred = false;
            let releaseDetailB = null;
            let overflowNext = false;
			let abandonedContradictionNext = false;
            let releaseHoldStoredError = false;
            let disposeStoredError = false;
            let nextAttachmentId = 12;
            let evidenceA = [
                {
                    attachmentId: 10, sha256: digestA, sizeBytes: 1200, mimeType: 'application/pdf',
                    originalOwnerUserId: 71, retentionState: 'retained', retentionUntil: '2020-01-01T00:00:00Z',
                    legalHold: false, referenceCounts: {providerReceipts: 0, results: 0, moderationIssues: 0, total: 0},
                    eligibleToDispose: false, accessUrl: '', canDispose: false,
                    canSetLegalHold: true, canReleaseLegalHold: false, custodyMode: 'owner',
                },
                {
                    attachmentId: 11, sha256: digestB, sizeBytes: 2400, mimeType: 'image/png',
                    originalOwnerUserId: 72, retentionState: 'retained', retentionUntil: '2027-12-31T00:00:00Z',
                    legalHold: false, referenceCounts: {providerReceipts: 0, results: 0, moderationIssues: 0, total: 0},
                    eligibleToDispose: true,
                    accessUrl: origin + '/wp-json/complete99/v1/campaign-evidence/11?token=private',
                    canDispose: true, canSetLegalHold: true, canReleaseLegalHold: false, custodyMode: 'custodian',
                },
            ];
            const calls = [];
            const evidenceGets = {campaign_a1: 0, campaign_b2: 0};

            function campaignList() {
                return {
                    campaigns: Object.values(campaigns).map((campaign) => ({
                        campaignId: campaign.campaignId,
                        name: campaign.name,
                        primaryChannel: campaign.primaryChannel,
                        lifecycleState: campaign.runtime.lifecycleState,
                        version: campaign.runtime.version,
                    })),
                    pagination: {page: 1, total: 2, totalPages: 1, hasPrevious: false, hasNext: false},
                };
            }
            function receiptRows(id) {
                if (id === 'campaign_b2') {
                    return [{
                        receiptId: 'receipt_secret', campaignId: id, status: 'stored', proofLevel: 'human_attested',
                        externalId: 'SECRET-EXTERNAL', providerKey: 'SECRET-PROVIDER', providerAccountRef: 'SECRET-ACCOUNT',
                        materialDigest: digestA, payloadDigest: digestB, occurredAt: '2026-08-11T09:00:00Z',
                        evidenceSummary: {kind: 'private_attachment', attachmentDigest: digestC, attachmentId: 999, mimeType: 'application/x-forbidden', sizeBytes: 8388609, accessUrl: 'https://attacker.example/private/999'},
                    }];
                }
                return [{
                    receiptId: 'receipt_1', campaignId: id, channel: 'social', status: 'stored', proofLevel: 'human_attested',
                    externalState: 'unverified', campaignVersion: 7, providerKey: 'provider-safe', providerAccountRef: 'binding-44',
                    externalId: '<img src=x onerror=alert(1)>', packageId: 'pkg_44', materialDigest: digestA,
                    payloadDigest: digestB, occurredAt: '2026-08-11T09:00:00Z',
                    evidenceSummary: {kind: 'private_attachment', attachmentDigest: digestB, sha256: digestC, attachmentId: 11, mimeType: 'image/png', sizeBytes: 2400, retentionUntil: '2027-12-31T00:00:00Z', accessUrl: origin + '/private/evidence/11'},
                }];
            }
            function resultRows(id) {
                if (id === 'campaign_b2') {
                    return [{
                        resultId: 'result_secret', campaignId: id, status: 'observed', metricKey: 'clicks', value: 1,
                        evidenceLevel: 'human_attested', externalId: 'SECRET-RESULT', materialDigest: digestA,
                        payloadDigest: digestB, observedAt: '2026-08-11T10:00:00Z',
                        evidenceSummary: {kind: 'https', host: 'secret.example', reference: 'https://secret.example/proof'},
                    }];
                }
                return [
                    {
                        resultId: 'result_1', campaignId: id, status: 'observed', metricKey: 'qualified_clicks', value: 4,
                        unit: 'count', observationType: 'observed', evidenceLevel: 'human_attested', campaignVersion: 7,
                        externalId: 'external-result', packageId: 'pkg_44', providerReceiptId: 'receipt_1',
                        materialDigest: digestA, payloadDigest: digestC, observedAt: '2026-08-11T10:00:00Z',
                        evidenceSummary: {kind: 'https', host: 'proof.partner.example', reference: 'https://proof.partner.example/item/1'},
                    },
                    {
                        resultId: 'result_unsafe', campaignId: id, status: 'observed', metricKey: 'qualified_clicks', value: 1,
                        unit: 'count', observationType: 'observed', evidenceLevel: 'human_attested', observedAt: '2026-08-11T10:01:00Z',
                        evidenceSummary: {kind: 'https', host: 'attacker.invalid', reference: 'javascript:alert(1)'},
                    },
                ];
            }
            function detail(id) {
                return {
                    campaign: clone(campaigns[id]),
                    packages: [],
                    providerReceipts: receiptRows(id),
                    results: resultRows(id),
                    unverifiedWebSignals: {aggregates: []},
                    moderationIssues: id === 'campaign_a1' && issue ? [clone(issue)] : [],
                };
            }
            function inventory(id) {
				if (id === 'campaign_b2') {
					const abandoned = {
						attachmentId: 20, sha256: digestA, sizeBytes: 1200, mimeType: 'application/pdf',
						originalOwnerUserId: 74, retentionState: 'upload_abandoned', retentionUntil: '2027-12-31T00:00:00Z',
						legalHold: false, referenceCounts: {providerReceipts: 0, results: 0, moderationIssues: 0, total: 0},
						eligibleToDispose: false, eligibilityReasons: ['upload_abandoned'], accessUrl: '', canDispose: false,
						canSetLegalHold: false, canReleaseLegalHold: false, custodyMode: 'owner',
					};
					if (abandonedContradictionNext) {
						abandonedContradictionNext = false;
						abandoned.canSetLegalHold = true;
						abandoned.accessUrl = origin + '/private/abandoned-20';
					}
					return {schemaVersion: 1, campaignId: id, locationId: 18, custodyMode: 'owner', items: [abandoned]};
				}
                if (overflowNext) {
                    overflowNext = false;
                    const prototype = evidenceA[0];
                    return {
                        schemaVersion: 1, campaignId: id, locationId: 17, custodyMode: 'custodian',
                        items: Array.from({length: 101}, (_, index) => ({
                            ...prototype,
                            attachmentId: 1000 + index,
                            sha256: index.toString(16).padStart(64, '0'),
                            canSetLegalHold: false,
                        })),
                    };
                }
                return {schemaVersion: 1, campaignId: id, locationId: 17, custodyMode: 'custodian', items: clone(evidenceA)};
            }
            function response(data, statusCode = 200) {
                return {ok: statusCode >= 200 && statusCode < 300, status: statusCode, json: () => Promise.resolve(clone(data))};
            }
            function parseBody(options) {
                return typeof options.body === 'string' ? JSON.parse(options.body) : null;
            }
            async function fetchStub(rawUrl, rawOptions) {
                const options = rawOptions || {};
                const method = options.method || 'GET';
                const parsed = new URL(rawUrl, origin);
                const path = parsed.pathname;
                calls.push({path, method, options});
                assert.strictEqual(options.credentials, 'same-origin');
                assert.strictEqual(options.headers['X-WP-Nonce'], 'nonce-1');

                if (path === endpoint && method === 'GET') return response(campaignList());
                for (const id of Object.keys(campaigns)) {
                    const base = endpoint + '/' + id;
                    if (path === base && method === 'GET') {
                        if (id === 'campaign_b2' && detailBDeferred) {
                            return new Promise((resolve) => { releaseDetailB = () => resolve(response(detail(id))); });
                        }
                        return response(detail(id));
                    }
                    if (path === base + '/evidence' && method === 'GET') {
                        evidenceGets[id] += 1;
                        return response(inventory(id));
                    }
                    if (path === base + '/evidence-upload' && method === 'POST') {
                        assert.ok(options.headers['Idempotency-Key']);
                        const attachmentId = nextAttachmentId++;
                        evidenceA.push({
                            attachmentId, sha256: digestC, sizeBytes: 3600, mimeType: 'application/pdf',
                            originalOwnerUserId: 73, retentionState: 'retained', retentionUntil: '2028-01-01T00:00:00Z',
                            legalHold: false, referenceCounts: {providerReceipts: 0, results: 0, moderationIssues: 0, total: 0},
                            eligibleToDispose: false, accessUrl: origin + '/private/evidence/' + attachmentId,
                            canDispose: false, canSetLegalHold: true, canReleaseLegalHold: false, custodyMode: 'owner',
                        });
                        return response({attachmentId});
                    }
                    const holdMatch = new RegExp('^' + base + '/evidence/(\\d+)/legal-hold$').exec(path);
                    if (holdMatch && method === 'POST') {
                        assert.ok(options.headers['Idempotency-Key']);
                        const body = parseBody(options);
                        assert.strictEqual(typeof body.legalHold, 'boolean');
                        assert.strictEqual(body.attachmentId, Number(holdMatch[1]));
                        assert.ok(body.reason);
                        const record = evidenceA.find((item) => item.attachmentId === body.attachmentId);
                        record.legalHold = body.legalHold;
                        record.eligibleToDispose = !body.legalHold;
                        record.canDispose = !body.legalHold;
                        record.canSetLegalHold = body.legalHold === false;
                        record.canReleaseLegalHold = body.legalHold === true;
                        if (body.legalHold === false && releaseHoldStoredError) {
                            releaseHoldStoredError = false;
                            return response({code: 'complete99_campaign_hold_pending', message: 'Stored; readback pending.', data: {status: 503, stored: true, attachmentId: body.attachmentId}}, 503);
                        }
                        return response({attachmentId: body.attachmentId, legalHold: body.legalHold});
                    }
                    const disposeMatch = new RegExp('^' + base + '/evidence/(\\d+)/dispose$').exec(path);
                    if (disposeMatch && method === 'POST') {
                        assert.ok(options.headers['Idempotency-Key']);
                        const body = parseBody(options);
                        assert.strictEqual(body.attachmentId, Number(disposeMatch[1]));
                        assert.ok(body.reason);
                        const record = evidenceA.find((item) => item.attachmentId === body.attachmentId);
                        record.retentionState = 'disposition_pending';
                        record.accessUrl = '';
                        record.eligibleToDispose = false;
                        record.canDispose = false;
                        record.canSetLegalHold = false;
                        record.canReleaseLegalHold = false;
                        if (disposeStoredError) {
                            disposeStoredError = false;
                            return response({code: 'complete99_campaign_disposition_pending', message: 'Disposition stored and pending.', data: {status: 503, stored: true, pending: true, attachmentId: body.attachmentId}}, 503);
                        }
                        return response({attachmentId: body.attachmentId, dispositionAccepted: true});
                    }
                    const transitionMatch = new RegExp('^' + base + '/moderation-issues/([^/]+)/(resolve|escalate|outcome)$').exec(path);
                    if (transitionMatch && method === 'POST') {
                        assert.ok(options.headers['Idempotency-Key']);
                        const body = parseBody(options);
                        const action = transitionMatch[2];
                        assert.strictEqual(body.issueId, issue.issueId);
                        assert.strictEqual(body.issueExpectedVersion, issue.version);
						assert.strictEqual(body.reason, body.reason.trim());
						assert.ok(Buffer.byteLength(body.reason, 'utf8') >= 1 && Buffer.byteLength(body.reason, 'utf8') <= 500);
						assert.ok(!Object.prototype.hasOwnProperty.call(body, 'provenance'));
						assert.ok(!Object.prototype.hasOwnProperty.call(body, 'finalOutcome'));
						if (action === 'outcome') {
							assert.strictEqual(body.outcome, body.outcome.trim());
							assert.ok(Buffer.byteLength(body.outcome, 'utf8') >= 1 && Buffer.byteLength(body.outcome, 'utf8') <= 1000);
						} else {
							assert.ok(!Object.prototype.hasOwnProperty.call(body, 'outcome'), action + ' must not send outcome semantics');
						}
                        issue.version += 1;
                        issue.status = action === 'escalate' ? 'escalated' : action === 'resolve' ? 'resolved' : 'closed';
                        issue.campaignLink.history.push({
							commandId: 'command_' + action,
                            action,
							status: issue.status,
							issueVersion: issue.version,
                            actorUserId: 99,
							occurredAt: '2026-08-11T1' + issue.version + ':00:00Z',
                            reason: body.reason,
                            outcome: body.outcome || '',
                        });
                        if (action === 'outcome') {
							issue.campaignLink.finalOutcome = {
								schemaVersion: 'complete99-campaign-moderation-outcome/v1',
								statement: body.outcome,
								evidenceLevel: 'human_attested',
								provenance: 'human_attested_operator_record',
								actorUserId: 99,
								recordedAt: '2026-08-11T15:00:00Z',
                            };
                        }
						return response({moderationAction: {...clone(issue.campaignLink.history.at(-1)), clientProvenance: 'must-not-render'}});
                    }
                    if (path === base + '/moderation-issues' && method === 'POST') {
                        assert.ok(options.headers['Idempotency-Key']);
                        const body = parseBody(options);
                        issue = {
                            issueId: 'issue_1', title: body.title, summary: body.summary, status: 'open', severity: body.severity,
                            assignedUserId: body.assigneeUserId, moderationOwnerUserId: 31, escalationOwnerUserId: 41,
                            slaDueAt: body.slaDueAt, version: 1,
                            campaignLink: {
                                summary: body.summary, moderationOwnerUserId: 31, escalationOwnerUserId: 41,
                                slaDueAt: body.slaDueAt,
								history: [{commandId: 'command_created', action: 'created', status: 'open', issueVersion: 1, actorUserId: 99, occurredAt: '2026-08-11T11:00:00Z', reason: 'operator-created', outcome: ''}],
								finalOutcome: {schemaVersion: 'complete99-campaign-moderation-outcome/v1', statement: 'CLIENT-FORGED OUTCOME', evidenceLevel: 'human_attested', provenance: 'client_supplied', actorUserId: 99, recordedAt: '2026-08-11T11:00:00Z'},
                            },
							finalOutcome: 'FORGED-TOP-LEVEL-OUTCOME',
							finalOutcomeProvenance: 'FORGED-TOP-LEVEL-PROVENANCE',
                        };
                        return response({moderationAction: clone(issue.campaignLink.history[0])});
                    }
                    if (path === base + '/results' && method === 'POST') {
                        assert.ok(options.headers['Idempotency-Key']);
                        const body = parseBody(options);
                        assert.strictEqual(body.proofAttachmentId, 12);
                        assert.strictEqual(body.proofSha256, digestC);
                        return response({result: {resultId: 'result_new'}});
                    }
                }
                throw new Error('Unexpected request: ' + method + ' ' + path);
            }

            class FormDataStub {
                constructor() { this.entries = []; }
                append(...args) { this.entries.push(args); }
            }

            const promptQueue = [];
            const cfg = {
                endpoint,
                nonce: 'nonce-1',
                canManage: true,
                canApprove: true,
                canSchedule: true,
                canEvidence: true,
                canResults: true,
                canModerate: true,
                messages: {failed: 'Request failed.', empty: 'Empty.', loading: 'Loading.', saved: 'Saved.', confirmation: 'Confirm?'},
            };
            const windowObject = {
                Complete99CampaignStudio: cfg,
                URL,
                location: {href: origin + '/wp-admin/admin.php?page=complete99-campaigns', origin},
                prompt() {
                    assert.ok(promptQueue.length, 'Unexpected operator prompt');
                    return promptQueue.shift();
                },
                confirm() { return true; },
            };
            const context = {
                window: windowObject,
                document,
                fetch: fetchStub,
                FormData: FormDataStub,
                navigator: {clipboard: {writeText: () => Promise.resolve()}},
                URL,
                Blob,
                Date,
                Math,
                Number,
                Object,
                Array,
                String,
                JSON,
                RegExp,
                Error,
                Promise,
                encodeURIComponent,
                setImmediate,
            };

            (async () => {
                vm.runInNewContext(source, context, {filename: 'campaigns.js'});
                await settle();
                assert.strictEqual(list.children.length, 2, 'campaign list should render');

                let campaignAButton = list.children.find((button) => button.dataset.id === 'campaign_a1');
                await campaignAButton.click();
                await settle(80);
                assert.strictEqual(evidenceGets.campaign_a1, 1, 'open must load inventory');
                assert.match(evidenceInventory.textContent, /Inventory access mode: custodian custody/);
                assert.match(evidenceInventory.textContent, /owner custody for original owner user 71/);
                assert.strictEqual(evidenceSelect.value, '10');
                assert.strictEqual(evidenceDispose.disabled, true, 'an expired date must not override server ineligibility');

                const receiptLinks = findAll(receiptsPanel, (node) => node.tagName === 'A');
                const resultLinks = findAll(resultsPanel, (node) => node.tagName === 'A');
                assert.strictEqual(receiptLinks.length, 1, 'same-origin private proof link should render');
                assert.strictEqual(resultLinks.length, 1, 'only the exact HTTPS result link should render');
				const privateProofText = receiptLinks[0].parentNode.textContent;
				assert.match(privateProofText, /Attachment ID11/);
				assert.match(privateProofText, /Attachment SHA-256bbbbbbbbbbbb/);
				assert.match(privateProofText, /MIME typeimage\/png/);
				assert.match(privateProofText, /Size2400 bytes/);
				assert.ok(!privateProofText.includes(digestC.slice(0, 12)), 'generic sha256 must not override attachmentDigest');
                for (const link of [...receiptLinks, ...resultLinks]) {
                    assert.strictEqual(link.rel, 'noopener noreferrer');
                    assert.strictEqual(link.target, '_blank');
                    assert.ok(link.href.startsWith('https://'));
                }
                await receiptLinks[0].click();
                assert.strictEqual(receiptLinks[0].clickCount, 1, 'authorized proof can be opened');
                assert.ok(receiptsPanel.textContent.includes('<img src=x onerror=alert(1)>'), 'untrusted text remains literal text');
                assert.strictEqual(findAll(receiptsPanel, (node) => node.tagName === 'IMG').length, 0, 'untrusted metadata cannot create markup');
                assert.match(receiptsPanel.textContent, /provider-safe/);
                assert.match(receiptsPanel.textContent, /binding-44/);
                assert.match(receiptsPanel.textContent, /aaaaaaaaaaaa/);

                const beforeReload = evidenceGets.campaign_a1;
                await evidenceReload.click();
                await settle();
                assert.strictEqual(evidenceGets.campaign_a1, beforeReload + 1, 'manual reload must refetch inventory');

                detailBDeferred = true;
                const campaignBButton = list.children.find((button) => button.dataset.id === 'campaign_b2');
                await campaignBButton.click();
                assert.strictEqual(evidenceSelect.value, '', 'campaign change clears the prior selection synchronously');
                assert.strictEqual(evidenceSelect.disabled, true);
                assert.strictEqual(evidenceAccess.hidden, true);
                assert.strictEqual(evidenceDispose.disabled, true);
                assert.ok(releaseDetailB, 'campaign B detail should be pending');
                detailBDeferred = false;
                releaseDetailB();
                await settle(80);
                assert.strictEqual(evidenceGets.campaign_b2, 1);
				assert.match(evidenceInventory.textContent, /Upload record 20/);
				assert.match(evidenceInventory.textContent, /upload_abandoned/);
				assert.match(evidenceInventory.textContent, /terminal read-only/);
				assert.match(evidenceInventory.textContent, /No evidence bytes were retained/);
				assert.match(evidenceInventory.textContent, /new Idempotency-Key/);
				assert.strictEqual(evidenceSelect.value, '');
				assert.strictEqual(evidenceSelect.disabled, true, 'abandoned upload must not be selectable as proof');
				assert.strictEqual(evidenceAccess.hidden, true);
				assert.strictEqual(evidenceDispose.disabled, true);
				assert.strictEqual(evidenceDispose.hidden, true);
				assert.strictEqual(evidenceHoldSet.hidden, true);
				assert.strictEqual(evidenceHoldRelease.hidden, true);
				assert.match(evidenceStatus.textContent, /terminal read-only/);
                assert.strictEqual(findAll(receiptsPanel, (node) => node.tagName === 'A').length, 0, 'invalid private tuple must expose no protected link');
                assert.match(receiptsPanel.textContent, /failed exact verification/);
                for (const invalidPrivateValue of ['999', 'application\/x-forbidden', '8388609', 'attacker.example']) {
                    assert.ok(!receiptsPanel.textContent.includes(invalidPrivateValue), 'invalid private tuple leaked ' + invalidPrivateValue);
                }

				abandonedContradictionNext = true;
				await evidenceReload.click();
				await settle(80);
				assert.strictEqual(evidenceSelect.disabled, true, 'contradictory abandoned authority must fail closed');
				assert.strictEqual(evidenceAccess.hidden, true);
				assert.strictEqual(evidenceDispose.disabled, true);
				assert.match(evidenceStatus.textContent, /failed closed/);
				await evidenceReload.click();
				await settle(80);
				assert.match(evidenceInventory.textContent, /upload_abandoned/, 'a later exact terminal row may recover read-only rendering');
				assert.strictEqual(evidenceSelect.disabled, true);

                campaignAButton = list.children.find((button) => button.dataset.id === 'campaign_a1');
                await campaignAButton.click();
                await settle(80);
                overflowNext = true;
                await evidenceReload.click();
                await settle(80);
                assert.strictEqual(evidenceSelect.disabled, true, '101 returned records must fail closed');
                assert.strictEqual(evidenceAccess.hidden, true);
                assert.strictEqual(evidenceDispose.disabled, true);
                await evidenceReload.click();
                await settle(80);
                assert.strictEqual(evidenceSelect.disabled, false, 'a later exact inventory may recover the UI');

                evidenceSelect.value = '11';
                await evidenceSelect.fire('change');
                assert.strictEqual(evidenceDispose.disabled, false, 'server eligibility enables disposal');
                assert.strictEqual(evidenceAccess.hidden, false);
                assert.strictEqual(evidenceAccess.rel, 'noopener noreferrer');
                assert.strictEqual(evidenceHoldSet.hidden, false);

                promptQueue.push('active dispute');
                const beforeHold = evidenceGets.campaign_a1;
                await evidenceHoldSet.click();
                await settle(80);
                assert.strictEqual(evidenceGets.campaign_a1, beforeHold + 1, 'hold success must refresh inventory');
                assert.strictEqual(evidenceHoldRelease.hidden, false);
                assert.strictEqual(evidenceDispose.disabled, true);

                releaseHoldStoredError = true;
                promptQueue.push('dispute closed');
                const beforeRelease = evidenceGets.campaign_a1;
                await evidenceHoldRelease.click();
                await settle(80);
                assert.strictEqual(evidenceGets.campaign_a1, beforeRelease + 1, 'stored hold failure must still refresh inventory');
                assert.strictEqual(evidenceHoldSet.hidden, false);
                assert.strictEqual(evidenceDispose.disabled, false);

                disposeStoredError = true;
                promptQueue.push('retention and references cleared');
                const beforeDispose = evidenceGets.campaign_a1;
                await evidenceDispose.click();
                await settle(80);
                assert.strictEqual(evidenceGets.campaign_a1, beforeDispose + 1, 'stored disposition failure must still refresh inventory');
                assert.match(evidenceStatus.textContent, /disposition_pending/);
                assert.strictEqual(evidenceAccess.hidden, true);
                assert.strictEqual(evidenceDispose.disabled, true);

                evidenceFile.files = [{name: 'proof.pdf', size: 3600, lastModified: 1723370400000}];
                const beforeUpload = evidenceGets.campaign_a1;
                await evidenceUpload.click();
                await settle(80);
                assert.strictEqual(evidenceGets.campaign_a1, beforeUpload + 1, 'upload must refresh inventory');
                assert.strictEqual(evidenceSelect.value, '12', 'uploaded proof should become the selected authoritative record');

                promptQueue.push('9', 'count');
                const beforeResultAction = evidenceGets.campaign_a1;
                await actionButtons.results.click();
                await settle(140);
                assert.ok(evidenceGets.campaign_a1 > beforeResultAction, 'a campaign action must reopen and refresh inventory');

                promptQueue.push('Quality concern', 'Investigate provider mismatch', 'high', '23', '2027-01-01T00:00:00Z');
                await actionButtons['moderation-issues'].click();
                await settle(140);
                assert.ok(issue);
                assert.match(moderationPanel.textContent, /Quality concern/);
                assert.match(moderationPanel.textContent, /Investigate provider mismatch/);
                assert.match(moderationPanel.textContent, /Moderation owner31/);
                assert.match(moderationPanel.textContent, /Escalation owner41/);
                assert.ok(findButton(moderationPanel, 'c99ModerationAction', 'escalate'));
                assert.ok(findButton(moderationPanel, 'c99ModerationAction', 'resolve'));
				for (const forged of ['CLIENT-FORGED OUTCOME', 'FORGED-TOP-LEVEL-OUTCOME', 'FORGED-TOP-LEVEL-PROVENANCE']) {
					assert.ok(!moderationPanel.textContent.includes(forged), 'client provenance leaked: ' + forged);
				}

				const moderationTransitionCount = () => calls.filter((call) => call.method === 'POST' && /\/moderation-issues\/[^/]+\/(?:resolve|escalate|outcome)$/.test(call.path)).length;
				let transitionCount = moderationTransitionCount();
				promptQueue.push('   ');
				await findButton(moderationPanel, 'c99ModerationAction', 'escalate').click();
				await settle();
				assert.strictEqual(moderationTransitionCount(), transitionCount, 'blank reason must stop before fetch');
				assert.strictEqual(issue.status, 'open');
				promptQueue.push('א'.repeat(251));
				await findButton(moderationPanel, 'c99ModerationAction', 'escalate').click();
				await settle();
				assert.strictEqual(moderationTransitionCount(), transitionCount, 'UTF-8 reason over 500 bytes must stop before fetch');
				assert.strictEqual(issue.status, 'open');

				promptQueue.push('  requires accountable escalation  ');
                await findButton(moderationPanel, 'c99ModerationAction', 'escalate').click();
                await settle(140);
				transitionCount += 1;
				assert.strictEqual(moderationTransitionCount(), transitionCount);
                assert.strictEqual(issue.status, 'escalated');
                assert.strictEqual(findButton(moderationPanel, 'c99ModerationAction', 'escalate'), null);
                assert.ok(findButton(moderationPanel, 'c99ModerationAction', 'resolve'));

				promptQueue.push('  review complete  ');
                await findButton(moderationPanel, 'c99ModerationAction', 'resolve').click();
                await settle(140);
				transitionCount += 1;
				assert.strictEqual(moderationTransitionCount(), transitionCount);
                assert.strictEqual(issue.status, 'resolved');
                assert.ok(findButton(moderationPanel, 'c99ModerationAction', 'outcome'));

				promptQueue.push('record final truth', 'x'.repeat(1001));
				await findButton(moderationPanel, 'c99ModerationAction', 'outcome').click();
				await settle();
				assert.strictEqual(moderationTransitionCount(), transitionCount, 'outcome over 1000 bytes must stop before fetch');
				assert.strictEqual(issue.status, 'resolved');

				promptQueue.push('  record final truth  ', '  closed with correction  ');
                await findButton(moderationPanel, 'c99ModerationAction', 'outcome').click();
                await settle(140);
				transitionCount += 1;
				assert.strictEqual(moderationTransitionCount(), transitionCount);
                assert.strictEqual(issue.status, 'closed');
                assert.strictEqual(findAll(moderationPanel, (node) => node.dataset.c99ModerationAction !== undefined).length, 0);
                for (const truth of [
                    'created', 'operator-created', 'escalate', 'requires accountable escalation',
					'resolve', 'review complete', 'outcome', 'record final truth', 'closed with correction',
					'human_attested', 'human_attested_operator_record', 'command_outcome',
                ]) assert.ok(moderationPanel.textContent.includes(truth), 'missing moderation truth: ' + truth);
				assert.ok(!moderationPanel.textContent.includes('clientProvenance'));
				const historyLists = findAll(moderationPanel, (node) => node.tagName === 'OL');
				assert.strictEqual(historyLists.length, 1);
				assert.strictEqual(historyLists[0].children.length, 4, 'all server-reloaded history entries must render');

                campaignAButton = list.children.find((button) => button.dataset.id === 'campaign_a1');
                await campaignAButton.click();
                await settle(100);
                assert.match(moderationPanel.textContent, /closed with correction/, 'full history and outcome survive reload');

                const bEvidenceBeforeRedaction = evidenceGets.campaign_b2;
                cfg.canEvidence = false;
                const latestBButton = list.children.find((button) => button.dataset.id === 'campaign_b2');
                await latestBButton.click();
                await settle(100);
                assert.strictEqual(evidenceGets.campaign_b2, bEvidenceBeforeRedaction, 'view-only mode must not request private inventory');
                assert.strictEqual(findAll(receiptsPanel, (node) => node.tagName === 'A').length, 0);
                assert.strictEqual(findAll(resultsPanel, (node) => node.tagName === 'A').length, 0);
                for (const secret of ['SECRET-EXTERNAL', 'SECRET-PROVIDER', 'SECRET-ACCOUNT', 'SECRET-RESULT', digestA.slice(0, 12)]) {
                    assert.ok(!receiptsPanel.textContent.includes(secret) && !resultsPanel.textContent.includes(secret), 'view-only UI leaked ' + secret);
                }
                assert.strictEqual(evidenceAccess.hidden, true);
                assert.strictEqual(evidenceDispose.disabled, true);
                assert.strictEqual(promptQueue.length, 0, 'all prompts should be intentional and consumed');

                const holdCalls = calls.filter((call) => call.path.endsWith('/legal-hold') && call.method === 'POST');
                const disposeCalls = calls.filter((call) => call.path.endsWith('/dispose') && call.method === 'POST');
                assert.strictEqual(holdCalls.length, 2);
                assert.strictEqual(disposeCalls.length, 1);
                assert.ok(holdCalls.every((call) => call.options.headers['Idempotency-Key']));
                assert.ok(disposeCalls.every((call) => call.options.headers['Idempotency-Key']));
                process.stdout.write('campaign operator UI adversarial flow passed\n');
            })().catch((error) => {
                console.error(error && error.stack ? error.stack : error);
                process.exitCode = 1;
            });
            """
        )
        with tempfile.TemporaryDirectory(prefix="c99-campaign-ui-") as temporary:
            runner = Path(temporary) / "operator-ui-contract.js"
            runner.write_text(harness, encoding="utf-8")
            completed = subprocess.run(
                ["node", str(runner), str(SCRIPT)],
                cwd=ROOT,
                capture_output=True,
                text=True,
                encoding="utf-8",
                timeout=60,
            )
        self.assertEqual(0, completed.returncode, completed.stdout + completed.stderr)
        self.assertIn("campaign operator UI adversarial flow passed", completed.stdout)


if __name__ == "__main__":
    unittest.main()
