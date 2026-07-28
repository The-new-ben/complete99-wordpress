from __future__ import annotations

import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "plugin" / "complete99-platform"


class AccessibleFrontendContracts(unittest.TestCase):
    def test_english_pages_remove_global_rtl_and_declare_direction(self) -> None:
        frontend = (
            PLUGIN / "includes" / "class-complete99-frontend.php"
        ).read_text(encoding="utf-8")
        template = (PLUGIN / "templates" / "public-shell.php").read_text(
            encoding="utf-8"
        )
        css = (PLUGIN / "assets" / "css" / "public.css").read_text(
            encoding="utf-8"
        )

        self.assertIn("array_diff( $classes, array( 'rtl' ) )", frontend)
        self.assertIn("'complete99-ltr'", frontend)
        self.assertIn("'complete99-rtl'", frontend)
        self.assertIn('dir="<?php echo esc_attr( $complete99_dir ); ?>"', template)
        self.assertIn('html[dir="ltr"] body.complete99-public', css)
        self.assertIn('html[dir="rtl"] body.complete99-public', css)

    def test_mobile_navigation_is_progressively_enhanced(self) -> None:
        css = (PLUGIN / "assets" / "css" / "public.css").read_text(
            encoding="utf-8"
        )
        script = (PLUGIN / "assets" / "js" / "public.js").read_text(
            encoding="utf-8"
        )

        self.assertIn("document.documentElement.classList.add('c99-js')", script)
        template = (PLUGIN / "templates" / "public-shell.php").read_text(
            encoding="utf-8"
        )
        self.assertIn(
            "<script>document.documentElement.classList.add('c99-js');</script>",
            template,
        )
        self.assertIn("html.c99-js .c99-menu-toggle", css)
        self.assertIn("html.c99-js .c99-primary-nav", css)
        self.assertIn("html.c99-js .c99-primary-nav.is-open", css)


if __name__ == "__main__":
    unittest.main()
