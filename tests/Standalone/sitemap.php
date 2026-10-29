<?php
spl_autoload_register(function ($class) {
    $prefix = 'IlBronza\\CRUD\\';
    if (strpos($class, $prefix) === 0) require dirname(__DIR__, 2) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
});
class Item implements \IlBronza\CRUD\Interfaces\Sitemapable {
    public function getSitemapUrls(): iterable { return ['it-IT'=>'https://example.com/it?a=1&b=2', 'en'=>'https://example.com/en', 'fr'=>'https://example.com/fr']; }
    public function getSitemapLastModified(): ?DateTimeInterface { return new DateTimeImmutable('2026-10-22'); }
    public function isSitemapIndexable(): bool { return true; }
    public function getSeo($locale) { return (object)['robots'=>$locale==='fr' ? 'NOINDEX, follow' : 'index', 'canonical_url'=>null]; }
    public function getDefaultSeoUrl($locale) { return iterator_to_array((function(){yield from $this->getSitemapUrls();})())[$locale]; }
}
$provider = new class implements \IlBronza\CRUD\Interfaces\SitemapProvider {
    public function items(): iterable { yield new Item; yield new Item; }
};
$generator = new \IlBronza\CRUD\Sitemap\SitemapGenerator;
$docs = iterator_to_array($generator->documents([$provider], 1));
if(count($docs)!==2) throw new Exception('Chunk/dedup failure');
foreach($docs as $xml) {
    if(!simplexml_load_string($xml) || strpos($xml,'example.com/fr')!==false || strpos($xml,'&amp;')===false || substr_count($xml,'xhtml:link')!==2) throw new Exception('XML/alternates failure');
}
if(!simplexml_load_string($generator->index(['https://example.com/sitemap-1.xml']))) throw new Exception('Index failure');
echo "PASS XML, multilingual alternates, noindex, chunking, deduplication, index\n";
