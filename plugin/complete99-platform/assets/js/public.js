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
		if (!open) {
			closeMegaMenus();
		}
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
		var openMega = megaToggles.find(function (button) {
			return button.getAttribute('aria-expanded') === 'true';
		});
		var openGroup = openMega ? openMega.closest('.c99-nav-group') : null;
		if (openGroup && !openGroup.contains(event.target)) {
			setMegaOpen(openMega, false);
		}
	});

	document.addEventListener('keydown', function (event) {
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
