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
use App\Integration\RestAxelGateway;
use App\Integration\XmlAxelGateway;
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
$sImgId = $svcStore->save(['title' => 'Képes', 'image' => 'https://pelda.hu/kep.jpg', 'sort' => 4, 'published' => 1]);
eq('image mező mentés/visszaolvasás', 'https://pelda.hu/kep.jpg', $svcStore->find($sImgId)['image']);
eq('image alapértelmezés üres', '', $svcStore->findBySlug('elso')['image']);
$svcStore->delete($sImgId);
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

echo "Barion\n";
$bTokenA = str_repeat('a', 32);
$bOrder = [
    'token' => $bTokenA,
    'number' => 'NT-000123',
    'totals' => ['gross' => 2540],
    'items' => [['name' => 'Raklap', 'unit' => 'db', 'qty' => 2, 'price_gross' => 1270, 'subtotal' => 2540]],
];
$bPayload = \App\Payment\BarionPaymentGateway::buildStartPayload($bOrder, ['base_url' => 'https://shop.example/', 'payee' => 'penztar@ceg.hu', 'currency' => 'HUF']);
eq('payload PaymentRequestId = token', $bTokenA, $bPayload['PaymentRequestId']);
eq('payload RedirectUrl a tokennel', 'https://shop.example/barion/vissza?token=' . $bTokenA, $bPayload['RedirectUrl']);
eq('payload CallbackUrl', 'https://shop.example/barion/callback', $bPayload['CallbackUrl']);
eq('payload Payee a tranzakcióban', 'penztar@ceg.hu', $bPayload['Transactions'][0]['Payee']);
eq('payload Total bruttó', 2540.0, $bPayload['Transactions'][0]['Total']);
eq('payload tételszám', 1, count($bPayload['Transactions'][0]['Items']));
eq('tétel ItemTotal', 2540.0, $bPayload['Transactions'][0]['Items'][0]['ItemTotal']);
ok('Succeeded = fizetve', \App\Payment\BarionPaymentGateway::isPaid('Succeeded'));
ok('Prepared != fizetve', !\App\Payment\BarionPaymentGateway::isPaid('Prepared'));
ok('Expired = végleges kudarc', \App\Payment\BarionPaymentGateway::isFinalFailure('Expired'));
ok('Started != végleges kudarc', !\App\Payment\BarionPaymentGateway::isFinalFailure('Started'));

$bCaptured = [];
$bTransport = static function (string $method, string $url, ?array $body) use (&$bCaptured): string {
    $bCaptured[] = ['method' => $method, 'url' => $url, 'body' => $body];
    return str_contains($url, 'Payment/Start')
        ? (string) json_encode(['PaymentId' => 'PID-1', 'GatewayUrl' => 'https://barion/pay/PID-1'])
        : (string) json_encode(['Status' => 'Succeeded', 'PaymentRequestId' => 'tok']);
};
$bClient = new \App\Payment\BarionClient('POS-GUID', 'test', $bTransport);
$bStart = $bClient->startPayment(['PaymentRequestId' => 'tok']);
eq('startPayment GatewayUrl', 'https://barion/pay/PID-1', $bStart['GatewayUrl']);
ok('startPayment POST a Start végpontra', $bCaptured[0]['method'] === 'POST' && str_contains($bCaptured[0]['url'], '/v2/Payment/Start'));
ok('startPayment a POSKey-t hozzáfűzi', ($bCaptured[0]['body']['POSKey'] ?? '') === 'POS-GUID');
ok('test env = sandbox host', str_contains($bCaptured[0]['url'], 'api.test.barion.com'));
$bState = $bClient->getPaymentState('PID-1');
eq('getPaymentState Status', 'Succeeded', $bState['Status']);
ok('getPaymentState GET + POSKey + PaymentId', $bCaptured[1]['method'] === 'GET' && str_contains($bCaptured[1]['url'], 'POSKey=POS-GUID') && str_contains($bCaptured[1]['url'], 'PaymentId=PID-1'));
$bProdUrl = '';
$bClientProd = new \App\Payment\BarionClient('POS', 'prod', static function ($m, $u, $b) use (&$bProdUrl): string { $bProdUrl = $u; return '{}'; });
$bClientProd->getPaymentState('X');
ok('prod env = éles host', str_contains($bProdUrl, 'https://api.barion.com') && !str_contains($bProdUrl, 'test'));

$bStore = new OrderStore(null, $tmp . '/border');
$bTokenB = str_repeat('b', 32);
$bStore->save(['token' => $bTokenB, 'number' => 'NT-1', 'status' => 'pending', 'payment' => ['method' => 'card', 'status' => 'pending'], 'totals' => ['gross' => 1000], 'items' => [['name' => 'X', 'unit' => 'db', 'qty' => 1, 'price_gross' => 1000, 'subtotal' => 1000]]]);
$bGwClient = new \App\Payment\BarionClient('POS', 'test', static fn ($m, $u, $b): string => (string) json_encode(['PaymentId' => 'PID-9', 'GatewayUrl' => 'https://barion/pay/PID-9']));
$bGw = new \App\Payment\BarionPaymentGateway($bGwClient, $bStore, ['base_url' => 'https://shop', 'payee' => 'p@c.hu', 'currency' => 'HUF']);
eq('start() a GatewayUrl-t adja', 'https://barion/pay/PID-9', $bGw->start($bStore->find($bTokenB)));
eq('start() eltárolja a PaymentId-t', 'PID-9', $bStore->find($bTokenB)['payment']['payment_id']);
eq('findByPaymentId visszatalál', $bTokenB, $bStore->findByPaymentId('PID-9')['token']);

echo "XmlAxelGateway\n";
$axelDir = $tmp . '/axel';
@mkdir($axelDir, 0775, true);
file_put_contents($axelDir . '/catalog.xml', '<?xml version="1.0" encoding="UTF-8"?>
<catalog>
  <product><sku>A-1</sku><slug>tegla</slug><category>ep</category><name>Tégla</name><unit>db</unit><priceNet>100</priceNet><vat>27</vat><stock>500</stock><icon>brick</icon><short>rövid</short></product>
  <product><sku>B-2</sku><slug>homok</slug><category>ep</category><name>Homok</name><unit>m3</unit><price_net>9000</price_net><vat>27</vat><stock>0</stock><icon>sand</icon><short>s</short></product>
</catalog>');
$xmlGw = new XmlAxelGateway($axelDir);
eq('XML: products() két terméket ad', 2, count($xmlGw->products()));
eq('XML: findProduct slug szerint', 'Tégla', $xmlGw->findProduct('tegla')->name);
eq('XML: priceNet beolvasva', 100, $xmlGw->findProduct('tegla')->priceNet);
eq('XML: price_net (snake_case) is megy', 9000, $xmlGw->findProduct('homok')->priceNet);
eq('XML: stockFor SKU szerint', 500, $xmlGw->stockFor('A-1'));
ok('XML: ismeretlen slug → null', $xmlGw->findProduct('nincs') === null);
ok('XML: ismeretlen SKU stock → null', $xmlGw->stockFor('NINCS') === null);
eq('XML: hiányzó katalógus → üres', 0, count((new XmlAxelGateway($tmp . '/nincs'))->products()));

$axelOrder = [
    'token' => 'abc123', 'number' => 'NT-7', 'created' => '2026-06-25T10:00:00+02:00',
    'customer' => ['name' => 'Teszt Elek', 'email' => 't@e.hu', 'phone' => '+3612', 'tax_number' => '123'],
    'billing' => ['zip' => '2660', 'city' => 'Balassagyarmat', 'address' => 'Fő út 1'],
    'shipping' => null,
    'items' => [['sku' => 'A-1', 'name' => 'Tégla', 'unit' => 'db', 'qty' => 3, 'price_net' => 100, 'vat' => 27, 'price_gross' => 127, 'subtotal' => 381]],
    'totals' => ['gross' => 381],
];
$xmlInv = $xmlGw->createInvoice($axelOrder);
ok('XML: createInvoice ok=true', $xmlInv->ok);
ok('XML: aszinkron – számlaszám nélkül', $xmlInv->invoiceNumber === null);
$orderFile = $axelDir . '/orders/order-abc123.xml';
ok('XML: rendelés-fájl létrejött', is_file($orderFile));
$writtenXml = is_file($orderFile) ? (string) file_get_contents($orderFile) : '';
ok('XML: rendelés-XML well-formed', simplexml_load_string($writtenXml) !== false);
ok('XML: tartalmazza a vevőt', str_contains($writtenXml, '<name>Teszt Elek</name>'));
ok('XML: tartalmazza a tételt', str_contains($writtenXml, '<sku>A-1</sku>') && str_contains($writtenXml, '<qty>3</qty>'));
ok('XML: adatcsere-könyvtár nélkül → ok=false', !(new XmlAxelGateway(''))->createInvoice($axelOrder)->ok);

echo "RestAxelGateway\n";
$restHttp = static function (string $method, string $url, array $headers, ?string $body): array {
    $GLOBALS['_rest_last'] = ['headers' => $headers, 'body' => $body];
    if ($method === 'GET' && str_ends_with($url, '/products')) {
        return ['status' => 200, 'body' => (string) json_encode([
            ['sku' => 'A-1', 'slug' => 'tegla', 'category' => 'c', 'name' => 'Tégla', 'unit' => 'db', 'priceNet' => 100, 'vat' => 27, 'stock' => 500, 'icon' => 'brick', 'short' => 's'],
            ['sku' => 'B-2', 'slug' => 'homok', 'category' => 'c', 'name' => 'Homok', 'unit' => 'm3', 'price_net' => 9000, 'vat' => 27, 'stock' => 0, 'icon' => 'sand', 'short' => 's'],
        ])];
    }
    if ($method === 'GET' && str_contains($url, '/stock/')) {
        return ['status' => 200, 'body' => (string) json_encode(['sku' => 'A-1', 'stock' => 42])];
    }
    if ($method === 'POST' && str_ends_with($url, '/invoices')) {
        return ['status' => 200, 'body' => (string) json_encode(['ok' => true, 'invoiceNumber' => '2026-NT-0042', 'message' => 'OK'])];
    }
    return ['status' => 404, 'body' => ''];
};
$restGw = new RestAxelGateway('https://api.example/v1/', 'KEY123', $restHttp);
eq('REST: products() két terméket ad', 2, count($restGw->products()));
eq('REST: priceNet leképezve', 100, $restGw->findProduct('tegla')->priceNet);
eq('REST: price_net (snake) is megy', 9000, $restGw->findProduct('homok')->priceNet);
eq('REST: stockFor a /stock végpontból', 42, $restGw->stockFor('A-1'));
ok('REST: X-Api-Key fejléc elküldve', in_array('X-Api-Key: KEY123', $GLOBALS['_rest_last']['headers'] ?? [], true));
$restInv = $restGw->createInvoice($axelOrder);
ok('REST: createInvoice ok=true', $restInv->ok);
eq('REST: számlaszám a válaszból', '2026-NT-0042', $restInv->invoiceNumber);
$restErr = new RestAxelGateway('https://api.example/v1', 'K', static fn ($m, $u, $h, $b): array => ['status' => 500, 'body' => '']);
eq('REST: 500 → üres katalógus', 0, count($restErr->products()));
ok('REST: 500 → createInvoice ok=false', !$restErr->createInvoice($axelOrder)->ok);
ok('REST: üres api_url → createInvoice ok=false', !(new RestAxelGateway(''))->createInvoice($axelOrder)->ok);

// Takarítás (rekurzív, hogy az almappák – pl. border, axel – se maradjanak)
$rmrf = static function (string $path) use (&$rmrf): void {
    foreach (glob($path . '/*') ?: [] as $f) {
        is_dir($f) ? $rmrf($f) : @unlink($f);
    }
    @rmdir($path);
};
$rmrf($tmp);

echo "\n";
if ($failed === 0) {
    echo "\033[32mMind a {$tests} teszt sikeres.\033[0m\n";
    exit(0);
}
echo "\033[31m{$failed}/{$tests} teszt megbukott.\033[0m\n";
exit(1);
