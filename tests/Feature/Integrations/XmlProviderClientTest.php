<?php

namespace Tests\Feature\Integrations;

use App\Integrations\Providers\XmlProviderClient;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class XmlProviderClientTest extends TestCase
{
    #[Test]
    public function maps_xml_payload_to_items_and_meta(): void
    {
        $url = 'https://example.test/provider2';
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed>
  <items>
    <item>
      <id>a1</id>
      <headline>Article #1</headline>
      <type>article</type>
      <stats>
        <reading_time>7</reading_time>
        <reactions>12</reactions>
      </stats>
      <publication_date>2025-09-01</publication_date>
      <categories>
        <category>Backend</category>
        <category>PHP</category>
      </categories>
    </item>
  </items>
  <meta>
    <total_count>1</total_count>
    <current_page>1</current_page>
    <items_per_page>50</items_per_page>
  </meta>
</feed>
XML;

        Http::fake([$url => Http::response($xml, 200)]);

        $client = new XmlProviderClient($url);
        $page = $client->fetchPage(1, 50);

        $this->assertSame(1, $page->page);
        $this->assertSame(50, $page->perPage);
        $this->assertNull($page->nextPage);
        $this->assertCount(1, $page->items);
        $this->assertSame('xml_provider', $page->items[0]['provider']);
        $this->assertSame('a1', $page->items[0]['provider_item_id']);
        $this->assertSame('article', $page->items[0]['type']);
        $this->assertSame(['Backend','PHP'], $page->items[0]['tags']);

        $items = iterator_to_array($client->streamAll(1, 50));
        $this->assertCount(1, $items);
        $this->assertSame('Article #1', $items[0]['title']);
    }
}
