<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Dom\Test;

abstract class HTMLParsingHelperTest extends ParsingHelperTest
{
    /**
     * @test
     */
    public function scriptWithCDataIsPreserved()
    {
        $this->readDumpAssertFragment('
            <script type="text/javascript" xml:space="preserve">
            //<![CDATA[
                xxx < > & " \'
            //]]>
            </script>
        ');
    }

    /**
     * @test
     */
    public function esiTagIsPreserved()
    {
        $this->readDumpAssertFragment('<p>Test <esi:include foo="bar"/></p>');
    }

    /**
     * @test
     */
    public function esiTagWithXMLSpecialCharsIsPreserved()
    {
        $this->readDumpAssertFragment('<p>Test <esi:include foo="http://foo.bar?one=two&three=four"/></p>');
    }
}
