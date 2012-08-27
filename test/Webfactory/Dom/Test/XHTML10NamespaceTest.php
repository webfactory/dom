<?php
namespace Webfactory\Dom\Test;

class XHTML10NamespaceTest extends XHTML10ParserTest {

    protected $xmlString = <<<XML
<?xml version="1.0"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head>
    <body>
        <p class="nonamespace">Test</p>
        <p xmlns:foo="urn:test" class="foonamespace">
            <foo:bar></foo:bar>
        </p>
    </body>
</html>
XML;

    public function testEntireDocumentIsPreserved() {
        $this->assertEquals(trim($this->xmlString), trim($this->parser->dumpDocument($this->document)));
    }

    public function testDumpElementInDefaultHTMLNamespace() {
        $res = $this->xpath->query('//html:p[@class="nonamespace"]', $this->document);
        $this->assertEquals(1, count($res));
        $this->assertXmlStringEqualsXmlString('<p class="nonamespace">Test</p>', $this->parser->dumpElement($res->item(0)));
    }

    protected function assertionsForXpathQueryElementInFooNamespace($xpathExpression, $result) {
        $res = $this->xpath->query($xpathExpression, $this->document);
        $this->assertEquals(1, count($res));
        $item = $res->item(0);
        $dump = $this->parser->dumpElement($item);
        $this->assertXmlStringEqualsXmlString($result, $dump);
    }

    public function testDumpElementInFooNamespaceByHTMLClass() {
        $this->assertionsForXpathQueryElementInFooNamespace('//html:p[@class="foonamespace"]', '<p xmlns:foo="urn:test" class="foonamespace"><foo:bar></foo:bar></p>');
    }

    public function testDumpElementInFooNamespaceByNamespaceName() {
        $this->xpath->registerNamespace('test', 'urn:test');
        $this->assertionsForXpathQueryElementInFooNamespace('//test:bar', '<foo:bar xmlns:foo="urn:test"/>');
    }
}
