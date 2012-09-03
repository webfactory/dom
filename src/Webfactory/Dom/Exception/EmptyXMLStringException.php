<?php

namespace Webfactory\Dom\Exception;

class EmptyXMLStringException extends ParsingHelperException {

    public function __construct() {
        parent::__construct('The given XML-String was empty');
    }

}
