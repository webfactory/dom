<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Dom;

abstract class HTMLParsingHelper extends BaseParsingHelper {

    protected $implicitNamespaces;

    public function __construct()
    {
        $this->implicitNamespaces = $this->defineImplicitNamespaces();

        libxml_set_external_entity_loader(function ($public, $system, $context) {
            if (isset($public)) {
                $catalogDir = __DIR__ . '/../../../xml-catalog/';
                switch ($public) {
                    case '-//W3C//DTD XHTML 1.0 Strict//EN':
                        return $catalogDir . 'xhtml1-strict.dtd';
                    case '-//W3C//ENTITIES Latin 1 for XHTML//EN':
                        return $catalogDir . 'xhtml-lat1.ent';
                    case '-//W3C//ENTITIES Symbols for XHTML//EN':
                        return $catalogDir . 'xhtml-symbol.ent';
                    case '-//W3C//ENTITIES Special for XHTML//EN':
                        return $catalogDir . 'xhtml-special.ent';
                    case '-//W3C//DTD XHTML 1.0 Transitional//EN':
                        return $catalogDir . 'xhtml1-transitional.dtd';
                }
            }

            return $system;
        });
    }

    protected function wrapFragment($fragment, $declaredNamespaces)
    {
        return "<html {$this->xmlNamespaceDeclaration($declaredNamespaces)}>$fragment</html>";
    }

    protected function defineImplicitNamespaces(): array
    {
        if (phpversion('xml') >= '8.1.21') {
            return [
                'html' => 'http://www.w3.org/1999/xhtml', // für XPath
                ''     => 'http://www.w3.org/1999/xhtml', // default ns
                'hx'   => 'http://purl.org/NET/hinclude' // fuer HInclude http://mnot.github.io/hinclude/; ein Weg um z.B. Controller in Symfony per Ajax zu embedden
            ];
        }

        return [
            ''     => 'http://www.w3.org/1999/xhtml', // default ns
            'html' => 'http://www.w3.org/1999/xhtml', // für XPath
            'hx'   => 'http://purl.org/NET/hinclude' // fuer HInclude http://mnot.github.io/hinclude/; ein Weg um z.B. Controller in Symfony per Ajax zu embedden
        ];
    }
}
