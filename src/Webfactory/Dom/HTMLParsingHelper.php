<?php

namespace Webfactory\Dom;

abstract class HTMLParsingHelper extends BaseParsingHelper {

    protected $implicitNamespaces = array(
        '' => 'http://www.w3.org/1999/xhtml', // default ns
        'html' => 'http://www.w3.org/1999/xhtml', // für XPath
        'esi' => 'http://www.edge-delivery.org/esi/1.0'
    );

    protected function wrapFragment($fragment, $declaredNamespaces) {
        return "<html {$this->xmlNamespaceDeclaration($declaredNamespaces)}>$fragment</html>";
    }

}
