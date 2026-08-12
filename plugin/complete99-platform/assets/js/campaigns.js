(function () {
	'use strict';
	var cfg = window.Complete99CampaignStudio;
	var root = document.querySelector('[data-c99-campaign-studio]');
	if (!cfg || !root) return;

	var status = root.querySelector('[data-c99-campaign-status]');
	var list = root.querySelector('[data-c99-campaign-list]');
	var searchForm = root.querySelector('[data-c99-campaign-search-form]');
	var searchInput = root.querySelector('[data-c99-campaign-search]');
	var previousPage = root.querySelector('[data-c99-campaign-prev]');
	var nextPage = root.querySelector('[data-c99-campaign-next]');
	var pageStatus = root.querySelector('[data-c99-campaign-page]');
	var form = root.querySelector('[data-c99-campaign-form]');
	var actions = root.querySelector('[data-c99-campaign-actions]');
	var packagePreview = root.querySelector('[data-c99-package-preview]');
	var packageCopy = root.querySelector('[data-c99-package-copy]');
	var packageDownload = root.querySelector('[data-c99-package-download]');
	var evidenceFile = root.querySelector('[data-c99-evidence-file]');
	var evidenceUpload = root.querySelector('[data-c99-evidence-upload]');
	var evidenceInventory = root.querySelector('[data-c99-evidence-inventory]');
	var evidenceSelect = root.querySelector('[data-c99-evidence-select]');
	var evidenceReload = root.querySelector('[data-c99-evidence-reload]');
	var evidenceAccess = root.querySelector('[data-c99-evidence-access]');
	var evidenceDispose = root.querySelector('[data-c99-evidence-dispose]');
	var evidenceHoldSet = root.querySelector('[data-c99-evidence-hold-set]');
	var evidenceHoldRelease = root.querySelector('[data-c99-evidence-hold-release]');
	var evidenceStatus = root.querySelector('[data-c99-evidence-status]');
	var receiptsPanel = root.querySelector('[data-c99-provider-receipts]');
	var resultsPanel = root.querySelector('[data-c99-results]');
	var signalsPanel = root.querySelector('[data-c99-signals]');
	var moderationPanel = root.querySelector('[data-c99-moderation]');
	var current = null;
	var lastPackage = '';
	var lastPackageDigest = '';
	var lastPackageArtifact = '';
	var lastEvidence = null;
	var evidenceRecords = [];
	var evidenceCampaignId = '';
	var evidenceCustodyMode = '';
	var inFlight = false;
	var intentKeys = Object.create(null);
	var listPage = 1;
	var listSearch = '';
	var moderationReasonMaximum = 500;
	var moderationOutcomeMaximum = 1000;
	var privateEvidenceMaximumBytes = 8388608;
	var privateEvidenceMimeTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'text/plain'];

	function hasOwn(object, key) {
		return !!object && Object.prototype.hasOwnProperty.call(object, key);
	}

	function pick(object, primary, fallback) {
		if (hasOwn(object, primary)) return object[primary];
		return fallback && hasOwn(object, fallback) ? object[fallback] : undefined;
	}

	function text(value) {
		return value === null || value === undefined ? '' : String(value);
	}

	function boundedText(value, maximum) {
		return typeof value === 'string' && value.length <= maximum ? value : '';
	}

	function utf8Length(value) {
		return new Blob([value]).size;
	}

	function boundedUtf8Text(value, maximum) {
		return typeof value === 'string' && utf8Length(value) <= maximum ? value : '';
	}

	function exactInteger(value, minimum) {
		return typeof value === 'number' && Number.isSafeInteger(value) && value >= minimum;
	}

	function digestPrefix(value) {
		var digest = text(value).toLowerCase();
		return /^[a-f0-9]{64}$/.test(digest) ? digest.slice(0, 12) + '…' : '';
	}

	function exactRfc3339(value) {
		value = boundedText(value, 64);
		return /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?Z$/.test(value) ? value : '';
	}

	function sameOriginPrivateUrl(value) {
		if (typeof value !== 'string' || value === '' || value.length > 4096) return '';
		try {
			var parsed = new window.URL(value, window.location.href);
			if (parsed.username || parsed.password || parsed.origin !== window.location.origin) return '';
			return parsed.href;
		} catch (error) {
			return '';
		}
	}

	function exactHttpsUrl(value) {
		if (typeof value !== 'string' || !/^https:\/\//.test(value)) return '';
		try {
			var parsed = new window.URL(value);
			if (parsed.protocol !== 'https:' || parsed.username || parsed.password) return '';
			return parsed.href;
		} catch (error) {
			return '';
		}
	}

	function intentKey(intent) {
		if (!intentKeys[intent]) {
			intentKeys[intent] = 'campaign-ui-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2) + '0000000000000000';
		}
		return intentKeys[intent];
	}

	function finishIntent(intent) {
		delete intentKeys[intent];
	}

	function setInFlight(active) {
		inFlight = active;
		root.toggleAttribute('aria-busy', active);
		Array.prototype.forEach.call(root.querySelectorAll('button,input,select,textarea'), function (control) {
			if (active) {
				control.dataset.c99WasDisabled = control.disabled ? '1' : '0';
				control.disabled = true;
			} else if (control.dataset.c99WasDisabled !== undefined) {
				control.disabled = control.dataset.c99WasDisabled === '1';
				delete control.dataset.c99WasDisabled;
			}
		});
	}

	function request(url, options) {
		options = options || {};
		if (inFlight) return Promise.reject(new Error(cfg.messages.failed));
		var credentials='same-origin';
		options.credentials = credentials;
		var baseHeaders = {'X-WP-Nonce':cfg.nonce};
		var formDataBody = typeof FormData !== 'undefined' && options.body instanceof FormData;
		if (!formDataBody) baseHeaders['Content-Type'] = 'application/json';
		options.headers = Object.assign(baseHeaders, options.headers || {});
		setInFlight(true);
		return fetch(url, options).then(function (response) {
			return response.json().catch(function () { return {}; }).then(function (data) {
				if (!response.ok) {
					var apiError = new Error(data.message || cfg.messages.failed);
					var details = data && typeof data.data === 'object' && data.data ? data.data : data;
					apiError.code = boundedText(data && data.code, 191);
					apiError.details = details && typeof details === 'object' ? details : {};
					apiError.storedOrPending = apiError.details.stored === true || apiError.details.pending === true || apiError.details.recoveryRequired === true || /(?:pending|recovery)/.test(apiError.code);
					throw apiError;
				}
				return data;
			});
		}).finally(function () {
			setInFlight(false);
		});
	}

	function note(message, error) {
		if (!actions) return;
		actions.textContent = message || '';
		actions.className = error ? 'notice notice-error inline' : 'notice notice-success inline';
	}

	function paint(data) {
		if (!list || !status) return;
		list.textContent = '';
		var rows = Array.isArray(data.campaigns) ? data.campaigns : [];
		var pagination = data.pagination || {page:1,total:rows.length,totalPages:1,hasPrevious:false,hasNext:false};
		listPage = Number.isSafeInteger(Number(pagination.page)) ? Number(pagination.page) : 1;
		status.textContent = rows.length ? rows.length + ' shown of ' + pagination.total + ' campaign records / רשומות קמפיין' : cfg.messages.empty;
		if (pageStatus) pageStatus.textContent = listPage + ' / ' + pagination.totalPages;
		if (previousPage) previousPage.disabled = pagination.hasPrevious !== true;
		if (nextPage) nextPage.disabled = pagination.hasNext !== true;
		rows.forEach(function (row) {
			var button = document.createElement('button');
			button.type = 'button';
			button.className = 'c99-campaign-card';
			button.dataset.id = text(row.campaignId);
			var strong = document.createElement('strong');
			strong.textContent = text(row.name);
			var meta = document.createElement('span');
			meta.textContent = text(row.primaryChannel) + ' · ' + text(row.lifecycleState) + ' · v' + text(row.version);
			button.append(strong, meta);
			button.addEventListener('click', function () { if (!inFlight) open(row.campaignId); });
			list.appendChild(button);
		});
	}

	function load(page, search) {
		if (Number.isSafeInteger(page) && page > 0) listPage = page;
		if (typeof search === 'string') listSearch = search.trim();
		if (status) status.textContent = cfg.messages.loading;
		var url = cfg.endpoint + '?page=' + encodeURIComponent(listPage) + '&limit=50';
		if (listSearch) url += '&search=' + encodeURIComponent(listSearch);
		return request(url).then(paint).catch(function (error) { if (status) status.textContent = error.message; });
	}

	function appendDefinitionList(container, pairs) {
		var visible = pairs.filter(function (pair) { return pair[1] !== '' && pair[1] !== null && pair[1] !== undefined; });
		if (!visible.length) return;
		var definitions = document.createElement('dl');
		visible.forEach(function (pair) {
			var term = document.createElement('dt');
			var description = document.createElement('dd');
			term.textContent = pair[0];
			description.textContent = text(pair[1]);
			definitions.append(term, description);
		});
		container.appendChild(definitions);
	}

	function appendValidatedLink(container, href, label) {
		if (!href) return false;
		var link = document.createElement('a');
		link.href = href;
		link.target = '_blank';
		link.rel = 'noopener noreferrer';
		link.referrerPolicy = 'no-referrer';
		link.textContent = label;
		container.appendChild(link);
		return true;
	}

	function evidenceSummary(row, fallback) {
		var summary = row && row.evidenceSummary;
		if (!summary && fallback) summary = row && row[fallback];
		return summary && typeof summary === 'object' && !Array.isArray(summary) ? summary : null;
	}

	function normalizePrivateEvidenceSummary(summary) {
		if (!cfg.canEvidence || !summary || summary.kind !== 'private_attachment') return null;
		var attachmentDigest = boundedText(summary.attachmentDigest, 64).toLowerCase();
		var attachmentId = summary.attachmentId;
		var mimeType = boundedText(summary.mimeType, 127);
		var sizeBytes = summary.sizeBytes;
		if (!/^[a-f0-9]{64}$/.test(attachmentDigest) || !exactInteger(attachmentId, 1) || privateEvidenceMimeTypes.indexOf(mimeType) < 0 || !exactInteger(sizeBytes, 1) || sizeBytes > privateEvidenceMaximumBytes) return null;
		return {
			attachmentDigest: attachmentDigest,
			attachmentId: attachmentId,
			mimeType: mimeType,
			sizeBytes: sizeBytes,
			retentionUntil: exactRfc3339(summary.retentionUntil),
			accessUrl: sameOriginPrivateUrl(summary.accessUrl)
		};
	}

	function renderEvidenceSummary(container, summary, label) {
		var section = document.createElement('div');
		var heading = document.createElement('h4');
		heading.textContent = label;
		section.appendChild(heading);
		if (!summary) {
			var redacted = document.createElement('p');
			redacted.textContent = 'Evidence reference unavailable or redacted.';
			section.appendChild(redacted);
			container.appendChild(section);
			return;
		}
		var linked = false;
		var kind = boundedText(summary.kind, 80);
		if ('private_attachment' === kind) {
			var privateSummary = normalizePrivateEvidenceSummary(summary);
			if (privateSummary) {
				appendDefinitionList(section, [
					['Kind', kind],
					['Attachment ID', privateSummary.attachmentId],
					['Attachment SHA-256', digestPrefix(privateSummary.attachmentDigest)],
					['MIME type', privateSummary.mimeType],
					['Size', privateSummary.sizeBytes + ' bytes'],
					['Retention until', privateSummary.retentionUntil]
				]);
				linked = appendValidatedLink(section, privateSummary.accessUrl, 'Open protected proof');
			} else {
				var invalidPrivate = document.createElement('p');
				invalidPrivate.textContent = 'Private evidence metadata is unavailable or failed exact verification.';
				section.appendChild(invalidPrivate);
			}
		} else if ('https' === kind) {
			appendDefinitionList(section, [['Kind', kind], ['Host', boundedText(summary.host, 253)]]);
			if (cfg.canEvidence) linked = appendValidatedLink(section, exactHttpsUrl(summary.reference), 'Open HTTPS evidence reference');
		} else if ('redacted' === kind) {
			appendDefinitionList(section, [['Kind', kind]]);
		}
		if (!linked) {
			var unavailable = document.createElement('p');
			unavailable.textContent = 'No authorized evidence link is available.';
			section.appendChild(unavailable);
		}
		container.appendChild(section);
	}

	function renderReceipts(rows) {
		if (!receiptsPanel) return;
		receiptsPanel.textContent = '';
		var heading = document.createElement('h3');
		heading.textContent = 'Provider receipts / proof truth';
		receiptsPanel.appendChild(heading);
		if (!Array.isArray(rows) || !rows.length) {
			var empty = document.createElement('p');
			empty.textContent = 'No provider receipts.';
			receiptsPanel.appendChild(empty);
			return;
		}
		rows.forEach(function (row) {
			var receipt = document.createElement('article');
			var receiptId = text(pick(row, 'receiptId', 'receipt_id'));
			var title = document.createElement('h4');
			title.textContent = receiptId ? 'Receipt ' + receiptId : 'Provider receipt';
			receipt.appendChild(title);
			appendDefinitionList(receipt, [
				['Campaign ID', text(pick(row, 'campaignId', 'campaign_id'))],
				['Channel', text(pick(row, 'channel'))],
				['Status', text(pick(row, 'status', 'receipt_status'))],
				['Proof level', text(pick(row, 'proofLevel', 'proof_level'))],
				['External state', text(pick(row, 'externalState', 'external_state'))],
				['Campaign version', text(pick(row, 'campaignVersion', 'campaign_version'))],
				['Provider key', cfg.canEvidence ? text(pick(row, 'providerKey', 'provider_key')) : ''],
				['Provider account binding', cfg.canEvidence ? text(pick(row, 'providerAccountRef', 'provider_account_ref')) : ''],
				['External ID', cfg.canEvidence ? text(pick(row, 'externalId', 'external_id')) : ''],
				['Package ID', cfg.canEvidence ? text(pick(row, 'packageId', 'package_id')) : ''],
				['Material SHA-256', cfg.canEvidence ? digestPrefix(pick(row, 'materialDigest', 'material_digest')) : ''],
				['Payload SHA-256', cfg.canEvidence ? digestPrefix(pick(row, 'payloadDigest', 'payload_digest')) : ''],
				['Occurred at', text(pick(row, 'occurredAt', 'occurred_at'))]
			]);
			renderEvidenceSummary(receipt, evidenceSummary(row, 'proof'), 'Proof summary');
			receiptsPanel.appendChild(receipt);
		});
	}

	function renderResults(rows) {
		if (!resultsPanel) return;
		resultsPanel.textContent = '';
		var heading = document.createElement('h3');
		heading.textContent = 'Observed results — not verified performance';
		resultsPanel.appendChild(heading);
		if (!Array.isArray(rows) || !rows.length) {
			var empty = document.createElement('p');
			empty.textContent = 'No observed results.';
			resultsPanel.appendChild(empty);
			return;
		}
		rows.forEach(function (row) {
			var result = document.createElement('article');
			var resultId = text(pick(row, 'resultId', 'result_id'));
			var title = document.createElement('h4');
			title.textContent = resultId ? 'Result ' + resultId : 'Observed result';
			result.appendChild(title);
			appendDefinitionList(result, [
				['Campaign ID', text(pick(row, 'campaignId', 'campaign_id'))],
				['Status', text(pick(row, 'status'))],
				['Metric', text(pick(row, 'metricKey', 'metric_key'))],
				['Value', text(pick(row, 'value', 'value_decimal'))],
				['Unit', text(pick(row, 'unit'))],
				['Observation type', text(pick(row, 'observationType', 'observation_type'))],
				['Evidence level', text(pick(row, 'evidenceLevel', 'evidence_level'))],
				['Campaign version', text(pick(row, 'campaignVersion', 'campaign_version'))],
				['External ID', cfg.canEvidence ? text(pick(row, 'externalId', 'external_id')) : ''],
				['Package ID', cfg.canEvidence ? text(pick(row, 'packageId', 'package_id')) : ''],
				['Provider receipt ID', cfg.canEvidence ? text(pick(row, 'providerReceiptId', 'provider_receipt_id')) : ''],
				['Material SHA-256', cfg.canEvidence ? digestPrefix(pick(row, 'materialDigest', 'material_digest')) : ''],
				['Payload SHA-256', cfg.canEvidence ? digestPrefix(pick(row, 'payloadDigest', 'payload_digest')) : ''],
				['Observed at', text(pick(row, 'observedAt', 'observed_at'))]
			]);
			renderEvidenceSummary(result, evidenceSummary(row, 'evidence'), 'Source summary');
			resultsPanel.appendChild(result);
		});
	}

	function renderSignals(data) {
		if (!signalsPanel) return;
		signalsPanel.textContent = '';
		var heading = document.createElement('h3');
		heading.textContent = 'Anonymous web signals — unverified';
		signalsPanel.appendChild(heading);
		var rows = data && Array.isArray(data.aggregates) ? data.aggregates : [];
		if (!rows.length) {
			var empty = document.createElement('p');
			empty.textContent = 'No anonymous web signals.';
			signalsPanel.appendChild(empty);
			return;
		}
		var signalList = document.createElement('ul');
		rows.forEach(function (row) {
			var item = document.createElement('li');
			item.textContent = text(pick(row, 'eventKey', 'event_key')) + ': ' + text(pick(row, 'eventCount', 'event_count')) + ' · anonymous_unverified';
			signalList.appendChild(item);
		});
		signalsPanel.appendChild(signalList);
	}

	function moderationField(issue, link, key, legacy) {
		if (hasOwn(issue, key)) return issue[key];
		if (legacy && hasOwn(issue, legacy)) return issue[legacy];
		if (hasOwn(link, key)) return link[key];
		return legacy && hasOwn(link, legacy) ? link[legacy] : undefined;
	}

	function normalizeModerationOutcome(link) {
		var outcome = link && link.finalOutcome;
		if (!outcome || typeof outcome !== 'object' || Array.isArray(outcome) || outcome.schemaVersion !== 'complete99-campaign-moderation-outcome/v1') return null;
		var statement = boundedUtf8Text(outcome.statement, moderationOutcomeMaximum);
		var recordedAt = exactRfc3339(outcome.recordedAt);
		if (!statement || statement !== statement.trim() || outcome.evidenceLevel !== 'human_attested' || outcome.provenance !== 'human_attested_operator_record' || !exactInteger(outcome.actorUserId, 1) || !recordedAt) return null;
		return {statement:statement, evidenceLevel:outcome.evidenceLevel, provenance:outcome.provenance, actorUserId:outcome.actorUserId, recordedAt:recordedAt};
	}

	function renderModerationOutcome(container, link) {
		var outcome = normalizeModerationOutcome(link);
		if (!outcome) {
			appendDefinitionList(container, [['Final outcome', 'No server-authored human-attested final outcome recorded.']]);
			return;
		}
		appendDefinitionList(container, [
			['Final outcome', outcome.statement],
			['Evidence level', outcome.evidenceLevel],
			['Final outcome provenance', outcome.provenance],
			['Attested by WordPress user', outcome.actorUserId],
			['Recorded at', outcome.recordedAt]
		]);
	}

	function renderModeration(rows) {
		if (!moderationPanel) return;
		moderationPanel.textContent = '';
		var heading = document.createElement('h3');
		heading.textContent = 'Campaign moderation history';
		moderationPanel.appendChild(heading);
		if (!Array.isArray(rows) || !rows.length) {
			var empty = document.createElement('p');
			empty.textContent = 'No campaign-linked moderation issues.';
			moderationPanel.appendChild(empty);
			return;
		}
		rows.forEach(function (issue) {
			var link = issue && issue.campaignLink && typeof issue.campaignLink === 'object' ? issue.campaignLink : {};
			var wrapper = document.createElement('article');
			var issueId = text(issue.issueId);
			var title = document.createElement('h4');
			title.textContent = text(issue.title) + (issueId ? ' · ' + issueId : '');
			wrapper.appendChild(title);
			var summary = moderationField(issue, link, 'summary');
			if (text(summary)) {
				var summaryText = document.createElement('p');
				summaryText.textContent = text(summary);
				wrapper.appendChild(summaryText);
			}
			appendDefinitionList(wrapper, [
				['Status', text(issue.status)],
				['Severity', text(issue.severity)],
				['Assigned user', text(issue.assignedUserId)],
				['Moderation owner', text(moderationField(issue, link, 'moderationOwnerUserId', 'ownerUserId'))],
				['Escalation owner', text(moderationField(issue, link, 'escalationOwnerUserId'))],
				['SLA due', text(moderationField(issue, link, 'slaDueAt'))],
				['Version', text(issue.version)]
			]);

			var historyHeading = document.createElement('h5');
			historyHeading.textContent = 'Complete action history';
			wrapper.appendChild(historyHeading);
			var history = Array.isArray(link.history) ? link.history : [];
			if (history.length) {
				var historyList = document.createElement('ol');
				history.forEach(function (entry) {
					var historyItem = document.createElement('li');
					entry = entry && typeof entry === 'object' && !Array.isArray(entry) ? entry : {};
					appendDefinitionList(historyItem, [
						['Action', boundedText(entry.action, 32)],
						['Status', boundedText(entry.status, 32)],
						['Issue version', exactInteger(entry.issueVersion, 1) ? entry.issueVersion : ''],
						['Actor user', exactInteger(entry.actorUserId, 1) ? entry.actorUserId : ''],
						['At', boundedText(entry.occurredAt, 64)],
						['Reason', boundedUtf8Text(entry.reason, moderationReasonMaximum) || '—'],
						['Outcome statement', boundedUtf8Text(entry.outcome, moderationOutcomeMaximum) || '—'],
						['Command ID', boundedText(entry.commandId, 128)]
					]);
					historyList.appendChild(historyItem);
				});
				wrapper.appendChild(historyList);
			} else {
				var noHistory = document.createElement('p');
				noHistory.textContent = 'No action history is available.';
				wrapper.appendChild(noHistory);
			}

			renderModerationOutcome(wrapper, link);

			if (cfg.canModerate) {
				var transitions = issue.status === 'open' ? ['resolve', 'escalate'] : issue.status === 'escalated' ? ['resolve'] : issue.status === 'resolved' ? ['outcome'] : [];
				if (transitions.length) {
					var controls = document.createElement('div');
					controls.setAttribute('role', 'group');
					controls.setAttribute('aria-label', 'Available actions for moderation issue ' + issueId);
					transitions.forEach(function (transition) {
						var button = document.createElement('button');
						button.type = 'button';
						button.className = 'button';
						button.dataset.c99ModerationAction = transition;
						button.dataset.issueId = issueId;
						button.dataset.issueVersion = text(issue.version);
						button.textContent = transition === 'outcome' ? 'Record final outcome' : transition.charAt(0).toUpperCase() + transition.slice(1) + ' issue';
						button.setAttribute('aria-label', button.textContent + ' ' + issueId);
						controls.appendChild(button);
					});
					wrapper.appendChild(controls);
				}
			}
			moderationPanel.appendChild(wrapper);
		});
	}

	function setPackageArtifact(row) {
		lastPackage = row && row.package_id ? row.package_id : '';
		lastPackageDigest = row && row.package_digest ? row.package_digest : '';
		lastPackageArtifact = row && row.artifactAvailable === true ? String(row.artifactJson || '') : '';
		if (packagePreview) packagePreview.textContent = lastPackageArtifact || 'No digest-verified persisted package is available. No provider publication is claimed.';
		if (packageCopy) packageCopy.disabled = !lastPackageArtifact;
		if (packageDownload) packageDownload.disabled = !lastPackageArtifact;
	}

	function paintDetail(data) {
		var packages = Array.isArray(data.packages) ? data.packages : [];
		setPackageArtifact(packages.find(function (row) { return row.artifactAvailable === true; }) || packages[0] || null);
		renderReceipts(data.providerReceipts);
		renderResults(data.results);
		renderSignals(data.unverifiedWebSignals);
		renderModeration(data.moderationIssues);
	}

	function setExistingAccessLink(record) {
		if (!evidenceAccess) return;
		evidenceAccess.hidden = true;
		evidenceAccess.removeAttribute('href');
		evidenceAccess.removeAttribute('target');
		evidenceAccess.removeAttribute('rel');
		if (!record || record.retentionState !== 'retained' || !record.accessUrl) return;
		evidenceAccess.href = record.accessUrl;
		evidenceAccess.target = '_blank';
		evidenceAccess.rel = 'noopener noreferrer';
		evidenceAccess.referrerPolicy = 'no-referrer';
		evidenceAccess.textContent = 'Open selected protected proof';
		evidenceAccess.hidden = false;
	}

	function updateEvidenceControls() {
		var record = lastEvidence && lastEvidence.retentionState !== 'upload_abandoned' ? lastEvidence : null;
		var abandonedOnly = evidenceRecords.length > 0 && evidenceRecords.every(function (candidate) { return candidate.retentionState === 'upload_abandoned'; });
		if (evidenceDispose) {
			evidenceDispose.hidden = abandonedOnly;
			evidenceDispose.disabled = !(cfg.canEvidence && record && record.eligibleToDispose === true && record.canDispose === true);
		}
		var canAdministerHold = cfg.canEvidence === true && cfg.canApprove === true;
		if (evidenceHoldSet) {
			evidenceHoldSet.hidden = !(canAdministerHold && record && record.canSetLegalHold === true);
			evidenceHoldSet.disabled = evidenceHoldSet.hidden;
		}
		if (evidenceHoldRelease) {
			evidenceHoldRelease.hidden = !(canAdministerHold && record && record.canReleaseLegalHold === true);
			evidenceHoldRelease.disabled = evidenceHoldRelease.hidden;
		}
		setExistingAccessLink(record);
		if (!evidenceStatus) return;
		if (!record) {
			evidenceStatus.textContent = abandonedOnly ? 'Upload abandoned: this is a terminal read-only record. No evidence bytes were retained. Start a new upload with a new Idempotency-Key.' : evidenceRecords.length ? 'Choose a protected proof record.' : 'No active protected evidence is available for this campaign.';
			return;
		}
		var counts = record.referenceCounts;
		evidenceStatus.textContent = 'Attachment ' + record.attachmentId + ' · ' + record.mimeType + ' · ' + record.sizeBytes + ' bytes · ' + record.retentionState + ' until ' + record.retentionUntil + ' · legal hold ' + (record.legalHold ? 'yes' : 'no') + ' · references ' + counts.total + ' (receipts ' + counts.providerReceipts + ', results ' + counts.results + ', moderation ' + counts.moderationIssues + ') · custody ' + record.custodyMode + ' for original owner user ' + record.originalOwnerUserId + ' · disposal ' + (record.eligibleToDispose ? 'server-eligible' : 'not server-eligible') + '.';
	}

	function resetEvidenceInventory(message) {
		evidenceRecords = [];
		evidenceCampaignId = '';
		evidenceCustodyMode = '';
		lastEvidence = null;
		if (evidenceInventory) evidenceInventory.textContent = '';
		if (evidenceSelect) {
			evidenceSelect.textContent = '';
			var placeholder = document.createElement('option');
			placeholder.value = '';
			placeholder.textContent = 'Choose protected evidence';
			evidenceSelect.appendChild(placeholder);
			evidenceSelect.value = '';
			evidenceSelect.disabled = true;
		}
		if (evidenceReload) evidenceReload.disabled = !(cfg.canEvidence && current);
		updateEvidenceControls();
		if (evidenceStatus && message) evidenceStatus.textContent = message;
	}

	function normalizeReferenceCounts(counts) {
		if (!counts || typeof counts !== 'object' || Array.isArray(counts)) throw new Error('Evidence reference counts are unavailable.');
		var receipts = counts.providerReceipts;
		var results = counts.results;
		var moderation = counts.moderationIssues;
		var total = counts.total;
		if (!exactInteger(receipts, 0) || !exactInteger(results, 0) || !exactInteger(moderation, 0) || !exactInteger(total, 0) || total !== receipts + results + moderation) {
			throw new Error('Evidence reference counts failed exact verification.');
		}
		return {providerReceipts:receipts, results:results, moderationIssues:moderation, total:total};
	}

	function normalizeEvidenceRecord(row, custodyMode) {
		if (!row || typeof row !== 'object' || Array.isArray(row)) throw new Error('Evidence inventory returned an invalid record.');
		var states = ['retained', 'disposition_pending', 'disposition_recovery_required', 'upload_abandoned'];
		var attachmentId = row.attachmentId;
		var sha256 = boundedText(row.sha256, 64).toLowerCase();
		var sizeBytes = row.sizeBytes;
		var mimeType = boundedText(row.mimeType, 127);
		var originalOwnerUserId = row.originalOwnerUserId;
		var retentionState = boundedText(row.retentionState, 64);
		var retentionUntil = boundedText(row.retentionUntil, 64);
		var legalHold = row.legalHold;
		var recordCustodyMode = boundedText(row.custodyMode, 16);
		if (!exactInteger(attachmentId, 1) || !/^[a-f0-9]{64}$/.test(sha256) || !exactInteger(sizeBytes, 1) || sizeBytes > privateEvidenceMaximumBytes || privateEvidenceMimeTypes.indexOf(mimeType) < 0 || !exactInteger(originalOwnerUserId, 1) || states.indexOf(retentionState) < 0 || !/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?Z$/.test(retentionUntil) || typeof legalHold !== 'boolean' || ['owner', 'custodian'].indexOf(recordCustodyMode) < 0) {
			throw new Error('Evidence identity or retention truth failed exact verification.');
		}
		['eligibleToDispose', 'canDispose', 'canSetLegalHold', 'canReleaseLegalHold'].forEach(function (key) {
			if (typeof row[key] !== 'boolean') throw new Error('Evidence authority flags are unavailable.');
		});
		if (['owner', 'custodian'].indexOf(custodyMode) < 0 || (row.canDispose && !row.eligibleToDispose) || (row.canSetLegalHold && row.canReleaseLegalHold) || (row.canSetLegalHold && legalHold !== false) || (row.canReleaseLegalHold && legalHold !== true)) {
			throw new Error('Evidence custody or authority flags are inconsistent.');
		}
		var accessUrl = boundedText(row.accessUrl, 4096);
		if (accessUrl) {
			accessUrl = sameOriginPrivateUrl(accessUrl);
			if (!accessUrl || retentionState !== 'retained') throw new Error('Evidence access URL failed exact verification.');
		}
		var referenceCounts = normalizeReferenceCounts(row.referenceCounts);
		var eligibilityReasons = Array.isArray(row.eligibilityReasons) ? row.eligibilityReasons : [];
		if (retentionState === 'upload_abandoned' && (legalHold !== false || referenceCounts.total !== 0 || row.eligibleToDispose !== false || row.canDispose !== false || row.canSetLegalHold !== false || row.canReleaseLegalHold !== false || accessUrl !== '' || eligibilityReasons.length !== 1 || eligibilityReasons[0] !== 'upload_abandoned')) {
			throw new Error('Abandoned upload terminal authority is contradictory.');
		}
		return {
			attachmentId:attachmentId,
			sha256:sha256,
			sizeBytes:sizeBytes,
			mimeType:mimeType,
			originalOwnerUserId:originalOwnerUserId,
			retentionState:retentionState,
			retentionUntil:retentionUntil,
			legalHold:legalHold,
			referenceCounts:referenceCounts,
			eligibilityReasons:eligibilityReasons,
			eligibleToDispose:row.eligibleToDispose,
			accessUrl:accessUrl,
			canDispose:row.canDispose,
			canSetLegalHold:row.canSetLegalHold,
			canReleaseLegalHold:row.canReleaseLegalHold,
			custodyMode:recordCustodyMode
		};
	}

	function validateEvidenceInventory(data, campaignId) {
		if (!data || data.schemaVersion !== 1 || data.campaignId !== campaignId || !exactInteger(data.locationId, 0) || !current || Number(current.locationId) !== data.locationId || ['owner', 'custodian'].indexOf(data.custodyMode) < 0 || !Array.isArray(data.items) || data.items.length > 100) {
			throw new Error('Evidence inventory failed its authoritative response contract.');
		}
		var seen = Object.create(null);
		var records = data.items.map(function (row) {
			var record = normalizeEvidenceRecord(row, data.custodyMode);
			if (seen[record.attachmentId]) throw new Error('Evidence inventory contains a duplicate attachment.');
			seen[record.attachmentId] = true;
			return record;
		});
		return {records:records, custodyMode:data.custodyMode};
	}

	function renderEvidenceInventory(preferredAttachmentId) {
		if (evidenceInventory) {
			evidenceInventory.textContent = '';
			var heading = document.createElement('h3');
			heading.textContent = 'Authoritative protected-evidence inventory';
			evidenceInventory.appendChild(heading);
			var custody = document.createElement('p');
			custody.textContent = 'Inventory access mode: ' + evidenceCustodyMode + ' custody.';
			evidenceInventory.appendChild(custody);
			if (evidenceRecords.length) {
				var inventoryList = document.createElement('ul');
				evidenceRecords.forEach(function (record) {
					var item = document.createElement('li');
					item.textContent = record.retentionState === 'upload_abandoned' ? 'Upload record ' + record.attachmentId + ' · upload_abandoned · terminal read-only. No evidence bytes were retained. Start a new upload with a new Idempotency-Key.' : 'Attachment ' + record.attachmentId + ' · ' + record.mimeType + ' · ' + record.retentionState + ' · hold ' + (record.legalHold ? 'yes' : 'no') + ' · references ' + record.referenceCounts.total + ' · ' + record.custodyMode + ' custody for original owner user ' + record.originalOwnerUserId;
					inventoryList.appendChild(item);
				});
				evidenceInventory.appendChild(inventoryList);
			} else {
				var empty = document.createElement('p');
				empty.textContent = 'No active retained or pending protected evidence.';
				evidenceInventory.appendChild(empty);
			}
		}
		if (evidenceSelect) {
			evidenceSelect.textContent = '';
			var placeholder = document.createElement('option');
			placeholder.value = '';
			placeholder.textContent = 'Choose protected evidence';
			evidenceSelect.appendChild(placeholder);
			evidenceRecords.filter(function (record) { return record.retentionState !== 'upload_abandoned'; }).forEach(function (record) {
				var option = document.createElement('option');
				option.value = String(record.attachmentId);
				option.textContent = 'Attachment ' + record.attachmentId + ' · ' + record.retentionState + ' · ' + record.custodyMode;
				evidenceSelect.appendChild(option);
			});
			evidenceSelect.disabled = !evidenceRecords.some(function (record) { return record.retentionState !== 'upload_abandoned'; });
		}
		var selected = null;
		var preferred = Number(preferredAttachmentId);
		if (Number.isSafeInteger(preferred) && preferred > 0) {
			selected = evidenceRecords.find(function (record) { return record.attachmentId === preferred && record.retentionState !== 'upload_abandoned'; }) || null;
		}
		if (!selected && evidenceRecords.length) selected = evidenceRecords.find(function (record) { return record.retentionState !== 'upload_abandoned'; }) || null;
		lastEvidence = selected;
		if (evidenceSelect) evidenceSelect.value = selected ? String(selected.attachmentId) : '';
		if (evidenceReload) evidenceReload.disabled = !(cfg.canEvidence && current);
		updateEvidenceControls();
	}

	function loadEvidenceInventory(campaignId, preferredAttachmentId) {
		if (!cfg.canEvidence || !current || current.campaignId !== campaignId) {
			resetEvidenceInventory('Protected evidence is unavailable for this account or campaign.');
			return Promise.resolve(null);
		}
		lastEvidence = null;
		updateEvidenceControls();
		if (evidenceStatus) evidenceStatus.textContent = 'Loading authoritative protected-evidence inventory…';
		return request(cfg.endpoint + '/' + encodeURIComponent(campaignId) + '/evidence').then(function (data) {
			if (!current || current.campaignId !== campaignId) throw new Error('Campaign changed before evidence inventory completed.');
			var verified = validateEvidenceInventory(data, campaignId);
			evidenceCampaignId = campaignId;
			evidenceCustodyMode = verified.custodyMode;
			evidenceRecords = verified.records;
			renderEvidenceInventory(preferredAttachmentId);
			return data;
		});
	}

	function refreshEvidenceInventory(campaignId, preferredAttachmentId) {
		return loadEvidenceInventory(campaignId, preferredAttachmentId).catch(function (error) {
			resetEvidenceInventory('Evidence inventory failed closed. No evidence action is available.');
			note(error.message, true);
			return null;
		});
	}

	function open(id) {
		var campaignId = text(id);
		current = null;
		resetEvidenceInventory('Select a campaign to load authoritative protected evidence.');
		return request(cfg.endpoint + '/' + encodeURIComponent(campaignId)).then(function (data) {
			if (!data || !data.campaign || data.campaign.campaignId !== campaignId) throw new Error('Campaign detail failed its identity contract.');
			current = data.campaign;
			paintDetail(data);
			form.campaignId.value = current.campaignId;
			form.name.value = current.name;
			form.locationId.value = current.locationId;
			form.primaryChannel.value = current.primaryChannel;
			form.campaignJson.value = JSON.stringify(current, null, 2);
			note('Loaded ' + current.name + ' / נטען');
			return cfg.canEvidence ? refreshEvidenceInventory(campaignId) : null;
		}).catch(function (error) {
			current = null;
			resetEvidenceInventory('Campaign evidence is unavailable.');
			note(error.message, true);
			return null;
		});
	}

	function nonnegativeInteger(raw, label) {
		var text = raw === null ? '' : String(raw).trim();
		var value = Number(text);
		if (text === '' || !Number.isSafeInteger(value) || value < 0) throw new Error(label + ' must be a non-negative whole number.');
		return value;
	}

	function positiveInteger(raw, label) {
		var value = nonnegativeInteger(raw, label);
		if (value < 1) throw new Error(label + ' must be a positive whole number.');
		return value;
	}

	function finiteNumber(raw, label) {
		var text = raw === null ? '' : String(raw).trim();
		var value = Number(text);
		if (text === '' || !Number.isFinite(value)) throw new Error(label + ' must be a finite number.');
		return value;
	}

	function bodyFromForm() {
		var body = {};
		try { body = JSON.parse(form.campaignJson.value || '{}'); } catch (error) { throw new Error('Campaign JSON is invalid.'); }
		body.name = form.name.value;
		body.locationId = nonnegativeInteger(form.locationId.value, 'Location ID');
		body.primaryChannel = form.primaryChannel.value;
		return body;
	}

	form.addEventListener('submit', function (event) {
		event.preventDefault();
		if (!cfg.canManage || inFlight) return;
		var campaign;
		try { campaign = bodyFromForm(); } catch (error) { note(error.message, true); return; }
		var editing = !!form.campaignId.value;
		var payload = editing ? {expectedVersion:current.runtime.version,campaign:campaign} : {campaign:campaign};
		var intent = 'save|' + (editing ? form.campaignId.value : 'new') + '|' + JSON.stringify(payload);
		request(editing ? cfg.endpoint + '/' + encodeURIComponent(form.campaignId.value) : cfg.endpoint, {
			method: editing ? 'PATCH' : 'POST',
			headers: {'Idempotency-Key':intentKey(intent)},
			body: JSON.stringify(payload)
		}).then(function (data) {
			finishIntent(intent);
			current = data.campaign.campaign;
			form.campaignId.value = current.campaignId;
			form.campaignJson.value = JSON.stringify(current, null, 2);
			note(cfg.messages.saved);
			return open(current.campaignId).then(load);
		}).catch(function (error) { note(error.message, true); });
	});

	function promptText(label, defaultValue) {
		var value = window.prompt(label, defaultValue || '');
		return value === null ? null : value;
	}

	function requiredBoundedPrompt(label, maximum, fieldName) {
		var value = promptText(label, '');
		if (value === null) return null;
		value = value.trim();
		if (!value || utf8Length(value) > maximum) {
			note(fieldName + ' is required and must be at most ' + maximum + ' UTF-8 bytes.', true);
			return null;
		}
		return value;
	}

	function requiredReason(label) {
		return requiredBoundedPrompt(label, moderationReasonMaximum, 'An operator reason');
	}

	if (packageCopy) packageCopy.addEventListener('click', function () {
		if (!lastPackageArtifact || inFlight) return;
		navigator.clipboard.writeText(lastPackageArtifact).then(function () { note('Exact persisted package copied. No publication was performed.'); }).catch(function () { note('Package copy failed.', true); });
	});

	if (packageDownload) packageDownload.addEventListener('click', function () {
		if (!lastPackageArtifact || inFlight) return;
		var url = URL.createObjectURL(new Blob([lastPackageArtifact], {type:'application/json'}));
		var anchor = document.createElement('a');
		anchor.href = url;
		anchor.download = (lastPackage || 'complete99-package') + '.json';
		document.body.appendChild(anchor);
		anchor.click();
		anchor.remove();
		URL.revokeObjectURL(url);
		note('Exact persisted package downloaded. No publication was performed.');
	});

	if (evidenceSelect) evidenceSelect.addEventListener('change', function () {
		var attachmentId;
		try { attachmentId = positiveInteger(evidenceSelect.value, 'Evidence attachment ID'); } catch (error) { lastEvidence = null; updateEvidenceControls(); return; }
		lastEvidence = evidenceCampaignId && current && evidenceCampaignId === current.campaignId ? evidenceRecords.find(function (record) { return record.attachmentId === attachmentId && record.retentionState !== 'upload_abandoned'; }) || null : null;
		updateEvidenceControls();
	});

	if (evidenceReload) evidenceReload.addEventListener('click', function () {
		if (!cfg.canEvidence || !current || inFlight) return;
		var preferred = lastEvidence ? lastEvidence.attachmentId : 0;
		refreshEvidenceInventory(current.campaignId, preferred);
	});

	if (evidenceUpload) evidenceUpload.addEventListener('click', function () {
		if (!cfg.canEvidence || !current || inFlight || !evidenceFile || !evidenceFile.files || evidenceFile.files.length !== 1) { note('Choose one protected proof file first.', true); return; }
		var campaignId = current.campaignId;
		var file = evidenceFile.files[0];
		var intent = 'evidence|' + campaignId + '|' + file.name + '|' + file.size + '|' + file.lastModified;
		var formData = new FormData();
		formData.append('proof', file, file.name);
		request(cfg.endpoint + '/' + encodeURIComponent(campaignId) + '/evidence-upload', {method:'POST',headers:{'Idempotency-Key':intentKey(intent)},body:formData}).then(function (data) {
			finishIntent(intent);
			var preferred = exactInteger(data.attachmentId, 1) ? data.attachmentId : 0;
			note('Protected proof stored; refreshing authoritative custody and retention truth.');
			return refreshEvidenceInventory(campaignId, preferred);
		}).catch(function (error) {
			note(error.message, true);
			if (error.storedOrPending) return refreshEvidenceInventory(campaignId, exactInteger(error.details.attachmentId, 1) ? error.details.attachmentId : 0);
			return null;
		});
	});

	if (evidenceDispose) evidenceDispose.addEventListener('click', function () {
		if (!current || !lastEvidence || inFlight || lastEvidence.eligibleToDispose !== true || lastEvidence.canDispose !== true) return;
		var campaignId = current.campaignId;
		var record = lastEvidence;
		var reason = requiredReason('Reason for disposing this eligible protected proof');
		if (reason === null || !window.confirm('Record this irreversible protected-evidence disposition command?')) return;
		var payload = {attachmentId:record.attachmentId,reason:reason};
		var intent = 'evidence-dispose|' + campaignId + '|' + record.attachmentId + '|' + record.sha256 + '|' + reason;
		request(cfg.endpoint + '/' + encodeURIComponent(campaignId) + '/evidence/' + record.attachmentId + '/dispose', {method:'POST',headers:{'Idempotency-Key':intentKey(intent)},body:JSON.stringify(payload)}).then(function () {
			finishIntent(intent);
			note('Disposition command recorded; refreshing authoritative evidence state.');
			return refreshEvidenceInventory(campaignId, record.attachmentId);
		}).catch(function (error) {
			note(error.message, true);
			return error.storedOrPending ? refreshEvidenceInventory(campaignId, record.attachmentId) : null;
		});
	});

	function changeEvidenceLegalHold(legalHold) {
		if (!current || !lastEvidence || inFlight || !cfg.canEvidence || !cfg.canApprove) return;
		var record = lastEvidence;
		var allowed = legalHold === true ? record.canSetLegalHold === true : legalHold === false && record.canReleaseLegalHold === true;
		if (!allowed) return;
		var campaignId = current.campaignId;
		var reason = requiredReason(legalHold === true ? 'Reason for setting legal hold' : 'Reason for releasing legal hold');
		if (reason === null || !window.confirm(legalHold === true ? 'Set legal hold on this protected proof?' : 'Release legal hold from this protected proof?')) return;
		var payload = {attachmentId:record.attachmentId,legalHold:legalHold,reason:reason};
		var intent = 'evidence-legal-hold|' + campaignId + '|' + record.attachmentId + '|' + legalHold + '|' + reason;
		request(cfg.endpoint + '/' + encodeURIComponent(campaignId) + '/evidence/' + record.attachmentId + '/legal-hold', {method:'POST',headers:{'Idempotency-Key':intentKey(intent)},body:JSON.stringify(payload)}).then(function () {
			finishIntent(intent);
			note(legalHold === true ? 'Legal hold recorded; refreshing evidence truth.' : 'Legal hold release recorded; refreshing evidence truth.');
			return refreshEvidenceInventory(campaignId, record.attachmentId);
		}).catch(function (error) {
			note(error.message, true);
			return error.storedOrPending ? refreshEvidenceInventory(campaignId, record.attachmentId) : null;
		});
	}

	if (evidenceHoldSet) evidenceHoldSet.addEventListener('click', function () { changeEvidenceLegalHold(true); });
	if (evidenceHoldRelease) evidenceHoldRelease.addEventListener('click', function () { changeEvidenceLegalHold(false); });

	root.addEventListener('click', function (event) {
		var button = event.target.closest('[data-c99-moderation-action]');
		if (!button || !current || inFlight || !cfg.canModerate) return;
		var campaignId = current.campaignId;
		var action = button.dataset.c99ModerationAction;
		if (['resolve', 'escalate', 'outcome'].indexOf(action) < 0) return;
		var payload;
		try {
			payload = {expectedVersion:current.runtime.version,issueId:button.dataset.issueId,issueExpectedVersion:positiveInteger(button.dataset.issueVersion,'Issue version')};
		} catch (error) { note(error.message, true); return; }
		var reason = requiredReason('Reason for this moderation ' + action + ' action');
		if (reason === null) return;
		payload.reason = reason;
		if (action === 'outcome') {
			payload.outcome = requiredBoundedPrompt('Human-attested final outcome statement', moderationOutcomeMaximum, 'A final outcome statement');
			if (payload.outcome === null) return;
		}
		if (!window.confirm(cfg.messages.confirmation)) return;
		var intent = 'moderation|' + campaignId + '|' + action + '|' + JSON.stringify(payload);
		request(cfg.endpoint + '/' + encodeURIComponent(campaignId) + '/moderation-issues/' + encodeURIComponent(payload.issueId) + '/' + action, {method:'POST',headers:{'Idempotency-Key':intentKey(intent)},body:JSON.stringify(payload)}).then(function () {
			finishIntent(intent);
			note('Moderation transition audit-receipted.');
			return open(campaignId).then(load);
		}).catch(function (error) {
			note(error.message, true);
			return error.storedOrPending ? open(campaignId).then(load) : null;
		});
	});

	root.addEventListener('click', function (event) {
		var button = event.target.closest('[data-c99-action]');
		if (!button || !current || inFlight) return;
		var campaignId = current.campaignId;
		var action = button.dataset.c99Action;
		var payload = {expectedVersion:current.runtime.version};
		try {
			if (action === 'prepare-package') payload.channel = current.primaryChannel;
			if (action === 'schedule') { payload.packageId = lastPackage || promptText('Prepared package ID'); if (!payload.packageId) return; }
			if (action === 'provider-receipts') {
				payload.packageDigest = lastPackageDigest || promptText('Prepared package SHA-256');
				payload.outcome = 'human_attested';
				if (lastEvidence) { payload.proofAttachmentId = lastEvidence.attachmentId; payload.proofSha256 = lastEvidence.sha256; } else { payload.proofUrl = promptText('Exact HTTPS proof URL'); }
			}
			if (action === 'results') {
				var observedRaw = promptText('Observed numeric value', '0');
				if (observedRaw === null) return;
				payload.metricKey = current.governance.successMetric;
				payload.value = finiteNumber(observedRaw, 'Observed value');
				payload.unit = promptText('Unit', 'count');
				if (lastEvidence) { payload.proofAttachmentId = lastEvidence.attachmentId; payload.proofSha256 = lastEvidence.sha256; } else { payload.proofUrl = promptText('Exact HTTPS evidence URL'); }
			}
			if (action === 'moderation-issues') {
				payload.title = promptText('Issue title');
				payload.summary = promptText('Issue summary');
				payload.severity = promptText('Severity: low, normal, high, critical', 'normal');
				var assigneeRaw = promptText('Assignee WordPress user ID', String(current.governance.moderationOwnerUserId));
				if (assigneeRaw === null) return;
				payload.assigneeUserId = positiveInteger(assigneeRaw, 'Assignee WordPress user ID');
				payload.slaDueAt = promptText('SLA due ISO-8601');
			}
		} catch (error) { note(error.message, true); return; }
		if (Object.keys(payload).some(function (key) { return payload[key] === null; })) return;
		if (!window.confirm(cfg.messages.confirmation)) return;
		var intent = 'action|' + campaignId + '|' + action + '|' + JSON.stringify(payload);
		request(cfg.endpoint + '/' + encodeURIComponent(campaignId) + '/' + action, {
			method: 'POST',
			headers: {'Idempotency-Key':intentKey(intent)},
			body: JSON.stringify(payload)
		}).then(function (data) {
			finishIntent(intent);
			if (data.package) { lastPackage = data.package.packageId; lastPackageDigest = data.package.sha256; }
			if (data.campaign) { current = data.campaign.campaign; form.campaignJson.value = JSON.stringify(current, null, 2); }
			note('Action recorded / הפעולה נרשמה');
			return open(campaignId).then(load);
		}).catch(function (error) {
			note(error.message, true);
			return error.storedOrPending ? open(campaignId).then(load) : null;
		});
	});

	if (searchForm) searchForm.addEventListener('submit', function (event) {
		event.preventDefault();
		if (inFlight) return;
		load(1, searchInput ? searchInput.value : '');
	});
	if (previousPage) previousPage.addEventListener('click', function () {
		if (!inFlight && listPage > 1) load(listPage - 1);
	});
	if (nextPage) nextPage.addEventListener('click', function () {
		if (!inFlight && nextPage.disabled !== true) load(listPage + 1);
	});

	Array.prototype.forEach.call(root.querySelectorAll('[data-c99-action]'), function (button) {
		var action = button.dataset.c99Action;
		button.hidden = (action === 'approve' && !cfg.canApprove) || (['prepare-package','schedule','cancel'].indexOf(action) >= 0 && !cfg.canSchedule) || (action === 'provider-receipts' && !cfg.canEvidence) || (action === 'results' && !cfg.canResults) || (action === 'moderation-issues' && !cfg.canModerate);
	});
	resetEvidenceInventory('Select a campaign to load authoritative protected evidence.');
	load();
})();
