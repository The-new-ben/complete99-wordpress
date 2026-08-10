(function () {
	'use strict';

	var config = window.Complete99OpsStatus;
	var shell = document.querySelector('[data-c99-ops-shell]');
	if (!shell) {
		return;
	}

	var status = shell.querySelector('[data-c99-ops-status]');
	var label = shell.querySelector('[data-c99-ops-status-label]');
	var detail = shell.querySelector('[data-c99-ops-status-detail]');
	var schema = shell.querySelector('[data-c99-ops-schema-version]');
	var tableCount = shell.querySelector('[data-c99-ops-table-count]');
	var checked = shell.querySelector('[data-c99-ops-checked]');

	function failClosed() {
		if (status) {
			status.classList.remove('is-ready');
			status.classList.add('is-blocked');
		}
		if (label) {
			label.textContent = 'Status unavailable';
		}
		if (detail) {
			detail.textContent = config && config.unavailable
				? config.unavailable
				: 'Private status could not be verified.';
		}
	}

	if (!config || !config.endpoint || !config.nonce || !window.fetch) {
		failClosed();
		return;
	}

	window.fetch(config.endpoint, {
		method: 'GET',
		credentials: 'same-origin',
		headers: {
			'Accept': 'application/json',
			'X-WP-Nonce': config.nonce
		}
	})
		.then(function (response) {
			if (!response.ok) {
				throw new Error('status-request-failed');
			}
			return response.json();
		})
		.then(function (payload) {
			if (!payload || payload.status_schema !== 'complete99-ops-status/v1' || payload.ready !== true) {
				throw new Error('status-contract-invalid');
			}
			if (payload.auth_provider !== 'wordpress' || payload.write_commands_enabled !== false) {
				throw new Error('status-boundary-invalid');
			}

			if (status) {
				status.classList.remove('is-blocked');
				status.classList.add('is-ready');
			}
			if (label) {
				label.textContent = config.ready || 'Foundation ready';
			}
			if (detail) {
				detail.textContent = 'Verified from durable WordPress storage';
			}
			if (schema) {
				schema.textContent = payload.ops_schema_version || '';
			}
			if (tableCount && payload.schema) {
				tableCount.textContent = String(payload.schema.present_table_count) + ' / ' + String(payload.schema.required_table_count);
			}
			if (checked) {
				checked.textContent = config.checked || 'Verified just now';
			}
		})
		.catch(failClosed);
}());
