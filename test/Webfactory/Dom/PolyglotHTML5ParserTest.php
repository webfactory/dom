<?php

namespace Webfactory\Dom;

class PolyglotHTML5ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PolyglotHTML5Parser
     */
    protected $parser;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->parser = new PolyglotHTML5Parser;
    }

    /**
     * @expectedException Webfactory\Dom\EmptyXMLStringException
     */
    public function testParseDocumentWithEmptyString() {
        $this->parser->parseDocument('');
    }

    public function testParseDocumentWithEncodedCDataSection() {
        $data = '<script type="text/javascript" xml:space="preserve">//<![CDATA[
                    xxx
                //]]]]></script>';
        $doc = $this->parser->parseDocument($data);
        $this->assertTrue($doc->documentElement !== null);

        $string = $doc->saveXML();
        // Sicherstellen, dass CDATA nur einmal vorkommt, und nicht
        // durch das Parsen doppelt escaped wurde (unsere CDATA-
        // Section ist fuer JavaScript escaped.
        $this->assertEquals(1, substr_count($string, 'CDATA'));
    }

    public function testParseDocumentWithFacebookButton() {
        $data = '<html xmlns="http://www.w3.org/1999/xhtml"
                        xmlns:fb="https://www.facebook.com/2008/fbml"><body>
                    <fb:like href="http://www.test.de" layout="button_count" show_faces="false" width="100%"></fb:like>
                </body></html>';
        $doc = $this->parser->parseDocument($data);
        $this->assertTrue($doc->documentElement !== null);
    }

    /**
     * @expectedException Webfactory\Dom\ParsingException
     */
    public function testParseDocumentWithFacebookButtonWithoutNamespace() {
        $data = '<html xmlns="http://www.w3.org/1999/xhtml"><body>
                    <fb:like href="http://www.test.de" layout="button_count" show_faces="false" width="100%"></fb:like>
                </body></html>';
        $this->parser->parseDocument($data);
    }

    public function testParseDocumentFragment() {
        $data = '<root><bla>x</bla></root>';
        $expected = new \DOMDocument('1.0');
        $expected->loadXML($this->wrapHtmlFragment($data));

        $actual = $this->parser->parseFragment($data);
        $this->assertEqualXMLStructure($expected->documentElement, $actual->documentElement);
    }

    public function testDumpDocument() {
        $data = "<?xml version=\"1.0\"?>\n<root><bla>x</bla></root>\n";
        $document = $this->parser->parseDocument($data);

        $actual = $this->parser->dumpDocument($document);
        $this->assertEquals($data, $actual);
    }

    public function testDumpDocumentWithEmptyElements() {
        $data = "<?xml version=\"1.0\"?>\n<root><area></area></root>\n";
        $expected = "<?xml version=\"1.0\"?>\n<root><area/></root>\n";
        $document = $this->parser->parseDocument($data);

        $actual = $this->parser->dumpDocument($document);
        $this->assertEquals($expected, $actual);
    }

    public function testDumpFragment() {
        $data = '<root><bla>x</bla></root>';
        $document = $this->parser->parseDocument($data);

        $actual = $this->parser->dumpElement($document->documentElement);
        $this->assertEquals($data, $actual);
    }

    protected function wrapHtmlFragment($data) {
        return '<html xmlns:esi="http://www.edge-delivery.org/esi/1.0" xmlns="' . PolyglotHTML5Parser::XHTMLNS . '">' . $data . '</html>';
    }

}
