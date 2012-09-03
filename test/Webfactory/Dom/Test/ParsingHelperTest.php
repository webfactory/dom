<?php
namespace Webfactory\Dom\Test;

abstract class ParsingHelperTest extends \PHPUnit_Framework_TestCase {

    /** @var \Webfactory\Dom\BaseParsingHelper */
    protected $parser;

    abstract protected function createParsingHelper();

    protected function setUp() {
        $this->parser = $this->createParsingHelper();
    }

    protected function readDumpAssertFragment($fragment, $result = null, $declaredNamespacesInDump = null, $declaredNamespacesInRead = null) {
        if (!$result) $result = $fragment;
        $f = $this->parser->parseFragment($fragment, $declaredNamespacesInRead);
        $dump = $this->parser->dump($f, $declaredNamespacesInDump);
        $this->assertEquals(trim($result), trim($dump));
    }

}
