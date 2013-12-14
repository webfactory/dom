<?php

namespace Webfactory\Dom\Test;

class BaseParsingHelperTest extends ParsingHelperTest {

    protected function createParsingHelper()
    {
        return new \Webfactory\Dom\BaseParsingHelper();
    }

    public function testEntireDocumentIsPreserved()
    {
        /* Ein gesamtes Dokument, mit default-Namespace und NS-Deklarationen in unterschiedlichen Scopes */
        $entireDocument = <<<XML
<?xml version="1.0"?>
<root xmlns="urn:default" xmlns:foo="urn:foo">
    <tag/>
    <foo:tag/>
    <bar xmlns:baz="urn:baz">
        <foo:tag/>
        <baz:tag/>
    </bar>
    <bar>
        <baz:tag xmlns:baz="urn:baz"/>
    </bar>
</root>
XML;
        $document = $this->parser->parseDocument($entireDocument);
        $this->assertXmlStringEqualsXmlString($entireDocument, $this->parser->dump($document));
    }

    public function testParseFragment()
    {
        // Unterschiedliche Fragmente mit verschiedenen NS-Deklarationen
        $this->readDumpAssertFragment('<tag>Foo</tag>');
        $this->readDumpAssertFragment('<foo:tag xmlns:foo="urn:test">Foo</foo:tag>');
        $this->readDumpAssertFragment('<tag xmlns:foo="urn:test"><foo:tag>Foo</foo:tag></tag>');
        $this->readDumpAssertFragment('<tag xmlns="urn:test">Foo</tag>');
    }

    /**
     * @expectedException \Webfactory\Dom\Exception\ParsingException
     */
    public function testParseFragmentWithUnknownNamespaceDecl()
    {
        $this->readDumpAssertFragment('<foo:tag>xxx</foo:tag>');
    }

    /**
     * @expectedException \Webfactory\Dom\Exception\ParsingException
     */
    public function testParseDocumentWithUnknownNamespaceDecl()
    {
        $document = $this->parser->parseDocument('<root><foo:fail>failme</foo:fail></root>');
    }

    public function testParseFragmentWithImplicitNamespaceDecl()
    {
        $this->parser->addImplicitNamespace('foo', 'urn:some-namespace-uri');
        $this->readDumpAssertFragment('<foo:tag>xxx</foo:tag>');
    }

    public function testParseFragmentImplicitNamespaceOnLoadIsAddedOnDump()
    {
        $this->readDumpAssertFragment(
            '<foo:tag>xxx</foo:tag>',
            '<foo:tag xmlns:foo="urn:some-namespace-uri">xxx</foo:tag>',
            null, // implicit NS decls in dump
            array('foo' => 'urn:some-namespace-uri') // implicit on load
        );

        // Wie zuvor, aber mit zwei Elementen
        $this->readDumpAssertFragment(
            '<foo:tag>xxx</foo:tag><foo:tag>xxx</foo:tag>',
            '<foo:tag xmlns:foo="urn:some-namespace-uri">xxx</foo:tag><foo:tag xmlns:foo="urn:some-namespace-uri">xxx</foo:tag>',
            null, // implicit NS decls in dump
            array('foo' => 'urn:some-namespace-uri') // implicit on load
        );

        // Wie zuvor, aber mit dem default Namespace
        $this->readDumpAssertFragment(
            '<tag><bar>xxx</bar><bar>xxx</bar></tag>',
            '<tag xmlns="urn:some-uri"><bar>xxx</bar><bar>xxx</bar></tag>',
            null, // implicit NS decls in dump
            array('' => 'urn:some-uri') // implicit on load
        );
    }

    public function testParseFragmentWithImplicitNamespaceOnDump()
    {
        // Wenn der Namespace schon deklariert ist, entfällt die erneute Angabe
        $this->readDumpAssertFragment(
            '<foo:tag xmlns:foo="urn:some-namespace-uri">xxx</foo:tag>',
            '<foo:tag>xxx</foo:tag>',
            array('foo' => 'urn:some-namespace-uri'), // implicit on dump
            null // implicit NS decls on load
        );
    }

    public function testImplicitNamespaceDeclDoesNotInterfereWithFragment()
    {
        $this->parser->addImplicitNamespace('foo', 'urn:some-namespace-uri');
        $this->readDumpAssertFragment('<foo:tag xmlns:foo="urn:some-other-uri">xxx</foo:tag>');
    }

    public function testImplicitNamespaceDeclOnDumpMismatchesFragment()
    {
        $this->readDumpAssertFragment(
            '<foo:tag xmlns:foo="urn:some-namespace-uri">xxx</foo:tag>',
            null, // same as loaded
            array('foo' => 'urn:some-other-uri') // implicit on dump
        );

        $this->readDumpAssertFragment(
            '<tag>xxx</tag>',
            '<tag xmlns="urn:some-uri">xxx</tag>',
            array('foo' => 'urn:some-other-uri'), // implicit on dump
            array('' => 'urn:some-uri') // implicit on load
        );
    }

    public function testImplicitNamespaceDeclDoesNotChangePrefix()
    {
        // Eine Deklaration über ein anderes Prefix ändert nichts - Prefixe werden nicht umgeschrieben
        $this->readDumpAssertFragment(
            '<foo:tag xmlns:foo="urn:some-uri">xxx</foo:tag>',
            null,
            array('bar' => 'urn:some-uri')
        );
    }

    public function testPartialDumpCarriesOverNamespaceDeclaration()
    {
        $document = $this->parser->parseDocument('<root xmlns="urn:test"><foo>test</foo></root>');
        $this->assertEquals(
            '<foo xmlns="urn:test">test</foo>',
            $this->parser->dump($document->documentElement->childNodes->item(0))
        );
    }

    public function testXPathExpressionAndNodeListDump()
    {
        $this->parser->addImplicitNamespace('foo', 'urn:some-uri');
        $f = $this->parser->parseFragment(
            '
                        <foo:bar>test1</foo:bar>
                        <bar>
                            This is the bar tag outside any NS, not a {urn:some-uri}bar tag.
                            <foo:bar>test2</foo:bar>
                        </bar>
                    '
        );
        $xp = $this->parser->createXPath($f);
        $res = $xp->query('//foo:bar', $f);
        $this->assertEquals('<foo:bar>test1</foo:bar><foo:bar>test2</foo:bar>', $this->parser->dump($res));
    }

    /**
     * @expectedException Webfactory\Dom\Exception\EmptyXMLStringException
     */
    public function testParsingEmptyDocumentFails()
    {
        $this->parser->parseDocument('');
    }

    /**
     * @expectedException Webfactory\Dom\Exception\EmptyXMLStringException
     */
    public function testParsingEmptyFragmentFails()
    {
        $this->parser->parseFragment('');
    }

    public function testInvalidCharactersAreTolerated()
    { // Case 12385

        $fragment = "<root>\x0b\t\n</root>";

        $clean = str_replace("\x0b", " ", $fragment);

        $this->assertXmlStringEqualsXmlString($clean, $this->parser->dump($this->parser->parseDocument($fragment)));
        $this->readDumpAssertFragment($fragment, $clean);
    }
}
