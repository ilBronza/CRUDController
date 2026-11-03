<?php
spl_autoload_register(function ($class) {
    $prefix = 'IlBronza\\CRUD\\';
    if (strpos($class, $prefix) === 0) require dirname(__DIR__, 2) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
});
class Item implements \IlBronza\CRUD\Interfaces\Sitemapable {
    public function getSitemapUrls(): iterable { return ['it-IT'=>'https://example.com/it?a=1&b=2', 'en'=>'https://example.com/en', 'fr'=>'https://example.com/fr']; }
    public function getSitemapLastModified(): ?DateTimeInterface { return new DateTimeImmutable('2026-10-22'); }
    public function isSitemapIndexable(): bool { return true; }
    public function getSitemapChangeFrequency(): ?\IlBronza\CRUD\Enums\SitemapChangeFrequency { return \IlBronza\CRUD\Enums\SitemapChangeFrequency::Weekly; }
    public function getSitemapPriority(): ?float { return 0.8; }
    public function getSeo($locale) { return (object)['robots'=>$locale==='fr' ? 'NOINDEX, follow' : 'index', 'canonical_url'=>null]; }
    public function getDefaultSeoUrl($locale) { return iterator_to_array((function(){yield from $this->getSitemapUrls();})())[$locale]; }
}
$provider = new class implements \IlBronza\CRUD\Interfaces\SitemapProvider {
    public function items(): iterable { yield new Item; yield new Item; }
};
$generator = new \IlBronza\CRUD\Sitemap\SitemapGenerator;
$docs = iterator_to_array($generator->documents([$provider], 1));
if(count($docs)!==2) throw new Exception('Chunk/dedup failure');
$joined = implode('', $docs);
if(!simplexml_load_string($docs[0]) || !simplexml_load_string($docs[1])) throw new Exception('Invalid XML');
if(strpos($joined,'example.com/fr')!==false || strpos($joined,'&amp;')===false || strpos($joined,'xhtml:link')!==false) throw new Exception('XML/default-without-hreflang failure');
if(strpos($joined,'<changefreq>weekly</changefreq>')===false || strpos($joined,'<priority>0.8</priority>')===false) throw new Exception('changefreq/priority failure');
try {
    new \IlBronza\CRUD\Sitemap\SitemapEntry('https://example.com', 'en', priority: 1.5);
    throw new Exception('priority validation failure');
} catch (InvalidArgumentException) {}
$withAlternates = new \IlBronza\CRUD\Sitemap\SitemapGenerator(includeHreflangAlternates: true);
foreach(iterator_to_array($withAlternates->documents([$provider], 1)) as $xml) {
    if(substr_count($xml,'xhtml:link')!==2) throw new Exception('XML/opt-in alternates failure');
}
if(!simplexml_load_string($generator->index(['https://example.com/sitemap-1.xml']))) throw new Exception('Index failure');
echo "PASS XML, noindex, chunking, deduplication, index, opt-in hreflang, changefreq/priority\n";
