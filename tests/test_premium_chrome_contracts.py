from __future__ import annotations

import re
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"
FRONTEND = PLUGIN / "includes" / "class-complete99-frontend.php"
CSS = PLUGIN / "assets" / "css" / "public.css"
SCRIPT = PLUGIN / "assets" / "js" / "public.js"


class PremiumChromeContracts(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.frontend = FRONTEND.read_text(encoding="utf-8")
        cls.css = CSS.read_text(encoding="utf-8")
        cls.script = SCRIPT.read_text(encoding="utf-8")

    def test_navigation_exposes_the_governed_hubs_and_two_primary_paths(self) -> None:
        navigation = self.frontend.split(
            "private static function navigation_groups", 1
        )[1].split("private static function navigation_url", 1)[0]
        for key in (
            "services",
            "industries",
            "platform",
            "dishes",
            "ingredients",
            "traditions",
            "knowledge",
            "store",
        ):
            self.assertIn(f"'{key}'", navigation)

        header = self.frontend.split("public static function render_header", 1)[
            1
        ].split("private static function render_language_switch", 1)[0]
        self.assertIn('class="c99-mega-toggle"', header)
        self.assertIn('aria-expanded="false"', header)
        self.assertIn('aria-haspopup="true"', header)
        self.assertIn('class="c99-mega-panel" hidden', header)
        self.assertIn("c99-nav-dishes", header)
        self.assertIn("c99-nav-cta", header)
        self.assertNotIn("BOM", navigation)

    def test_mobile_and_mega_navigation_return_focus_and_close_outside(self) -> None:
        for contract in (
            "aria-expanded",
            "panel.hidden = !open",
            "event.key !== 'Escape'",
            "openMega.focus()",
            "toggle.focus()",
            "!header.contains(event.target)",
            "event.key !== 'ArrowDown'",
            "firstLink.focus()",
            "document.addEventListener('focusin'",
            "!openGroup.contains(event.target)",
        ):
            self.assertIn(contract, self.script)
        self.assertIn("@media (max-width: 1180px)", self.css)
        self.assertIn("html.c99-js .c99-primary-nav.is-open", self.css)
        self.assertIn(".c99-mega-panel[hidden]", self.css)

    def test_focus_type_and_target_sizes_are_explicit(self) -> None:
        self.assertRegex(
            self.css,
            r"\.complete99-public a:focus-visible,[\s\S]+outline: 2px solid "
            r"var\(--c99-focus\)",
        )
        self.assertIn("font-size: 17px;", self.css)
        self.assertGreaterEqual(self.css.count("min-height: 44px"), 4)
        self.assertRegex(
            self.css,
            r"(?s)\.c99-text-link\s*\{[^}]*min-height:\s*44px",
        )
        self.assertRegex(
            self.css,
            r"(?s)\.c99-footer-cluster a\s*\{[^}]*min-width:\s*44px",
        )
        self.assertRegex(
            self.css,
            r"(?s)\.c99-mega-toggle\s*\{[^}]*width:\s*44px;[^}]*min-width:\s*44px",
        )
        self.assertRegex(
            self.css,
            r"(?s)\.c99-brand\s*\{[^}]*min-height:\s*44px",
        )
        self.assertRegex(
            self.css,
            r"(?s)@media\s*\(max-width:\s*560px\).*?\.c99-language-switch\s*\{[^}]*width:\s*44px;[^}]*min-width:\s*44px",
        )
        self.assertRegex(
            self.css,
            r"(?s)\.c99-header-inner\s*\{[^}]*width:\s*min\(1320px,\s*calc\(100%\s*-\s*40px\)\)",
        )

    def test_hubs_have_rich_cards_and_store_language_is_non_transactional(self) -> None:
        self.assertIn("private static function render_hub_experience", self.frontend)
        self.assertIn('class="c99-hub-card"', self.frontend)
        self.assertIn(
            "'services', 'industries', 'platform', 'dishes', 'ingredients', "
            "'traditions', 'knowledge', 'store'",
            self.frontend,
        )
        self.assertIn("Sales, payment and delivery will appear only after", self.frontend)
        self.assertNotIn("Buy now", self.frontend)
        self.assertNotIn("Add to cart", self.frontend)

    def test_operating_layout_is_explicitly_a_capability_preview(self) -> None:
        self.assertIn("תצוגת יכולות", self.frontend)
        self.assertIn("Capability preview", self.frontend)
        self.assertIn(
            "No live location, supplier, camera or campaign data is shown.",
            self.frontend,
        )
        self.assertNotIn("c99-live-dot", self.frontend)
        self.assertNotIn(".c99-live-dot", self.css)

    def test_food_imagery_is_local_responsive_and_presented_normally(self) -> None:
        self.assertIn(
            "assets/images/original/c99-food-house-spread-hero-2021-wp-v01.avif",
            self.frontend,
        )
        self.assertIn('width="1400" height="788"', self.frontend)
        self.assertIn('fetchpriority="high"', self.frontend)
        self.assertIn('loading="lazy"', self.frontend)
        self.assertIn(
            "Overhead spread of beet kubeh, couscous, meatballs, salad and additional dishes",
            self.frontend,
        )
        self.assertNotIn("Complete99 archive food photograph", self.frontend)
        self.assertNotIn("c99-archive-note", self.frontend)
        self.assertNotIn("business-owned", self.frontend.lower())
        self.assertIn("complete99-connected-table-editorial-v1.avif", self.frontend)
        self.assertIn("assets/images/complete99-mark.svg", self.frontend)
        self.assertIn('rel="icon"', self.frontend)
        self.assertNotIn("c99-identity-legacy-logo-square-2021", self.frontend)

    def test_breadcrumb_and_footer_follow_the_visible_hierarchy(self) -> None:
        self.assertIn(
            "Complete99_Content::breadcrumb_trail( $post->ID )", self.frontend
        )
        self.assertIn("'itemListElement' => $breadcrumb_items", self.frontend)
        footer = self.frontend.split("public static function render_footer", 1)[1]
        for key in (
            "services",
            "dishes",
            "knowledge",
            "platform",
            "store",
            "about",
            "contact",
            "privacy",
            "terms",
            "accessibility",
        ):
            self.assertIn(f"'{key}'", footer)
        self.assertNotIn("BOM", footer)
        self.assertIsNone(re.search(r"(?:mailto:|tel:|instagram|facebook)", footer, re.I))


if __name__ == "__main__":
    unittest.main()
