<?php

/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Dom\Test;

use Webfactory\Dom\PolyglotHTML5ParsingHelper;

class PolyglotHTML5ParsingHelperTest extends HTMLParsingHelperTestCase
{
    protected function createParsingHelper(): PolyglotHTML5ParsingHelper
    {
        return new PolyglotHTML5ParsingHelper();
    }

    /**
     * @test
     */
    public function voidTagsArePreservedWhileEmptyTagsAreExpanded(): void
    {
        $this->readDumpAssertFragment(
            '<area/><base/><br/><col/><command/><embed/><hr/><img/><input/><keygen/><link/><meta/><param/><source/>'
        );

        $this->readDumpAssertFragment('<p/>', '<p></p>');
    }

    /**
     * @test
     */
    public function htmlEntitiesSupportedAsConvenience(): void
    {
        // webfactory Case 12739,
        // http://dev.w3.org/html5/html-xhtml-author-guide/#named-entity-references
        /*
            Dieser Test zeigt zwei Dinge:
            - Named entity references wie &auml; u. ä. sind in Polyglot HTML5 nicht verfügbar. Der
              ParsingHelper ermoeglicht es aus Bequemlichkeit und Kompatibilitaet mit "legacy"-Content
              (der als XHTML1 angelegt wurde), die Entitaeten beim Parsen in UTF-8 zu expandieren.
            - Die in XML eingebauten Entitaeten lt, gt, apos und quot werden (von der libxml) selektiv
              beibehalten, wenn es notwendig ist. In PCDATA scheint das für lt/gt der Fall zu sein,
              innerhalb eines Attributs (das mit " eingeleitet wurde) ist auch das quot geschuetzt.
        */
        $f = $this->parser->parseFragment(
            '<p>&auml; x &ouml; x &uuml; &quot; &lt; &gt; &apos; <x foo="&quot; &lt; &gt; &apos;"></x></p>'
        );
        $d = $this->parser->dump($f);

        $this->assertEquals('<p>ä x ö x ü " &lt; &gt; \' <x foo="&quot; &lt; &gt; \'"></x></p>', $d);
    }

    /**
     * @test
     */
    public function svgNamespaceIsNotReconciled(): void
    {
        /*
         * libxml2 will attempt (under which circumstances?) to reconciliate namespace declarations, that is, find
         * namespaces used by several nodes and move these declarations up the DOM tree.
         *
         * This also affects the default namespace as commonly used by <svg> inlined in HTML5 documents. As you cannot
         * move the default namespace away from an element, libxml turns it into a regular "named" namespace and
         * chooses a namespace prefix like "default" (sic), possibly followed by a number, for it. This happens
         * in xmlNewReconciliedNs, see https://github.com/GNOME/libxml2/blob/35e83488505d501864826125cfe6a7950d6cba78/tree.c#L6230.
         *
         * The result is that markup like <svg xmlns="http://www.w3.org/2000/svg"><path ...></path></svg> will be turned
         * into <default:svg><default:path>...</default:path></default:svg>, with xmlns:default="http://www.w3.org/2000/svg"
         * somewhere up the tree.
         *
         * This is reported (not for the SVG namespace, but the general case) at https://bugs.php.net/bug.php?id=55294
         * and https://bugs.php.net/bug.php?id=47530, with the conclusion that it would need to be fixed in libxml2.
         *
         * libxml2, on the other hand, will argue that the result is perfectly fine when applying XML semantics. The
         * problem is that browsers may or may not make this distinction. According to https://stackoverflow.com/questions/18467982/are-svg-parameters-such-as-xmlns-and-version-needed,
         * it might depend on wheter the page is served as application/xhtml+xml or text/html. In the latter case,
         * XML namespace semantics do not apply.
         *
         * For <svg> in HTML5, a possible workaround is to completely remove the XML NS declaration: This is
         * possible as <svg> is included in HTML5 as a "foreign element" (https://www.w3.org/TR/html5/syntax.html#foreign-elements).
         * That is, the elements from the SVG namespace are also valid in HTML5.
         *
         * Instead of completely removing the xmlns, our current workaround is to move the namespace declaration
         * "out of the way" when parsing the XML and fixing it up again later when dumping the XML.
         */
        $this->readDumpAssertFragment(
            '<div><svg xmlns="http://www.w3.org/2000/svg" class="x" width="300" height="150" viewBox="0 0 300 150"><path fill="#FF7949" d="M300 5.49c0-2.944-1.057-4.84-2.72-5.49h-2.92c-.79.247-1.632.67-2.505 1.293L158.145 96.56c-4.48 3.19-11.81 3.19-16.29 0L8.146 1.292C7.27.67 6.43.247 5.64 0H2.72C1.056.65 0 2.546 0 5.49V150h300V5.49z"></path></svg></div>'
        );
    }

    /**
     * @test
     * @dataProvider provideXpathForDocuments
     */
    public function xpathParseDocument($xml, $xpathExpression): void
    {
        $document = $this->parser->parseDocument($xml);
        $xpath = $this->parser->createXPath($document);

        $domNodeList = $xpath->query($xpathExpression);

        self::assertCount(1, $domNodeList);
        self::assertSame('test', $domNodeList[0]->textContent);
    }

    public static function provideXpathForDocuments(): ?\Generator
    {
        yield 'HTML document that does not use a default namespace' => [
            /*
                In this document, nodes are not in a namespace at all. Thus, we have to use the
                XPath expression "//p" which searches for an item _not associated with a namespace_.

                Note that this _should not be done in practice_, since HTML5 has a built-in, undeclared "native"
                default namespace for the <html> element.

                The libxml XML parser, however, does not know about HTML5 - only about XML. This is why the
                Polyglot spec (https://www.w3.org/TR/html-polyglot/#h4_element-level-namespaces) states that

                <html xmlns="http://www.w3.org/1999/xhtml">

                ... should be used to achieve the same semantics for HTML5-aware and XML-only parsers.
            */
            '<html><body><p>test</p></body></html>',
            '//p',
        ];

        yield 'HTML document that uses a default namespace' => [
            /*
                In this document, a default namespace is used. All nodes are associated with this namespace.
                The XPath expression has to match namespaced nodes, and "//p" would be a node _without_ a
                namespace. -> We have to register a namespace on the Xpath expression, and use its prefix.

                If we don't give an explicit namespace mapping when creating the xpath expression, the
                \Webfactory\Dom\BaseParsingHelper::createXPath() will register the ParsingHelper's implicit
                namespaces for us as convenience. That includes the "html" namespace prefix for the URI
                according to the current HTML variant (XHTML vs HTML5) in use.
            */
            '<html xmlns="http://www.w3.org/1999/xhtml"><body><p>test</p></body></html>',
            '//html:p',
        ];

        yield 'HTML document with explicit namespace' => [
            /*
                Basically, as before, this time using an explicit namespace prefix.
            */
            '<html xmlns:foo="http://www.w3.org/1999/xhtml"><foo:body><foo:p>test</foo:p></foo:body></html>',
            '//html:p',
        ];
    }

    /**
     * @test
     * @dataProvider provideXpathForFragments
     */
    public function xpathParseFragment($xmlFragment, $xpathExpression): void
    {
        $fragment = $this->parser->parseFragment($xmlFragment);
        $xpath = $this->parser->createXPath($fragment);

        $domNodeList = $xpath->query($xpathExpression);

        self::assertCount(1, $domNodeList);
        self::assertSame('test', $domNodeList[0]->textContent);
    }

    public static function provideXpathForFragments(): ?\Generator
    {
        yield 'default namespace assumed for fragments' => [
            /*
                When BaseParsingHelper::parseFragment() is used without passing a mapping of
                namespaces, a 'default' assumption is made depending on the ParsingHelper instance.

                For HTML5, this assumes fragment elements without namespace declarations live in the
                http://www.w3.org/1999/xhtml namespace URI; this corresponds to the 'html' convenience
                prefix set up in xpath expressions.
            */
            '<p>test</p>',
            '//html:p',
        ];
    }
}
