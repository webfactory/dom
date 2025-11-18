<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Dom\Test;

use Webfactory\Dom\BaseParsingHelper;
use Webfactory\Dom\Exception\EmptyXMLStringException;
use Webfactory\Dom\Exception\ParsingException;

class BaseParsingHelperTest extends ParsingHelperTestCase
{
    protected function createParsingHelper(): BaseParsingHelper
    {
        return new BaseParsingHelper();
    }

    /**
     * @test
     */
    public function entireDocumentIsPreserved(): void
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

    /**
     * @test
     */
    public function parseFragment(): void
    {
        // Unterschiedliche Fragmente mit verschiedenen NS-Deklarationen
        $this->readDumpAssertFragment('<tag>Foo</tag>');
        $this->readDumpAssertFragment('<foo:tag xmlns:foo="urn:test">Foo</foo:tag>');
        $this->readDumpAssertFragment('<tag xmlns:foo="urn:test"><foo:tag>Foo</foo:tag></tag>');
        $this->readDumpAssertFragment('<tag xmlns="urn:test">Foo</tag>');
    }

    /**
     * @test
     */
    public function defaultNamespaceOnInnerElementIsNotPrefixed(): void
    {
        $this->markTestSkipped(
            'Pending bugs in PHP: https://bugs.php.net/bug.php?id=47530, https://bugs.php.net/bug.php?id=55294'
        );

        /*
         * In this example, "tag" is from the "urn:first" namespace and "inner" is from "urn:second".
         *
         * One could expect that parsing and re-serializing this fragment does not need to change the resulting
         * XML at all. However, some internals in libxml try to merge namespace declarations on common ancestor nodes
         * and lead to the declaration of "default" and "default1" namespace prefixes on the "outer" element;
         * <tag> and <inner> are rewritten to <default:tag> and <default1:inner>.
         * Note that this has nothing to do with the default namespace; it's something internal to libxml.
         *
         *      From xmlNewReconciliedNs in libxml's tree.c:
         *             This function tries to locate a namespace definition in a tree
         *             ancestors, or create a new namespace definition node similar to
         *             @ns trying to reuse the same prefix. However if the given prefix is
         *             null (default namespace) or reused within the subtree defined by
         *             @tree or on one of its ancestors then a new prefix is generated.
         *             Returns the (new) namespace definition or NULL in case of error
         *
         * Now, technically, this does not make a difference as the elements are still associated with the
         * original namespace. However, it does make a difference when embedding <svg> in HTML, for example,
         * when User Agents are not using a XML parser and do not honor the namespace declarations.
         *
         * In this case,
         * http://stackoverflow.com/questions/18467982/are-svg-parameters-such-as-xmlns-and-version-needed
         * suggests to simply omit the xmlns declaration for the <svg> element.
         */
        $this->readDumpAssertFragment('<outer><tag xmlns="urn:first"><inner xmlns="urn:second"></inner></tag></outer>');
    }

    /**
     * @test
     */
    public function parseFragmentWithUnknownNamespaceDecl(): void
    {
        $this->expectException(ParsingException::class);
        $this->readDumpAssertFragment('<foo:tag>xxx</foo:tag>');
    }

    /**
     * @test
     */
    public function parseDocumentWithUnknownNamespaceDecl(): void
    {
        $this->expectException(ParsingException::class);
        $document = $this->parser->parseDocument('<root><foo:fail>failme</foo:fail></root>');
    }

    /**
     * @test
     */
    public function parseFragmentWithImplicitNamespaceDecl(): void
    {
        $this->parser->addImplicitNamespace('foo', 'urn:some-namespace-uri');
        $this->readDumpAssertFragment('<foo:tag>xxx</foo:tag>');
    }

    /**
     * @test
     */
    public function parseFragmentImplicitNamespaceOnLoadIsAddedOnDump(): void
    {
        $this->readDumpAssertFragment(
            '<foo:tag>xxx</foo:tag>',
            '<foo:tag xmlns:foo="urn:some-namespace-uri">xxx</foo:tag>',
            null, // implicit NS decls in dump
            ['foo' => 'urn:some-namespace-uri'] // implicit on load
        );

        // Wie zuvor, aber mit zwei Elementen
        $this->readDumpAssertFragment(
            '<foo:tag>xxx</foo:tag><foo:tag>xxx</foo:tag>',
            '<foo:tag xmlns:foo="urn:some-namespace-uri">xxx</foo:tag><foo:tag xmlns:foo="urn:some-namespace-uri">xxx</foo:tag>',
            null,
            // implicit NS decls in dump
            ['foo' => 'urn:some-namespace-uri'] // implicit on load
        );

        // Wie zuvor, aber mit dem default Namespace
        $this->readDumpAssertFragment(
            '<tag><bar>xxx</bar><bar>xxx</bar></tag>',
            '<tag xmlns="urn:some-uri"><bar>xxx</bar><bar>xxx</bar></tag>',
            null, // implicit NS decls in dump
            ['' => 'urn:some-uri'] // implicit on load
        );
    }

    /**
     * @test
     */
    public function parseFragmentWithImplicitNamespaceOnDump(): void
    {
        // Wenn der Namespace schon deklariert ist, entfällt die erneute Angabe
        $this->readDumpAssertFragment(
            '<foo:tag xmlns:foo="urn:some-namespace-uri">xxx</foo:tag>',
            '<foo:tag>xxx</foo:tag>',
            ['foo' => 'urn:some-namespace-uri'], // implicit on dump
            null // implicit NS decls on load
        );
    }

    /**
     * @test
     */
    public function implicitNamespaceDeclDoesNotInterfereWithFragment(): void
    {
        $this->parser->addImplicitNamespace('foo', 'urn:some-namespace-uri');
        $this->readDumpAssertFragment('<foo:tag xmlns:foo="urn:some-other-uri">xxx</foo:tag>');
    }

    /**
     * @test
     */
    public function implicitNamespaceDeclOnDumpMismatchesFragment(): void
    {
        $this->readDumpAssertFragment(
            '<foo:tag xmlns:foo="urn:some-namespace-uri">xxx</foo:tag>',
            null, // same as loaded
            ['foo' => 'urn:some-other-uri'] // implicit on dump
        );

        $this->readDumpAssertFragment(
            '<tag>xxx</tag>',
            '<tag xmlns="urn:some-uri">xxx</tag>',
            ['foo' => 'urn:some-other-uri'], // implicit on dump
            ['' => 'urn:some-uri'] // implicit on load
        );
    }

    /**
     * @test
     */
    public function implicitNamespaceDeclDoesNotChangePrefix(): void
    {
        // Eine Deklaration über ein anderes Prefix ändert nichts - Prefixe werden nicht umgeschrieben
        $this->readDumpAssertFragment(
            '<foo:tag xmlns:foo="urn:some-uri">xxx</foo:tag>',
            null,
            ['bar' => 'urn:some-uri']
        );
    }

    /**
     * @test
     */
    public function partialDumpCarriesOverNamespaceDeclaration(): void
    {
        $document = $this->parser->parseDocument('<root xmlns="urn:test"><foo>test</foo></root>');
        $this->assertEquals(
            '<foo xmlns="urn:test">test</foo>',
            $this->parser->dump($document->documentElement->childNodes->item(0))
        );
    }

    /**
     * @test
     */
    public function xPathExpressionAndNodeListDump(): void
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
     * @test
     */
    public function parsingEmptyDocumentFails(): void
    {
        $this->expectException(EmptyXMLStringException::class);
        $this->parser->parseDocument('');
    }

    /**
     * @test
     */
    public function parsingEmptyFragmentFails(): void
    {
        $this->expectException(EmptyXMLStringException::class);
        $this->parser->parseFragment('');
    }

    /**
     * @test
     */
    public function invalidCharactersAreTolerated(): void
    {
        $fragment = "<root>\x0b\t\n</root>";

        $clean = str_replace("\x0b", ' ', $fragment);

        $this->assertXmlStringEqualsXmlString($clean, $this->parser->dump($this->parser->parseDocument($fragment)));
        $this->readDumpAssertFragment($fragment, $clean);
    }
}
