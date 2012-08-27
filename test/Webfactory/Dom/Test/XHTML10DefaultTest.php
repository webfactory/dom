<?php
namespace Webfactory\Dom\Test;

class XHTML10DefaultTest extends XHTML10ParserTest {

    public function testParseDocument() {
        $this->markTestSkipped("Was soll das testen?");
        $data = '<root><bla>x</bla></root>';
        $expected = "<?xml version=\"1.0\"?>\n<root><bla>x</bla></root>\n";
        $document = $this->parser->parseDocument($data);

        $actual = $document->saveXML();
        $this->assertEquals($expected, $actual);
    }

    public function testParseFragment() {
        $this->markTestSkipped("Was soll das testen?");
        $data = '<root><bla>x</bla></root>';
        $expected = '<?xml version="1.0"?>' . "\n" . $this->wrapFragment($data) . "\n";
        $document = $this->parser->parseFragment($data);

        $actual = $document->saveXML();
        $this->assertEquals($expected, $actual);
    }

    protected function wrapFragment($fragment) {
        return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\n"
            . '<html xmlns:esi="http://www.edge-delivery.org/esi/1.0" xmlns="http://www.w3.org/1999/xhtml">'
            . '<html xmlns:esi="http://www.edge-delivery.org/esi/1.0" xmlns="http://www.w3.org/1999/xhtml">'
            . $fragment
            . '</html>';
    }


}
