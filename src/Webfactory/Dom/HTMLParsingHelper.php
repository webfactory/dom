<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Dom;

abstract class HTMLParsingHelper extends BaseParsingHelper {

    protected $implicitNamespaces = array(
        ''     => 'http://www.w3.org/1999/xhtml', // default ns
        'html' => 'http://www.w3.org/1999/xhtml', // für XPath
        'hx'   => 'http://purl.org/NET/hinclude' // fuer HInclude http://mnot.github.io/hinclude/; ein Weg um z.B. Controller in Symfony per Ajax zu embedden
    );

    protected function wrapFragment($fragment, $declaredNamespaces)
    {
        return "<html {$this->xmlNamespaceDeclaration($declaredNamespaces)}>$fragment</html>";
    }
}
