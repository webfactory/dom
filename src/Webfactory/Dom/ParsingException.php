<?php

namespace Webfactory\Dom;

class ParsingException extends \Exception {

    public function __construct($errors) {
        parent::__construct($this->errorToString($errors, ''));
    }

    protected function errorToString($errors, $message) {
        if ($errors instanceof \LibXMLError) {
            $message .= $errors->message . ' in ' . $errors->file . ':' . $errors->line;
        } else if (is_array($errors)) {
            foreach ($errors as $error) {
                $message .= $this->errorToString($error, $message);
            }
        } else if(is_string($errors)) {
            $message .= $errors;
        }
        return $message;
    }

}
