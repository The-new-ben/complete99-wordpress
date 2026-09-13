<?php
/** Local-only rendering fixture for the actual homepage method, not WordPress. */
if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__, 2);
$request_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (PHP_SAPI === 'cli-server') {
    if (preg_match('~^/plugin/complete99-platform/assets/(css|js|images)/[a-zA-Z0-9/_.-]+$~D', $request_path)
        && !str_contains($request_path, '..')) {
        return false;
    }
    if (!in_array($request_path, ['/preview/he/', '/preview/en/'], true)) {
        http_response_code(404);
        exit('Local preview route not found.');
    }
}
define('ABSPATH', $root . '/');
define('COMPLETE99_PLATFORM_DIR', $root . '/plugin/complete99-platform/');
define('COMPLETE99_PLATFORM_URL', '/plugin/complete99-platform/');
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_attr($value) { return esc_html($value); }
function esc_url($value) { return esc_html($value); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower($value)); }
function sanitize_file_name($value) { return basename($value); }
function home_url($path) { return 'https://complete99.co.il' . $path; }
class Complete99_Content {
    public static function route_url($key, $lang) {
        $paths = ['home'=>'', 'dishes'=>'dishes/', 'proposal'=>'request-proposal/', 'ingredients'=>'ingredients/', 'knowledge'=>'knowledge/', 'traditions'=>'traditions/', 'contact'=>'contact/', 'about'=>'about/', 'store'=>'store/'];
        if (!array_key_exists($key, $paths)) { throw new RuntimeException('Unknown fixture route: ' . $key); }
        return home_url(($lang === 'en' ? '/en/' : '/') . $paths[$key]);
    }
}
class Complete99_Commerce {
    public static function order_url($lang) { return 'https://wolt.com/' . $lang . '/isr/tel-aviv/restaurant/sabich-complete'; }
    public static function is_ready() { return false; }
    public static function catalog_is_ready() { return false; }
    public static function can_preview_commerce() { return false; }
}
class Complete99_REST {
    public static function public_indexable_items() { return require COMPLETE99_PLATFORM_DIR . 'data/consumer-menu.php'; }
}
class Complete99_Frontend {
    public static function live_dish_url($slug, $lang) { return home_url(($lang === 'en' ? '/en/' : '/') . 'menu/' . $slug . '/'); }
}
require COMPLETE99_PLATFORM_DIR . 'includes/class-complete99-consumer.php';
$lang = PHP_SAPI === 'cli' ? ($argv[1] ?? 'he') : ($request_path === '/preview/en/' ? 'en' : 'he');
if (!in_array($lang, ['he', 'en'], true)) { throw new RuntimeException('Unknown fixture language'); }
?>
<!doctype html>
<html lang="<?= esc_attr($lang) ?>" dir="<?= $lang === 'he' ? 'rtl' : 'ltr' ?>">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Complete99 homepage implementation preview</title>
<link rel="stylesheet" href="/plugin/complete99-platform/assets/css/public.css">
<link rel="stylesheet" href="/plugin/complete99-platform/assets/css/consumer.css">
</head>
<body class="complete99-public c99-consumer-site">
<aside style="padding:8px 24px;background:#17231f;color:white;font:16px Arial">תצוגת פיתוח מקומית של גוף דף הבית. לא פורסם באתר. כותרת האתר וקטלוג WooCommerce אינם חלק מהבדיקה הזאת.</aside>
<main id="c99-main"><?php
$method = new ReflectionMethod(Complete99_Consumer::class, 'render_home');
$method->invoke(null, (object) ['post_excerpt'=>''], $lang);
?></main>
</body></html>
