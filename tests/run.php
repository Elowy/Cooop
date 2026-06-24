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

use App\Blog\BlogStore;
use App\Catalog\Categories;
use App\Catalog\ProductImageStore;
use App\Core\Cart;
use App\Core\Csrf;
use App\Core\LoginThrottle;
use App\Core\Mailer;
use App\Core\View;
use App\Integration\Product;
use App\Order\OrderStore;
use App\Service\ServiceStore;

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

echo "BlogStore::slugify\n";
eq('ékezet + szóköz', 'export-csomagolas', BlogStore::slugify('Export Csomagolás'));
eq('többszörös elválasztó összevon', 'a-b', BlogStore::slugify('  a---b!!  '));
eq('üres bemenet → bejegyzes', 'bejegyzes', BlogStore::slugify('!!!'));

echo "BlogStore (fájl mód)\n";
$blogStore = new BlogStore(null, $tmp . '/blog.json', $tmp . '/noseed.php');
$bId1 = $blogStore->save(['title' => 'Első bejegyzés', 'body' => 'Tartalom', 'published' => 1]);
ok('mentés pozitív id-t ad', $bId1 > 0);
eq('slug a címből generálódik', 'elso-bejegyzes', $blogStore->find($bId1)['slug']);
$bId2 = $blogStore->save(['title' => 'Első bejegyzés', 'published' => 0]);
eq('ütköző slug -2 utótaggal', 'elso-bejegyzes-2', $blogStore->find($bId2)['slug']);
eq('all() minden bejegyzést hoz', 2, count($blogStore->all()));
eq('all(true) csak a publikáltat', 1, count($blogStore->all(true)));
ok('findBySlug megtalál', $blogStore->findBySlug('elso-bejegyzes') !== null);
ok('findBySlug publishedOnly rejti a vázlatot', $blogStore->findBySlug('elso-bejegyzes-2', true) === null);
$blogStore->save(['id' => $bId2, 'title' => 'Első bejegyzés', 'published' => 1]);
eq('publikálás után 2 látszik', 2, count($blogStore->all(true)));
$blogStore->delete($bId1);
eq('törlés után 1 marad', 1, count($blogStore->all()));

echo "ServiceStore (fájl mód)\n";
$svcStore = new ServiceStore(null, $tmp . '/services.json', $tmp . '/noseed.php');
$svcStore->save(['title' => 'Második', 'sort' => 2, 'published' => 1]);
$svcStore->save(['title' => 'Első', 'sort' => 1, 'published' => 1]);
$sId3 = $svcStore->save(['title' => 'Rejtett', 'sort' => 3, 'published' => 0]);
eq('sort szerint rendez (Első előre)', 'Első', $svcStore->all()[0]['title']);
eq('all(true) csak a publikáltakat', 2, count($svcStore->all(true)));
eq('menu() a publikáltakat sorrendben', ['Első', 'Második'], array_column($svcStore->menu(), 'title'));
ok('findBySlug megtalál', $svcStore->findBySlug('elso') !== null);
ok('findBySlug publishedOnly rejti', $svcStore->findBySlug('rejtett', true) === null);
eq('slugify fallback', 'szolgaltatas', ServiceStore::slugify('!!!'));
$svcStore->delete($sId3);
eq('törlés után 2 marad', 2, count($svcStore->all()));

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

echo "Consent\n";
$cEmpty = \App\Core\Consent::parse('');
ok('üres süti: nincs hozzájárulás (set=false)', $cEmpty['set'] === false);
ok('üres süti: szükséges mindig igaz', $cEmpty['necessary'] === true);
ok('üres süti: analytics/marketing hamis', !$cEmpty['analytics'] && !$cEmpty['marketing']);
$cAll = \App\Core\Consent::parse('all');
ok('örökölt "all": minden kategória igaz', $cAll['set'] && $cAll['analytics'] && $cAll['marketing']);
$cNec = \App\Core\Consent::parse('necessary');
ok('"necessary": csak szükséges', $cNec['set'] && !$cNec['analytics'] && !$cNec['marketing']);
$cAna = \App\Core\Consent::parse('necessary-analytics');
ok('"necessary-analytics": csak statisztika', $cAna['analytics'] && !$cAna['marketing']);
$cMar = \App\Core\Consent::parse('necessary-marketing');
ok('"necessary-marketing": csak marketing', !$cMar['analytics'] && $cMar['marketing']);
eq('encode(true,false) → necessary-analytics', 'necessary-analytics', \App\Core\Consent::encode(true, false));
eq('encode(false,false) → necessary', 'necessary', \App\Core\Consent::encode(false, false));
eq('encode(true,true) → minden', 'necessary-analytics-marketing', \App\Core\Consent::encode(true, true));
eq('parse∘encode oda-vissza', true, \App\Core\Consent::parse(\App\Core\Consent::encode(true, true))['marketing']);

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
