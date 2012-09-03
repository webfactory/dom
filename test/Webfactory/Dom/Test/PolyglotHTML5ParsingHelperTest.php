<?php
namespace Webfactory\Dom\Test;

class PolyglotHTML5ParsingHelperTest extends HTMLParsingHelperTest {

    protected function createParsingHelper() {
        return new \Webfactory\Dom\PolyglotHTML5ParsingHelper();
    }

    public function testVoidTagsArePreservedWhileEmptyTagsAreExpanded() {
        $this->readDumpAssertFragment('
            <area/><base/><br/><col/><command/><embed/><hr/><img/><input/><keygen/><link/><meta/><param/><source/>
        ');
     
        $this->readDumpAssertFragment('<p/>', '<p></p>');
    }

    public function testHtmlEntitiesSupportedAsConvenience() {
        // webfactory Case 12739,
        // http://dev.w3.org/html5/html-xhtml-author-guide/#named-entity-references
        $this->readDumpAssertFragment(
            '<p>&auml; x &ouml; x &uuml;</p>',
            '<p>ä x ö x ü</p>'
        );
    }
    
}
