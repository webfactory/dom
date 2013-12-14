<?php
namespace Webfactory\Dom\Test;

class PolyglotHTML5ParsingHelperTest extends HTMLParsingHelperTest {

    protected function createParsingHelper()
    {
        return new \Webfactory\Dom\PolyglotHTML5ParsingHelper();
    }

    public function testVoidTagsArePreservedWhileEmptyTagsAreExpanded()
    {
        $this->readDumpAssertFragment(
            '<area/><base/><br/><col/><command/><embed/><hr/><img/><input/><keygen/><link/><meta/><param/><source/>'
        );

        $this->readDumpAssertFragment('<p/>', '<p></p>');
    }

    public function testHtmlEntitiesSupportedAsConvenience()
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
}
