(function () {
	'use strict';

	var ROOT_SELECTOR = '.c99-public-campaign';
	var CONSENT_BASIS = 'public_contextual';
	var MEASUREMENT_MODE = 'explicit_opt_in';
	var MEASUREMENT_PURPOSE = 'campaign_banner_interactions_v1';
	var CHOICE_SCHEMA = 'complete99-campaign-measurement-choice/v2';
	var CHOICE_KEY_PREFIX = 'complete99_campaign_measurement_choice_v2:';
	var EVENT_KEY_PREFIX = 'complete99_campaign_impression_event_v2:';
	var GRANTED_CHOICE = '{"schemaVersion":"' + CHOICE_SCHEMA + '","decision":"granted","purpose":"' + MEASUREMENT_PURPOSE + '"}';
	var DENIED_CHOICE = '{"schemaVersion":"' + CHOICE_SCHEMA + '","decision":"denied","purpose":"' + MEASUREMENT_PURPOSE + '"}';
	var PLACEMENT_PATTERN = /^plc_[a-f0-9]{48}$/;
	var DIGEST_PATTERN = /^[a-f0-9]{64}$/;
	var EVENT_ID_PATTERN = /^[A-Za-z0-9_-]{20,80}$/;
	var ALLOWED_EVENTS = {
		impression: true,
		click: true
	};
	var STATUS_ATTRIBUTES = {
		undecided: 'data-c99-status-undecided',
		granted: 'data-c99-status-granted',
		denied: 'data-c99-status-denied',
		'privacy-signal': 'data-c99-status-privacy-signal',
		error: 'data-c99-status-error',
		disabled: 'data-c99-status-unavailable'
	};

	if (typeof window === 'undefined' || typeof document === 'undefined') {
		return;
	}

	var placements = document.querySelectorAll(ROOT_SELECTOR);
	if (placements.length !== 1) {
		for (var placementIndex = 0; placementIndex < placements.length; placementIndex += 1) {
			makePlacementInert(placements[placementIndex], 'disabled');
		}
		return;
	}

	var box = placements[0];
	var grantControl = box.querySelector('[data-c99-measurement-grant]');
	var declineControl = box.querySelector('[data-c99-measurement-decline]');
	var changeControl = box.querySelector('[data-c99-measurement-change]');
	var statusControl = box.querySelector('[data-c99-measurement-status]');
	var initialControlsWereInert = controlsStartInert();
	var initialContract = readContract();
	var state = 'disabled';
	var measurementEnabled = false;
	var activeContract = null;
	var sessionEventId = '';
	var choiceListenersAttached = false;
	var clickListenerAttached = false;

	function blockControl(control) {
		if (!control) {
			return;
		}
		control.hidden = true;
		control.disabled = true;
		control.setAttribute('aria-disabled', 'true');
		control.setAttribute('tabindex', '-1');
	}

	function enableControl(control) {
		if (!control) {
			return;
		}
		control.hidden = false;
		control.disabled = false;
		control.removeAttribute('aria-disabled');
		control.removeAttribute('tabindex');
	}

	function makePlacementInert(placement, nextState) {
		blockControl(placement.querySelector('[data-c99-measurement-grant]'));
		blockControl(placement.querySelector('[data-c99-measurement-decline]'));
		blockControl(placement.querySelector('[data-c99-measurement-change]'));
		placement.setAttribute('data-c99-measurement-state', nextState);
		var status = placement.querySelector('[data-c99-measurement-status]');
		if (status) {
			var messageAttribute = STATUS_ATTRIBUTES[nextState] || STATUS_ATTRIBUTES.disabled;
			status.textContent = status.getAttribute(messageAttribute) || status.textContent;
		}
	}

	function controlsStartInert() {
		return !!grantControl && grantControl.hidden === true && grantControl.disabled === true &&
			!!declineControl && declineControl.hidden === true && declineControl.disabled === true &&
			!!changeControl && changeControl.hidden === true && changeControl.disabled === true;
	}

	function setState(nextState) {
		state = nextState;
		box.setAttribute('data-c99-measurement-state', nextState);
		if (statusControl) {
			var messageAttribute = STATUS_ATTRIBUTES[nextState] || STATUS_ATTRIBUTES.disabled;
			statusControl.textContent = statusControl.getAttribute(messageAttribute) || statusControl.getAttribute(STATUS_ATTRIBUTES.disabled) || statusControl.textContent;
		}
	}

	function showUndecided() {
		measurementEnabled = false;
		activeContract = null;
		sessionEventId = '';
		setState('undecided');
		enableControl(grantControl);
		enableControl(declineControl);
		blockControl(changeControl);
	}

	function showGranted() {
		setState('granted');
		blockControl(grantControl);
		blockControl(declineControl);
		enableControl(changeControl);
	}

	function showDenied() {
		measurementEnabled = false;
		activeContract = null;
		sessionEventId = '';
		setState('denied');
		blockControl(grantControl);
		blockControl(declineControl);
		enableControl(changeControl);
	}

	function showDisabled() {
		measurementEnabled = false;
		activeContract = null;
		sessionEventId = '';
		setState('disabled');
		blockControl(grantControl);
		blockControl(declineControl);
		blockControl(changeControl);
	}

	function showError(allowChange) {
		measurementEnabled = false;
		activeContract = null;
		sessionEventId = '';
		setState('error');
		blockControl(grantControl);
		blockControl(declineControl);
		if (allowChange) {
			enableControl(changeControl);
		} else {
			blockControl(changeControl);
		}
	}

	function readContract() {
		var grant = box.querySelector('[data-c99-measurement-grant]');
		var decline = box.querySelector('[data-c99-measurement-decline]');
		var change = box.querySelector('[data-c99-measurement-change]');
		var status = box.querySelector('[data-c99-measurement-status]');
		var placementId = box.getAttribute('data-c99-placement-id');
		var publicDigest = box.getAttribute('data-c99-public-digest');
		var endpoint = box.getAttribute('data-c99-campaign-event-endpoint');
		var token = box.getAttribute('data-c99-campaign-event-token');
		var scope = box.getAttribute('data-c99-measurement-scope');

		if (!grant || !decline || !change || !status || grant === decline || grant === change || decline === change ||
			status.getAttribute('role') !== 'status' || status.getAttribute('aria-live') !== 'polite' ||
			!status.getAttribute(STATUS_ATTRIBUTES.disabled) || !status.getAttribute(STATUS_ATTRIBUTES.undecided) ||
			!status.getAttribute(STATUS_ATTRIBUTES.granted) || !status.getAttribute(STATUS_ATTRIBUTES.denied) ||
			!status.getAttribute(STATUS_ATTRIBUTES['privacy-signal']) || !status.getAttribute(STATUS_ATTRIBUTES.error) ||
			box.getAttribute('data-c99-consent-basis') !== CONSENT_BASIS ||
			box.getAttribute('data-c99-measurement-mode') !== MEASUREMENT_MODE ||
			box.getAttribute('data-c99-measurement-purpose') !== MEASUREMENT_PURPOSE || scope !== token ||
			!PLACEMENT_PATTERN.test(placementId || '') || !DIGEST_PATTERN.test(publicDigest || '') ||
			!DIGEST_PATTERN.test(token || '') || typeof endpoint !== 'string' ||
			typeof window.URL !== 'function' || !window.location || !window.location.href ||
			typeof window.fetch !== 'function') {
			return null;
		}

		try {
			var pageUrl = new window.URL(window.location.href);
			var eventUrl = new window.URL(endpoint, pageUrl.href);
			var exactPath = '/wp-json/complete99/v1/campaign-events/' + token;
			if (eventUrl.origin !== pageUrl.origin || eventUrl.pathname !== exactPath || eventUrl.search ||
				eventUrl.hash || eventUrl.username || eventUrl.password) {
				return null;
			}
			return {
				endpoint: eventUrl.href,
				token: token,
				scope: scope,
				purpose: MEASUREMENT_PURPOSE,
				placementId: placementId,
				publicDigest: publicDigest
			};
		} catch (error) {
			return null;
		}
	}

	function contractsMatch(first, second) {
		return !!first && !!second && first.endpoint === second.endpoint && first.token === second.token &&
			first.scope === second.scope && first.purpose === second.purpose &&
			first.placementId === second.placementId && first.publicDigest === second.publicDigest;
	}

	function doNotTrackEnabled(value) {
		if (typeof value !== 'string' && typeof value !== 'number') {
			return false;
		}
		var normalized = String(value).toLowerCase().replace(/^\s+|\s+$/g, '');
		return normalized === '1' || normalized === 'yes';
	}

	function privacySignalEnabled() {
		var browser = window.navigator || {};
		return browser.globalPrivacyControl === true ||
			doNotTrackEnabled(browser.doNotTrack) ||
			doNotTrackEnabled(browser.msDoNotTrack) ||
			doNotTrackEnabled(window.doNotTrack);
	}

	function choiceKey(contract) {
		return CHOICE_KEY_PREFIX + contract.purpose + ':' + contract.scope;
	}

	function eventKey(contract) {
		return EVENT_KEY_PREFIX + contract.token;
	}

	function readSessionValue(key) {
		try {
			return { ok: true, value: window.sessionStorage.getItem(key) };
		} catch (error) {
			return { ok: false, value: null };
		}
	}

	function setSessionValue(key, value) {
		try {
			window.sessionStorage.setItem(key, value);
			return window.sessionStorage.getItem(key) === value;
		} catch (error) {
			return false;
		}
	}

	function removeSessionValue(key) {
		try {
			window.sessionStorage.removeItem(key);
			return window.sessionStorage.getItem(key) === null;
		} catch (error) {
			return false;
		}
	}

	function clearScopedSession(contract, clearChoice) {
		if (!contract) {
			return false;
		}
		var eventCleared = removeSessionValue(eventKey(contract));
		var choiceCleared = !clearChoice || removeSessionValue(choiceKey(contract));
		return eventCleared && choiceCleared;
	}

	function enterPrivacyState(contract) {
		measurementEnabled = false;
		activeContract = null;
		sessionEventId = '';
		if (contract) {
			clearScopedSession(contract, true);
		}
		setState('privacy-signal');
		blockControl(grantControl);
		blockControl(declineControl);
		blockControl(changeControl);
	}

	function byteAsHex(value) {
		var encoded = value.toString(16);
		return encoded.length === 1 ? '0' + encoded : encoded;
	}

	function freshEventId() {
		var candidate = '';
		try {
			if (window.crypto && typeof window.crypto.randomUUID === 'function') {
				candidate = window.crypto.randomUUID().replace(/-/g, '');
			} else if (window.crypto && typeof window.crypto.getRandomValues === 'function' && typeof window.Uint8Array === 'function') {
				var bytes = new window.Uint8Array(16);
				window.crypto.getRandomValues(bytes);
				for (var index = 0; index < bytes.length; index += 1) {
					candidate += byteAsHex(bytes[index]);
				}
			}
		} catch (error) {
			return '';
		}
		return EVENT_ID_PATTERN.test(candidate) ? candidate : '';
	}

	function sessionStillAuthorizes(contract) {
		var storedChoice = readSessionValue(choiceKey(contract));
		var storedEvent = readSessionValue(eventKey(contract));
		return storedChoice.ok && storedChoice.value === GRANTED_CHOICE && storedEvent.ok &&
			storedEvent.value === sessionEventId && EVENT_ID_PATTERN.test(storedEvent.value || '');
	}

	function failClosed(contract) {
		measurementEnabled = false;
		activeContract = null;
		sessionEventId = '';
		if (contract) {
			clearScopedSession(contract, true);
		}
		showDisabled();
	}

	function record(eventName, suppliedEventId) {
		if (!measurementEnabled || state !== 'granted' || !ALLOWED_EVENTS[eventName]) {
			return;
		}
		if (privacySignalEnabled()) {
			enterPrivacyState(activeContract || initialContract);
			return;
		}

		var currentContract = readContract();
		if (!contractsMatch(activeContract, currentContract) || !sessionStillAuthorizes(activeContract)) {
			failClosed(activeContract || initialContract);
			return;
		}

		var eventId = suppliedEventId || freshEventId();
		if (!EVENT_ID_PATTERN.test(eventId || '')) {
			failClosed(activeContract);
			return;
		}

		try {
			var request = window.fetch(activeContract.endpoint, {
				method: 'POST',
				mode: 'same-origin',
				credentials: 'omit',
				cache: 'no-store',
				redirect: 'error',
				referrerPolicy: 'no-referrer',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					event: eventName,
					event_id: eventId
				}),
				keepalive: true
			});
			if (request && typeof request.catch === 'function') {
				request.catch(function () {});
			}
		} catch (error) {
			// Optional measurement failure never blocks the campaign destination.
		}
	}

	function attachClickListener() {
		if (clickListenerAttached) {
			return;
		}
		var clickTarget = box.querySelector('[data-c99-campaign-click]');
		if (!clickTarget) {
			return;
		}
		clickListenerAttached = true;
		clickTarget.addEventListener('click', function () {
			record('click');
		});
	}

	function grantMeasurement() {
		if (grantControl.disabled || state !== 'undecided') {
			return;
		}
		if (privacySignalEnabled()) {
			enterPrivacyState(initialContract);
			return;
		}

		var currentContract = readContract();
		if (!contractsMatch(initialContract, currentContract)) {
			failClosed(initialContract);
			return;
		}

		var nextEventId = freshEventId();
		if (!nextEventId || !setSessionValue(choiceKey(currentContract), GRANTED_CHOICE) ||
			!setSessionValue(eventKey(currentContract), nextEventId)) {
			failClosed(currentContract);
			return;
		}

		activeContract = currentContract;
		sessionEventId = nextEventId;
		measurementEnabled = true;
		showGranted();
		attachClickListener();
		// The server retains these consent-gated counts as anonymous_unverified evidence.
		record('impression', nextEventId);
	}

	function revokeMeasurement() {
		var contract = activeContract || initialContract;
		measurementEnabled = false;
		activeContract = null;
		sessionEventId = '';
		showDenied();
		if (!contract) {
			return false;
		}
		var eventCleared = removeSessionValue(eventKey(contract));
		var denialStored = setSessionValue(choiceKey(contract), DENIED_CHOICE);
		if (!eventCleared || !denialStored) {
			showError(true);
			return false;
		}
		return true;
	}

	function declineMeasurement() {
		if (declineControl.disabled || state !== 'undecided') {
			return;
		}
		if (privacySignalEnabled()) {
			enterPrivacyState(initialContract);
			return;
		}
		revokeMeasurement();
	}

	function changeMeasurementChoice() {
		if (changeControl.disabled || (state !== 'granted' && state !== 'denied' && state !== 'error')) {
			return;
		}
		if (privacySignalEnabled()) {
			enterPrivacyState(activeContract || initialContract);
			return;
		}
		if (state === 'granted') {
			if (!revokeMeasurement()) {
				return;
			}
		}
		var choiceRead = readSessionValue(choiceKey(initialContract));
		var eventRead = readSessionValue(eventKey(initialContract));
		if (!choiceRead.ok || !eventRead.ok) {
			showError(false);
			return;
		}
		showUndecided();
	}

	function attachChoiceListeners() {
		if (choiceListenersAttached) {
			return;
		}
		choiceListenersAttached = true;
		grantControl.addEventListener('click', grantMeasurement);
		declineControl.addEventListener('click', declineMeasurement);
		changeControl.addEventListener('click', changeMeasurementChoice);
	}

	function initializeChoice() {
		var storedChoice = readSessionValue(choiceKey(initialContract));
		var storedEvent = readSessionValue(eventKey(initialContract));
		if (!storedChoice.ok || !storedEvent.ok) {
			showError(false);
			return;
		}

		if (storedChoice.value === DENIED_CHOICE) {
			if (storedEvent.value !== null && !removeSessionValue(eventKey(initialContract))) {
				showDisabled();
				return;
			}
			showDenied();
			return;
		}

		// A prior or tampered grant is never authority for this rendered page.
		if ((storedChoice.value !== null && !removeSessionValue(choiceKey(initialContract))) ||
			(storedEvent.value !== null && !removeSessionValue(eventKey(initialContract)))) {
			showDisabled();
			return;
		}
		showUndecided();
	}

	if (!initialControlsWereInert || !initialContract) {
		showDisabled();
		return;
	}
	if (privacySignalEnabled()) {
		enterPrivacyState(initialContract);
		return;
	}

	attachChoiceListeners();
	initializeChoice();
})();
