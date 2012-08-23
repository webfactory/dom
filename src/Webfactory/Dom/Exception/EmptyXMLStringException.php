<?php

namespace Webfactory\Dom\Exception;

class EmptyXMLStringException extends \Exception {

    public function __construct() {
        parent::__construct('The given XML-String was empty!');
    }

}
