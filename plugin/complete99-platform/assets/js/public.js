(function () {
	'use strict';

	var toggle = document.querySelector('.c99-menu-toggle');
	var nav = document.getElementById('c99-primary-nav');
	if (!toggle || !nav) {
		return;
	}

	function setOpen(open) {
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		nav.classList.toggle('is-open', open);
	}

	toggle.addEventListener('click', function () {
		setOpen(toggle.getAttribute('aria-expanded') !== 'true');
	});

	nav.addEventListener('click', function (event) {
		if (event.target.closest('a')) {
			setOpen(false);
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			setOpen(false);
			toggle.focus();
		}
	});
}());
