<?php

namespace Webfactory\Dom\Test;

abstract class HTMLParsingHelperTest extends ParsingHelperTest {

    public function testScriptWithCDataIsPreserved() {
        $this->readDumpAssertFragment('
            <script type="text/javascript" xml:space="preserve">
            //<![CDATA[
                xxx < > & " \'
            //]]>
            </script>
        ');
    }

    public function testEsiTagIsPreserved() {
        $this->readDumpAssertFragment('<p>Test <esi:include foo="bar"/></p>');
    }

}