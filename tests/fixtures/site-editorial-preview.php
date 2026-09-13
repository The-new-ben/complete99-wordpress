<?php
/** Local visual fixture of captured public CMS pages; never accepts submissions. */
if (PHP_SAPI !== 'cli-server') { http_response_code(404); exit; }
header("Content-Security-Policy: form-action 'none'");
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') { http_response_code(405); exit; }
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$pages = ['dishes', 'dish', 'group', 'ingredients'];
if ($path === '/site-editorial.css') {
    header('Content-Type: text/css; charset=utf-8');
    readfile(dirname(__DIR__, 2) . '/editorial-release/plugin/assets/site-editorial.css'); exit;
}
if (preg_match('~^/page/(dishes|dish|group|ingredients)/$~D', $path, $match)) {
    $file = 'C:/Users/777/Downloads/codexmanager/complete99-editorial-internal-preview/' . $match[1] . '.html';
    if (!is_file($file)) { http_response_code(404); exit; }
    $html = file_get_contents($file);
    // Remove analytics and CMS scripts from a local preview; keep only menu/filter behavior.
    $html = preg_replace('~<script\b[^>]*>.*?</script>~is', '', $html);
    $html = str_replace('</head>', '<link rel="stylesheet" href="/site-editorial.css"></head>', $html);
    $html = str_replace('</body>', '<script src="https://complete99.co.il/wp-content/plugins/complete99-editorial-home/assets/public.js"></script></body>', $html);
    echo $html; exit;
}
if (!preg_match('~^/(dishes|dish|group|ingredients)/$~D', $path, $match)) { http_response_code(404); exit; }
?><!doctype html><html lang="he" dir="rtl"><meta charset="utf-8"><title>Complete99 internal-page visual preview</title>
<style>body{margin:0;background:#ddd;font:16px Arial}nav{padding:16px}a{margin:12px}iframe{display:block;border:0;margin:16px;background:white}p{margin:16px}</style>
<nav>בדיקה מקומית של עמודים קיימים עם עיצוב מוצע. לא האתר החי.
<?php foreach ($pages as $page): ?><a href="/<?= $page ?>/"><?= $page ?></a><?php endforeach; ?></nav>
<p>מובייל: רוחב תוכן 390 פיקסלים. טפסים אינם נשלחים מכאן.</p>
<iframe title="Mobile preview" width="390" height="844" src="/page/<?= $match[1] ?>/"></iframe>
<p>מחשב: רוחב תוכן 960 פיקסלים.</p>
<iframe title="Desktop preview" width="960" height="900" src="/page/<?= $match[1] ?>/"></iframe>
</html>
