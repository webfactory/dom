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
