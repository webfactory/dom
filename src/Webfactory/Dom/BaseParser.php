<?php

namespace Webfactory\Dom;

use Webfactory\Dom\EmptyXMLStringException;
use Webfactory\Dom\Exception\ParsingException;

abstract class BaseParser {

    const XHTMLNS = 'http://www.w3.org/1999/xhtml';

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

        return $d;
    }

    public function parseFragment($fragmentXml) {
        if (!$fragmentXml)
            throw new EmptyXMLStringException();

        $document = $this->parseDocument($this->wrapFragment($fragmentXml));
        $document->createdFromFragment = true;
        return $document;
    }

    public function dumpDocument(\DOMDocument $document) {
        if ($document->createdFromFragment) {
            $root = $document->documentElement;

            $dump = '';
            // Wir nutzen an dieser Stelle explizit nicht
            // $this->dumpElement, da wir aus performance-
            // gruenden $this->fixDump nur einmal rufen wollen.
            foreach ($root->childNodes as $n)
                $dump .= $document->saveXML($n);
        } else {
            $dump = $document->saveXML();
        }

        return $this->fixDump($dump);
    }

    public function dumpElement(\DOMNode $element) {
        return $this->fixDump($element->ownerDocument->saveXML($element));
    }

    public function createXPath(\DOMDocument $document, array $namespaces = array('html' => 'http://www.w3.org/1999/xhtml')) {
        $xpath = new \DOMXPath($document);
        foreach ($namespaces as $nsName => $nsURI) {
            // TODO: Automatisch alle Namespaces registrieren (aus $document parsen)...
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

    abstract protected function wrapFragment($fragmentXml);
    abstract protected function fixDump($dump);


}
