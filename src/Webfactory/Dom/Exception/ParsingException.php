<?php

namespace Webfactory\Dom\Exception;

class ParsingException extends ParsingHelperException {

    protected $errors;
    protected $document;
	protected $xmlInput;

    public function __construct($errors, \DOMDocument $document, $xmlInput) {
        $this->errors = $errors;
        $this->document = $document;
		$this->xmlInput = $xmlInput;
        parent::__construct($this->errorsToString($errors));
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getDocument() {
        return $this->document;
    }
	
	public function getXmlInput() {
		return $this->xmlInput;
	}

    protected function errorsToString($errors) {
		if (!is_array($errors)) $errors = array($errors);
		$message = '';
		foreach ($errors as $error) {
			if ($error instanceof \LibXMLError) {
				$message .= $error->message . ' in line ' . $error->line;
			} elseif(is_string($error)) {
				$message .= $error;
			}
			$message .= "\n";
		}
		return $message;
    }

}
