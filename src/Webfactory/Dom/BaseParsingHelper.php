<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Dom;

use Webfactory\Dom\Exception\EmptyXMLStringException;
use Webfactory\Dom\Exception\ParsingException;
use Webfactory\Dom\Exception\ParsingHelperException;

class BaseParsingHelper
{
    protected $implicitNamespaces = [];

    public function setImplicitNamespaces(array $ns)
    {
        $this->implicitNamespaces = $ns;
    }

    public function addImplicitNamespace($prefix, $uri)
    {
        $this->implicitNamespaces[$prefix] = $uri;
    }

    public function getImplicitNamespaces()
    {
        return $this->implicitNamespaces;
    }

    /*
     * Ein XML-Dokument ist "vollständig" im Hinblick auf die Namespaces -
     * alle im Dokument verwendeten Namespaces und ihre Prefixe sind im
     * Dokument deklariert. Diese Methode braucht also keine Namespace-
     * Deklarationen übergeben bekommen und braucht auch nicht auf
     * $this->implicitNamespaces zurückgreifen.
     */
    public function parseDocument($xml)
    {
        return $this->parseSanitizedDocument($this->sanitize($xml));
    }

    protected function parseSanitizedDocument($xml)
    {
        if (!$xml) {
            throw new EmptyXMLStringException();
        }

        $d = $this->createDOMDocument();

        $errorHandling = libxml_use_internal_errors(true);

        $d->loadXML($xml);

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($errorHandling);

        if (null == $d->documentElement || $errors) {
            throw new ParsingException($errors, $d, $xml);
        }

        return $d;
    }

    /*
     * Bei der Verarbeitung eines Fragments ist unklar, welcher Namespace der
     * default-Namespace (für Elemente ohne Prefix) ist. Auch kann es sein, dass
     * Elemente im Fragment ein Namespace-Prefix tragen, dessen Deklaration aber
     * außerhalb des Fragments liegt (was passiert, wenn wir einfach als String-Operation
     * einen Teil aus einem XML-Dokument ausschneiden).
     *
     * In diesem Fall kann über $declaredNamespaces eine Liste von Prefix -> Namespace-URL
     * übergeben werden, die bei der Verarbeitung zu Grunde zu legen ist. XML-Elemente mit
     * Prefixen aus dieser Liste werden dann ohne Fehler importiert und den entsprechenden
     * Namespace-URLs zugeordnet.
     *
     * Wird die Liste nicht übergeben, gelten die auf dem Parser als "implicitNamespaces"
     * gesetzten Zuordnungen.
     */
    public function parseFragment($fragmentXml, $declaredNamespaces = null)
    {
        return $this->parseSanitizedFragment($this->sanitize($fragmentXml), $declaredNamespaces);
    }

    protected function parseSanitizedFragment($fragmentXml, $declaredNamespaces)
    {
        if (!$fragmentXml) {
            throw new EmptyXMLStringException();
        }

        $xml = $this->wrapFragment($fragmentXml, $declaredNamespaces ?: $this->implicitNamespaces);

        $document = $this->parseSanitizedDocument($xml);
        $document->createdFromFragment = true;

        return $document;
    }

    protected function xmlNamespaceDeclaration($ns)
    {
        if (!$ns) {
            return '';
        }

        $s = '';
        foreach ($ns as $prefix => $url) {
            if ('' == $prefix) {
                $attr = 'xmlns';
            } else {
                $attr = "xmlns:$prefix";
            }
            $s .= " $attr=\"$url\"";
        }

        return $s;
    }

    /*
     * Schreibt ein DOMDocument, eine DOMNodeList oder eine DOMNode
     * zurück in einen String.
     *
     * Ein DOMDocument ist im Hinblick auf XML-Namespaces immer "vollständig",
     * alle notwendigen Deklarationen sind in ihm enthalten.
     *
     * Bei DOMNodeList oder DOMNodes ist das anders:
     *
     * Wird eine Liste von $declaredNamespaces (Prefix -> URL)
     * übergeben, so werden bei XML-Knoten mit passenden Zuordnungen
     * die XML Namespace-Deklarationen ausgelassen (kompakter, redundanzfrei).
     *
     * Das setzt voraus, dass an der Stelle, an der der XML-String eingesetzt
     * wird, die entsprechenden Namespaces deklariert sind.
     *
     * Wird $declaredNamespaces ausgelassen, so werden die $implicitNamespaces
     * dieses Parsers verwendet. Es ist dann notwendig, dass sich diese Deklarationen
     * im Ziel-XML-Dokument befinden, beispielsweise auf dem Root-Element.
     */
    public function dump($obj, $declaredNamespaces = null)
    {
        if ($obj instanceof \DOMAttr) {
            return $obj->value;
        }

        if ($obj instanceof \DOMNodeList && $obj->item(0) instanceof \DOMAttr) {
            $s = '';
            foreach ($obj as $attr) {
                if (!$attr instanceof \DOMAttr) {
                    throw new ParsingHelperException('A DOMNodeList must contain only DOMAttr or DOMNode nodes');
                }
                $s .= $attr->value.' ';
            }

            return trim($s);
        }

        if ($obj instanceof \DOMDocument) {
            if ($obj instanceof Document && $obj->createdFromFragment) {
                return $this->dump($obj->documentElement->childNodes, $declaredNamespaces);
            } else {
                return $this->fixDump($obj->saveXML());
            }
        }

        if ($obj instanceof \DOMNodeList || $obj instanceof \DOMNode) {
            $d = $this->parseSanitizedDocument(
                $this->wrapFragment('', $declaredNamespaces ?: $this->implicitNamespaces)
            ); // create empty document

            if ($obj instanceof \DOMNodeList) {
                foreach ($obj as $node) {
                    if ($node instanceof \DOMAttr) {
                        throw new ParsingHelperException('A DOMNodeList must contain only DOMAttr or DOMNode nodes');
                    }
                    $d->documentElement->appendChild($d->importNode($node, true));
                }
            } else {
                $d->documentElement->appendChild($d->importNode($obj, true));
            }

            $s = '';
            foreach ($d->documentElement->childNodes as $node) {
                $s .= $d->saveXML($node);
            }

            return $this->fixDump($s);
        }
    }

    /*
     * Erzeugt eine neue DOMXPath-Instanz zur Suche im übergebenen
     * DOMDocument. Die übergebenen Namespace-Mappings (Prefix->URL)
     * werden auf dem XPath-Ausdruck zur Verfügung gestellt.
     * Werden keine gesonderten Namespace-Mappings übergeben, so
     * werden die auf diesem Parser hinterlegten $implicitNamespaces
     * genutzt.
     *
     * Das ist ein bisschen Convenience und widerspricht etwas den
     * üblichen Verwendungsmustern für einen XPath-Ausdruck:
     * Ein Klient würde den XPath-Ausdruck erzeugen, sich überlegen,
     * Elemente welches Namespace er treffen möchte, die Namespace-URI
     * mit einem Prefix im XPath-Ausdruck registrieren und dann unter Verwendung dieses
     * selbstgewählten Prefix den Ausdruck angeben. Es ist für den
     * Klienten an der Stelle nicht relevant oder notwendig, Annahmen
     * über die Prefixe im Dokument zu treffen oder sich auf die
     * gleichen Prefixe zu beziehen.
     */
    public function createXPath(\DOMDocument $document, $namespaceMappings = null)
    {
        $xpath = new \DOMXPath($document);

        $ns = $namespaceMappings ?: $this->implicitNamespaces;

        foreach ($ns as $prefix => $url) {
            if ($prefix) {
                $xpath->registerNamespace($prefix, $url);
            }
        }

        return $xpath;
    }

    protected function createDOMDocument()
    {
        $d = new Document();
        $d->resolveExternals = true; // Externe Dateien (aus der DTD) bei der Auflösung von Entities beachten. Falls nicht, sind die Entities nicht bekannt.
        $d->substituteEntities = false; // Entities nicht expandieren

        return $d;
    }

    protected function wrapFragment($fragment, $declaredNamespaces)
    {
        return "<root {$this->xmlNamespaceDeclaration($declaredNamespaces)}>$fragment</root>";
    }

    protected function fixDump($dump)
    {
        return preg_replace('_\<\!\[CDATA\[((?:<esi:[^>]+>)+)\]\]>_', '$1', $dump);
    }

    protected function sanitize($s)
    {
        $s = preg_replace('_<esi:[^>]+>_', '<![CDATA[$0]]>', $s); // escape <esi:...> placeholders

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', ' ', $s);
    }
}
