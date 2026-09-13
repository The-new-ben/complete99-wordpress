<?php
/** Local-only browser test router. Simulated responses; no leads, mail or database. */
if (PHP_SAPI !== 'cli-server' || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(404); exit; }
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$assets = [
    '/editorial-release/plugin/assets/group-enquiry.js',
    '/editorial-release/plugin/assets/site-editorial.css',
    '/plugin/complete99-platform/assets/css/public.css',
    '/plugin/complete99-platform/assets/css/consumer.css',
];
if (in_array($path, $assets, true)) { return false; }
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; form-action 'self'; connect-src 'self'; img-src 'none'");
if (in_array($path, ['/mobile/he/', '/mobile/en/'], true) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $locale = $path === '/mobile/en/' ? 'en' : 'he';
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Local 390px enquiry preview</title><body><p>Local responsive test. Simulated responses only.</p><iframe title="390px enquiry" src="/preview/' . $locale . '/" style="width:390px;height:850px;border:0"></iframe></body></html>';
    exit;
}
if ($path === '/wp-admin/admin-post.php' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $status = (string) ($_POST['_test_reply'] ?? '400');
    if ($status === '200') {
        $language = ($_POST['language'] ?? '') === 'en' ? 'en' : 'he';
        header('Location: /preview/' . $language . '/?c99_sent=1', true, 303);
    } else {
        http_response_code(in_array($status, ['400', '403', '429', '500'], true) ? (int) $status : 400);
        echo 'Local simulated error. Nothing stored.';
    }
    exit;
}
if (!in_array($path, ['/preview/he/', '/preview/en/'], true) || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') { http_response_code(404); exit; }
ob_start();
require __DIR__ . '/lead-form-contract.php';
$html = ob_get_clean();
$picker = '<label>Local simulated response<select name="_test_reply"><option value="400">Invalid fields</option><option value="403">Expired form</option><option value="429">Rate limit</option><option value="500">Storage error</option><option value="200">Success without storage</option></select></label>';
$html = str_replace('</form>', $picker . '</form>', $html);
$html = str_replace('</head>', '<link rel="stylesheet" href="/editorial-release/plugin/assets/site-editorial.css"></head>', $html);
echo str_replace('</body>', '<script src="/editorial-release/plugin/assets/group-enquiry.js"></script></body>', $html);
