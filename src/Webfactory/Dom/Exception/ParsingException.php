<?php

namespace Webfactory\Dom\Exception;

class ParsingException extends ParsingHelperException {

    protected $errors;
    protected $document;

    public function __construct($errors, \DOMDocument $document) {
        $this->errors = $errors;
        $this->document = $document;
        parent::__construct($this->errorToString($errors, ''));
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getDocument() {
        return $this->document;
    }

    protected function errorToString($errors, $message) {
        if ($errors instanceof \LibXMLError) {
            $message .= $errors->message . ' in ' . $errors->file . ':' . $errors->line;
        } else if (is_array($errors)) {
            foreach ($errors as $error) {
                $message .= $this->errorToString($error, $message) . "\n";
            }
        } else if (is_string($errors)) {
            $message .= $errors;
        }
        return $message;
    }

}
