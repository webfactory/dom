<?php
namespace Webfactory\Dom\Test;

abstract class ParserTest extends \PHPUnit_Framework_TestCase {

    /** @var \Webfactory\Dom\BaseParser */
    protected $parser;
    protected $xmlString = null;
    protected $document = null;
    /** @var \DOMXPath */ protected $xpath = null;

    abstract protected function createParser();

    protected function setUp() {
        $this->parser = $this->createParser();

        if ($this->xmlString) {
            $this->document = $this->parser->parseDocument($this->xmlString);
            $this->xpath = $this->parser->createXPath($this->document);
        }
    }


}
