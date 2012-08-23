<?php

namespace Webfactory\Dom;

use Webfactory\Dom\Exception\EmptyXMLStringException;
use Webfactory\Dom\Exception\ParsingException;

abstract class BaseParser {

    protected $standardNamespaces = array(
        'html' => 'http://www.w3.org/1999/xhtml',
        'esi' => 'http://www.edge-delivery.org/esi/1.0',
        'fb' => 'http://www.facebook.com/2008/fbml'
    );
    protected $defaultNamespace = 'html';

    public function parseDocument($xml) {
        if (!$xml)
            throw new EmptyXMLStringException();

        $d = $this->createDOMDocument();

        $errorHandling = libxml_use_internal_errors(true);

        $d->loadXML($xml);

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($errorHandling);

        if ($d->documentElement == null || $errors)
            throw new ParsingException($errors, $d);

        $d->nonStandardNamespaces = $this->extractNonStandardNamespaces($xml);

        return $d;
    }

    public function parseFragment($fragmentXml) {
        if (!$fragmentXml)
            throw new EmptyXMLStringException();

        $xml = $this->wrapFragment($fragmentXml);

        $document = $this->parseDocument($xml);
        $document->createdFromFragment = true;
        $document->nonStandardNamespaces = $this->extractNonStandardNamespaces($xml);

        return $document;
    }

    public function dumpDocument(\DOMDocument $document) {
        if (isset($document->createdFromFragment)) {
            $root = $document->documentElement;

            $dump = '';
            foreach ($root->childNodes as $n) {
                $dump .= $this->dumpElement($n);
            }
            return $dump;
        } else {
            return $this->fixDump($document->saveXML());
        }
    }

    public function dumpElement(\DOMNode $element) {
        if ($element instanceof \DOMElement) {
            if (isset($element->ownerDocument->nonStandardNamespaces)) {
                foreach ($element->ownerDocument->nonStandardNamespaces as $ns => $uri) {
                    $element->setAttribute('xmlns:' . $ns, $uri);
                }
            }
        }
        return $this->fixDump($element->ownerDocument->saveXML($element));
    }

    public function createXPath(\DOMDocument $document) {
        $xpath = new \DOMXPath($document);
        $namespaces = $this->standardNamespaces;
        if (isset($document->nonStandardNamespaces))
            $namespaces += $document->nonStandardNamespaces;

        foreach ($namespaces as $nsName => $nsURI) {
            $xpath->registerNamespace($nsName, $nsURI);
        }

        return $xpath;
    }

    protected function createDOMDocument() {
        $d = new \DOMDocument();
        $d->resolveExternals = true; // Externe Dateien (aus der DTD) bei der Auflösung von Entities beachten. Falls nicht, sind die Entities nicht bekannt.
        $d->substituteEntities = true; // Entities auflösen und die Zeichen, die sie darstellen, einsetzen.
        return $d;
    }

    protected function extractNonStandardNamespaces($xml) {
        preg_match_all('(xmlns:([^=]+)="([^"]+)")', $xml, $matches, PREG_SET_ORDER);
        $unknownNamespaces = array();
        foreach ($matches as $match) {
            $key = $match[1];
            if (!in_array($key, array_keys($this->standardNamespaces))) {
                $unknownNamespaces[$key] = $match[2];
            }
        }
        return $unknownNamespaces;
    }

    protected function wrapWithRootNode($fragmentXML) {
        $wrap = '<html';
        foreach ($this->standardNamespaces as $key => $uri) {
            $wrap .= " xmlns:$key=\"$uri\"";
        }
        $wrap .= ' xmlns="' . $this->standardNamespaces[$this->defaultNamespace] . '">';
        $wrap .= $fragmentXML;
        $wrap .= '</html>';
        return $wrap;
    }

    abstract protected function wrapFragment($fragmentXml);
    abstract protected function fixDump($dump);

}
