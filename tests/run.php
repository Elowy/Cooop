<?php

declare(strict_types=1);

/**
 * Pehelysúlyú teszt-futtató (PHPUnit nélkül, hogy a projekt függőség-mentes
 * maradjon). A kulcs-üzleti logikát fedi le; futtatás: `php tests/run.php`.
 * Hibánál nem-nulla kilépési kóddal tér vissza (CI-kapu).
 */

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Catalog\Categories;
use App\Catalog\ProductImageStore;
use App\Core\Cart;
use App\Core\Csrf;
use App\Core\LoginThrottle;
use App\Core\Mailer;
use App\Core\View;
use App\Integration\Product;
use App\Order\OrderStore;

$tests = 0;
$failed = 0;
function ok(string $name, bool $cond): void
{
    global $tests, $failed;
    $tests++;
    if ($cond) {
        echo "  \033[32m✓\033[0m {$name}\n";
    } else {
        $failed++;
        echo "  \033[31m✗ {$name}\033[0m\n";
    }
}
function eq(string $name, mixed $expected, mixed $actual): void
{
    $same = $expected === $actual;
    ok($name . ($same ? '' : ' [várt: ' . var_export($expected, true) . ', kapott: ' . var_export($actual, true) . ']'), $same);
}

$tmp = sys_get_temp_dir() . '/nt-tests-' . bin2hex(random_bytes(4));
@mkdir($tmp, 0775, true);

echo "Product\n";
$p = Product::fromArray(['sku' => 'X', 'slug' => 'x', 'category' => 'c', 'name' => 'X', 'unit' => 'db', 'price_net' => 100, 'vat' => 27, 'stock' => 5, 'icon' => 'i', 'short' => 's']);
eq('priceGross 100+27% = 127', 127, $p->priceGross());
ok('inStock igaz, ha stock > 0', $p->inStock());
$p0 = Product::fromArray(['sku' => 'Y', 'slug' => 'y', 'category' => 'c', 'name' => 'Y', 'unit' => 'db', 'price_net' => 100, 'vat' => 27, 'stock' => 0, 'icon' => 'i', 'short' => 's']);
ok('inStock hamis, ha stock = 0', !$p0->inStock());

echo "Cart\n";
$_SESSION = [];
Cart::add('A', 2);
Cart::add('A', 3);
eq('add összegez', 5, Cart::count());
Cart::add('B', 1);
eq('két SKU darabszáma', 6, Cart::count());
Cart::set('A', 0);
ok('set 0 töröl', !isset(Cart::items()['A']));
Cart::remove('B');
eq('remove után üres', 0, Cart::count());

echo "Csrf\n";
$_SESSION = [];
$token = Csrf::token();
ok('token 32 hex', strlen($token) === 32 && ctype_xdigit($token));
ok('érvényes token átmegy', Csrf::check($token));
ok('rossz token elbukik', !Csrf::check('rossz'));
ok('null token elbukik', !Csrf::check(null));

echo "Categories\n";
$cats = new Categories();
ok('van levél-kategória', $cats->leafCount() > 0);
$leaf = null;
foreach ($cats->all() as $key => $node) {
    if ($node['children'] === []) {
        $leaf = $key;
        break;
    }
}
ok('találtunk levelet', $leaf !== null);
if ($leaf !== null) {
    $path = $cats->path($leaf);
    eq('path utolsó eleme a levél', $leaf, end($path));
    ok('path gyökere top-level', $cats->find($path[0])['parent'] === null);
    ok('branch tartalmazza önmagát', in_array($leaf, $cats->branch($leaf), true));
}

echo "OrderStore::validToken\n";
ok('32 hex érvényes', OrderStore::validToken(str_repeat('a', 32)));
ok('rövid érvénytelen', !OrderStore::validToken('abc'));
ok('nem-hex érvénytelen', !OrderStore::validToken(str_repeat('z', 32)));

echo "ProductImageStore (fájl mód)\n";
$imgStore = new ProductImageStore(null, $tmp . '/images.json');
$imgStore->add('SKU1', 'a.png');
$imgStore->add('SKU1', 'b.png');
eq('add sorrendben', ['a.png', 'b.png'], $imgStore->find('SKU1'));
$imgStore->makePrimary('SKU1', 'b.png');
eq('makePrimary előrehoz', ['b.png', 'a.png'], $imgStore->find('SKU1'));
$imgStore->remove('SKU1', 'a.png');
eq('remove töröl', ['b.png'], $imgStore->find('SKU1'));
$imgStore->save('SKU1', []);
eq('üres mentés törli a kulcsot', [], $imgStore->find('SKU1'));

echo "LoginThrottle\n";
$throttle = new LoginThrottle($tmp . '/login.json', 3, 60);
ok('kezdetben nincs zárolás', !$throttle->blocked('ip1'));
$throttle->registerFailure('ip1');
$throttle->registerFailure('ip1');
ok('2 hiba után még nincs zárolás', !$throttle->blocked('ip1'));
$throttle->registerFailure('ip1');
ok('3 hiba után zárolva', $throttle->blocked('ip1'));
ok('retryAfter pozitív zároláskor', $throttle->retryAfter('ip1') > 0);
$throttle->clear('ip1');
ok('clear feloldja', !$throttle->blocked('ip1'));
ok('másik kulcs független', !$throttle->blocked('ip2'));

echo "Mailer::dotStuff\n";
eq('vezető pontot duplázza', "..hello", Mailer::dotStuff('.hello'));
eq('CRLF-re normalizál', "a\r\nb", Mailer::dotStuff("a\nb"));
eq('normál sor változatlan', "hello", Mailer::dotStuff('hello'));

echo "View\n";
eq('huf formázás', '1' . "\u{00A0}" . '234' . "\u{00A0}Ft", View::huf(1234));
eq('e escape-eli a HTML-t', '&lt;b&gt;', View::e('<b>'));
ok('asset cache-busting ?v=', str_contains(View::asset('/assets/css/style.css'), '?v='));

// Takarítás
array_map('unlink', glob($tmp . '/*') ?: []);
@rmdir($tmp);

echo "\n";
if ($failed === 0) {
    echo "\033[32mMind a {$tests} teszt sikeres.\033[0m\n";
    exit(0);
}
echo "\033[31m{$failed}/{$tests} teszt megbukott.\033[0m\n";
exit(1);
