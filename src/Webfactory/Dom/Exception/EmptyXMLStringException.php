<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Dom\Exception;

class EmptyXMLStringException extends ParsingHelperException {

    public function __construct() {
        parent::__construct('The given XML-String was empty');
    }

}
