(function () {
	'use strict';

	document.documentElement.classList.add('c99-js');

	var toggle = document.querySelector('.c99-menu-toggle');
	var nav = document.getElementById('c99-primary-nav');
	var header = document.querySelector('.c99-site-header');
	if (!toggle || !nav) {
		return;
	}

	var megaToggles = Array.prototype.slice.call(nav.querySelectorAll('.c99-mega-toggle'));
	var mobileQuery = window.matchMedia('(max-width: 1180px)');

	function setMenuOpen(open) {
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		nav.classList.toggle('is-open', open);
		document.body.classList.toggle('c99-menu-open', open && mobileQuery.matches);
		if (open && mobileQuery.matches) {
			window.requestAnimationFrame(function () {
				var firstLink = nav.querySelector('a, button:not([disabled])');
				if (firstLink) {
					firstLink.focus();
				}
			});
		}
		if (!open) {
			closeMegaMenus();
		}
	}

	function mobileFocusables() {
		return [toggle].concat(
			Array.prototype.slice.call(
				nav.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')
			).filter(function (element) {
				return !element.hidden && element.offsetParent !== null;
			})
		);
	}

	function panelFor(button) {
		var id = button.getAttribute('aria-controls');
		return id ? document.getElementById(id) : null;
	}

	function setMegaOpen(button, open) {
		var panel = panelFor(button);
		var group = button.closest('.c99-nav-group');
		if (!panel || !group) {
			return;
		}
		button.setAttribute('aria-expanded', open ? 'true' : 'false');
		panel.hidden = !open;
		group.classList.toggle('is-open', open);
	}

	function closeMegaMenus(except) {
		megaToggles.forEach(function (button) {
			if (button !== except) {
				setMegaOpen(button, false);
			}
		});
	}

	toggle.addEventListener('click', function () {
		setMenuOpen(toggle.getAttribute('aria-expanded') !== 'true');
	});

	megaToggles.forEach(function (button) {
		button.addEventListener('click', function () {
			var shouldOpen = button.getAttribute('aria-expanded') !== 'true';
			closeMegaMenus(button);
			setMegaOpen(button, shouldOpen);
		});

		button.addEventListener('keydown', function (event) {
			if (event.key !== 'ArrowDown') {
				return;
			}
			event.preventDefault();
			closeMegaMenus(button);
			setMegaOpen(button, true);
			var panel = panelFor(button);
			var firstLink = panel ? panel.querySelector('a') : null;
			if (firstLink) {
				firstLink.focus();
			}
		});
	});

	nav.addEventListener('click', function (event) {
		if (event.target.closest('a')) {
			closeMegaMenus();
			if (mobileQuery.matches) {
				setMenuOpen(false);
			}
		}
	});

	document.addEventListener('click', function (event) {
		if (header && !header.contains(event.target)) {
			closeMegaMenus();
			if (mobileQuery.matches) {
				setMenuOpen(false);
			}
		}
	});

	document.addEventListener('focusin', function (event) {
		if (mobileQuery.matches && toggle.getAttribute('aria-expanded') === 'true' && header && !header.contains(event.target)) {
			setMenuOpen(false);
			return;
		}
		var openMega = megaToggles.find(function (button) {
			return button.getAttribute('aria-expanded') === 'true';
		});
		var openGroup = openMega ? openMega.closest('.c99-nav-group') : null;
		if (openGroup && !openGroup.contains(event.target)) {
			setMegaOpen(openMega, false);
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Tab' && mobileQuery.matches && toggle.getAttribute('aria-expanded') === 'true') {
			var focusables = mobileFocusables();
			var first = focusables[0];
			var last = focusables[focusables.length - 1];
			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
				return;
			}
			if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
				return;
			}
		}
		if (event.key !== 'Escape') {
			return;
		}
		var openMega = megaToggles.find(function (button) {
			return button.getAttribute('aria-expanded') === 'true';
		});
		if (openMega) {
			event.preventDefault();
			setMegaOpen(openMega, false);
			openMega.focus();
			return;
		}
		if (toggle.getAttribute('aria-expanded') === 'true') {
			event.preventDefault();
			setMenuOpen(false);
			toggle.focus();
		}
	});

	function handleViewportChange() {
		if (!mobileQuery.matches) {
			setMenuOpen(false);
			document.body.classList.remove('c99-menu-open');
		}
	}

	if (typeof mobileQuery.addEventListener === 'function') {
		mobileQuery.addEventListener('change', handleViewportChange);
	} else {
		mobileQuery.addListener(handleViewportChange);
	}
}());

(function () {
	'use strict';

	var shell = document.querySelector('[data-c99-dish-filter]');
	var grid = document.querySelector('[data-c99-dish-grid]');
	if (!shell || !grid) {
		return;
	}

	var buttons = Array.prototype.slice.call(shell.querySelectorAll('[data-c99-filter]'));
	var cards = Array.prototype.slice.call(grid.querySelectorAll('[data-c99-dish-card]'));
	var count = shell.querySelector('[data-c99-filter-count]');
	var empty = document.querySelector('[data-c99-filter-empty]');
	var language = (document.documentElement.lang || 'he').toLowerCase();

	function cardMatches(card, filter) {
		if (filter === 'all') {
			return true;
		}
		var facets = (card.getAttribute('data-c99-facets') || '').split(/\s+/);
		return facets.indexOf(filter) !== -1;
	}

	function announce(total) {
		if (!count) {
			return;
		}
		if (language.indexOf('he') === 0) {
			count.textContent = total === 1 ? 'מנה אחת' : total + ' מנות';
			return;
		}
		count.textContent = total === 1 ? '1 dish' : total + ' dishes';
	}

	function updateAddress(filter) {
		if (!window.history || typeof window.history.replaceState !== 'function') {
			return;
		}
		var url = new URL(window.location.href);
		if (filter === 'all') {
			url.searchParams.delete('dish-style');
		} else {
			url.searchParams.set('dish-style', filter);
		}
		window.history.replaceState({}, '', url.toString());
	}

	function applyFilter(filter, updateUrl) {
		var visible = 0;
		buttons.forEach(function (button) {
			var selected = button.getAttribute('data-c99-filter') === filter;
			button.classList.toggle('is-active', selected);
			button.setAttribute('aria-pressed', selected ? 'true' : 'false');
		});
		cards.forEach(function (card) {
			var matches = cardMatches(card, filter);
			card.hidden = !matches;
			if (matches) {
				visible += 1;
			}
		});
		if (empty) {
			empty.hidden = visible !== 0;
		}
		announce(visible);
		if (updateUrl) {
			updateAddress(filter);
		}
	}

	buttons.forEach(function (button, index) {
		button.addEventListener('click', function () {
			applyFilter(button.getAttribute('data-c99-filter') || 'all', true);
		});
		button.addEventListener('keydown', function (event) {
			var next = index;
			if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
				next = (index + 1) % buttons.length;
			} else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
				next = (index - 1 + buttons.length) % buttons.length;
			} else if (event.key === 'Home') {
				next = 0;
			} else if (event.key === 'End') {
				next = buttons.length - 1;
			} else {
				return;
			}
			event.preventDefault();
			buttons[next].focus();
		});
	});

	var requested = new URL(window.location.href).searchParams.get('dish-style');
	var validRequested = buttons.some(function (button) {
		return button.getAttribute('data-c99-filter') === requested;
	});
	applyFilter(validRequested ? requested : 'all', false);
}());
