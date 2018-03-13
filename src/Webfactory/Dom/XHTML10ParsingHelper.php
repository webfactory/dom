<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Dom;

class XHTML10ParsingHelper extends HTMLParsingHelper
{
    public function __construct()
    {
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
                }
            }

            return $system;
        });
    }

    protected function wrapFragment($fragment, $declaredNamespaces)
    {
        return
            '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'
            . parent::wrapFragment(
                $fragment,
                $declaredNamespaces
            );
    }
}
