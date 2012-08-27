<?php
namespace Webfactory\Dom\Test;

abstract class XHTML10ParserTest extends ParserTest {

    final protected function createParser() {
        return new \Webfactory\Dom\XHTML10Parser();
    }

}
