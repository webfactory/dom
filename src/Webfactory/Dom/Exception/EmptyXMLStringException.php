<?php

namespace Webfactory\Dom;

class EmptyXMLStringException extends \Exception {

    public function __construct() {
        parent::__construct('The given XML-String was empty!');
    }

}
