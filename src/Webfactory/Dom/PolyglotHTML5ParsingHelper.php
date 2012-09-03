<?php

namespace Webfactory\Dom;

use Webfactory\Dom\BaseParser;

class PolyglotHTML5ParsingHelper extends HTMLParsingHelper {

    // HTML-Entities fixen als Bequemlichkeit für legacy (Case 12739)
    protected function sanitize($xml) {
        return mb_convert_encoding(parent::sanitize($xml), 'UTF-8', 'HTML-ENTITIES');
    }

    protected function fixDump($dump) {
        // http://www.w3.org/TR/html-polyglot/#empty-elements
        static $voidElements = array(
            'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img',
            'input', 'keygen', 'link', 'meta', 'param', 'source', 'esi');

        preg_match_all('_<((?!\w+:)(\w+)[^>]*)/>_', $dump, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            if (!in_array($m[2], $voidElements))
                $dump = str_replace($m[0], "<{$m[1]}></{$m[2]}>", $dump);
        }

        return $dump;
    }

}
