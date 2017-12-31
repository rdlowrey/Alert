<?php

namespace Amp\Internal;

use Amp\Iterator;
use Amp\Promise;

/**
 * Wraps a set of callables that implement the iterator methods.
 */
class PrivateIterator implements Iterator
{
    /** @var callable */
    private $advance;

    /** @var callable */
    private $current;

    /** @var callable */
    private $dispose;

    public function __construct(callable $advance, callable $current, callable $dispose)
    {
        $this->advance = $advance;
        $this->current = $current;
        $this->dispose = $dispose;
    }

    public function __destruct()
    {
        ($this->dispose)();
    }

    public function advance(): Promise
    {
        return ($this->advance)();
    }

    public function getCurrent()
    {
        return ($this->current)();
    }
}
