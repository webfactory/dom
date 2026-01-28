<?php

/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Dom\Test;

use PHPUnit\Framework\TestCase;

abstract class ParsingHelperTestCase extends TestCase
{
    /** @var \Webfactory\Dom\BaseParsingHelper */
    protected $parser;

    abstract protected function createParsingHelper();

    protected function setUp(): void
    {
        $this->parser = $this->createParsingHelper();
    }

    protected function readDumpAssertFragment(
        $fragment,
        $result = null,
        $declaredNamespacesInDump = null,
        $declaredNamespacesInRead = null,
    ): void {
        if (!$result) {
            $result = $fragment;
        }
        $f = $this->parser->parseFragment($fragment, $declaredNamespacesInRead);
        $dump = $this->parser->dump($f, $declaredNamespacesInDump);
        $this->assertEquals(trim($result), trim($dump));
    }
}
