// Execute the shipped menu-filter IIFE against a minimal DOM contract.
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync('plugin/complete99-platform/assets/js/public.js', 'utf8');
const marker = source.indexOf("var shell = document.querySelector('[data-c99-dish-filter]')");
assert(marker > 0);
const start = source.lastIndexOf('(function () {', marker);
const end = source.indexOf('}());', marker) + 5;
const script = source.slice(start, end);

function fixture(local = true, language = 'he', enhance = false, href = 'https://complete99.co.il/', filterScript = script) {
  const search = {value: '', addEventListener(event, fn) { this[event] = fn; }};
  const count = {textContent: ''};
  const empty = {hidden: true};
  const cards = [['קובה סלק Beet kubbeh', 'pots meat'], ['קוסקוס Couscous', 'pots vegetarian'], ['סביח Sabich', 'pita vegetarian']].map(([textContent, facets]) => ({textContent, hidden: false, getAttribute() { return facets; }}));
  const buttons = ['all', 'pots', 'pita', 'vegetarian'].map(filter => ({
    selected: false, focused: false,
    classList: {toggle() {}},
    getAttribute() { return filter; },
    setAttribute(key, value) { this.selected = value === 'true'; },
    addEventListener(event, fn) { this[event] = fn; },
    focus() { this.focused = true; }
  }));
  const shell = {
    setAttribute(key) { if (key === 'data-c99-local-search') { local = true; } },
    querySelectorAll() { return buttons; },
    querySelector(selector) { return selector === '[data-c99-menu-search]' ? search : count; },
    hasAttribute() { return local; }
  };
  const urls = [];
  const context = {
    URL, window: {location: {href}, history: {replaceState(a,b,url) { urls.push(url); }}},
    document: {documentElement: {lang: language}, querySelectorAll() {return [shell];}, querySelector(selector) {
      return selector === '[data-c99-dish-filter]' ? shell : selector === '[data-c99-dish-grid]' ? {querySelectorAll() { return cards; }} : empty;
    }}
  };
  if (enhance) { vm.runInNewContext(fs.readFileSync('editorial-release/plugin/assets/discovery-local.js', 'utf8'), context); }
  vm.runInNewContext(filterScript, context);
  return {search, count, empty, cards, buttons, urls};
}
const f = fixture();
assert.equal(f.count.textContent, '3 מנות');
f.search.value = 'קוּבָּה'; f.search.input();
assert.deepEqual(f.cards.map(c=>c.hidden), [false,true,true]);
assert.equal(f.count.textContent, 'מנה אחת');
f.buttons[2].click();
assert(f.empty.hidden === false);
f.search.value = ''; f.search.input();
assert.deepEqual(f.cards.map(c=>c.hidden), [true,true,false]);
f.buttons[0].click();
assert(f.cards.every(c=>!c.hidden));
assert.equal(f.empty.hidden, true);
assert.equal(f.urls.length, 0, 'Homepage interactions must not create query URLs');
f.buttons[0].keydown({key:'End', preventDefault(){}});
assert(f.buttons[3].focused);
const en = fixture(true, 'en');
en.search.value = 'KUBBEH'; en.search.input();
assert.equal(en.count.textContent, '1 dish');
const legacy = fixture(false);
legacy.buttons[1].click();
assert.equal(legacy.urls[0], 'https://complete99.co.il/?dish-style=pots');
const upgraded = fixture(false, 'he', true, 'https://complete99.co.il/dishes/');
upgraded.buttons[1].click();
assert.equal(upgraded.count.textContent, '2 מנות');
assert.equal(upgraded.urls.length, 0, 'Upgraded internal filters keep the clean URL');
const oldLink = fixture(false, 'en', true, 'https://complete99.co.il/en/dishes/?dish-style=pots&utm_source=email#menu');
assert.equal(oldLink.urls[0], '/en/dishes/?utm_source=email#menu');
oldLink.buttons[2].click();
assert.equal(oldLink.urls.length, 1, 'No new history writes after legacy filter cleanup');
const archived = require('node:child_process').execFileSync('python', ['-c', "import importlib.util,sys; s=importlib.util.spec_from_file_location('b','scripts/build-editorial-release.py'); b=importlib.util.module_from_spec(s); s.loader.exec_module(b); sys.stdout.buffer.write(b.legacy_public_script())"], {encoding:'utf8'});
const liveMarker = archived.indexOf("var shell = document.querySelector('[data-c99-dish-filter]')");
const liveScript = archived.slice(archived.lastIndexOf('(function () {',liveMarker),archived.indexOf('}());',liveMarker)+5);
for (const language of ['he','en']) {
  const actual = fixture(false,language,true,'https://complete99.co.il/dishes/',liveScript);
  actual.buttons[1].click();
  assert.deepEqual(actual.cards.map(c=>c.hidden),[false,false,true]);
  assert.equal(actual.urls.length,0,'Actual archived 1.22.1 script must keep URLs clean');
  actual.buttons[0].keydown({key:'End',preventDefault(){}});
  assert(actual.buttons[3].focused);
}
console.log('PASS: Hebrew marks, case-insensitive English, combined filters, empty/reset states, keyboard navigation, clean homepage URL, legacy isolation');
