/* Run immediately before the existing footer script. Filters never create URL variants. */
(function () {
  'use strict';
  var shells = document.querySelectorAll('[data-c99-dish-filter]');
  if (!shells.length) { return; }
  shells.forEach(function (shell) { shell.setAttribute('data-c99-local-search', ''); });
  var url = new URL(window.location.href);
  if (url.searchParams.has('dish-style') && window.history && typeof window.history.replaceState === 'function') {
    url.searchParams.delete('dish-style');
    window.history.replaceState(window.history.state, '', url.pathname + url.search + url.hash);
  }
}());
