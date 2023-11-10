<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Dom;

class PolyglotHTML5ParsingHelper extends HTMLParsingHelper
{
    // HTML-Entities fixen als Bequemlichkeit für legacy (Case 12739)
    protected function sanitize($xml)
    {
        $xml = parent::sanitize($xml);

        $xml = str_replace('xmlns="http://www.w3.org/2000/svg"', '_xmlns="http://www.w3.org/2000/svg"', $xml);

        $escaped = str_replace(
            ['&amp;', '&#38;', '&lt;', '&#60;', '&gt;', '&#62;', '&quot;', '&#34;', '&apos;', '&#39;'],
            ['&amp;amp;', '&amp;amp;', '&amp;lt;', '&amp;lt;', '&amp;gt;', '&amp;gt;', '&amp;quot;', '&amp;quot;', '&amp;apos;', '&amp;apos;'],
            $xml
        );

        $decoded = html_entity_decode($escaped, \ENT_QUOTES, 'UTF-8');

        return $decoded;
    }

    protected function fixDump($dump)
    {
        $dump = parent::fixDump($dump);

        // http://www.w3.org/TR/html-polyglot/#empty-elements
        static $voidElements = [
            'area',
            'base',
            'br',
            'col',
            'command',
            'embed',
            'hr',
            'img',
            'input',
            'keygen',
            'link',
            'meta',
            'param',
            'source',
        ];

        preg_match_all('_<((?!\w+:)(\w+)[^>]*)/>_', $dump, $matches, \PREG_SET_ORDER);

        foreach ($matches as $m) {
            if (!\in_array($m[2], $voidElements)) {
                $dump = str_replace($m[0], "<{$m[1]}></{$m[2]}>", $dump);
            }
        }

        $dump = str_replace('_xmlns="http://www.w3.org/2000/svg"', 'xmlns="http://www.w3.org/2000/svg"', $dump);

        return $dump;
    }
}
