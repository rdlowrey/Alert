<?php

namespace Amp\Test\Loop;

use Amp\Loop\UvLoop;
use Amp\Test\LoopTest;

/**
 * @requires extension uv
 */
class UvLoopLoopTest extends LoopTest {
    public function getFactory() {
        return function () {
            return new UvLoop;
        };
    }
}