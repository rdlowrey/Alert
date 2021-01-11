<?php

namespace Amp\Internal;

use Amp\Pipeline;
use Amp\Promise;

/**
 * Wraps an EmitSource instance that has public methods to emit, complete, and fail into an object that only allows
 * access to the public API methods and automatically calls EmitSource::destroy() when the object is destroyed.
 *
 * @internal
 *
 * @template-covariant TValue
 * @template-implements Pipeline<TValue>
 */
final class AutoDisposingPipeline implements Pipeline
{
    /** @var EmitSource<TValue, null> */
    private $source;

    public function __construct(EmitSource $source)
    {
        $this->source = $source;
    }

    public function __destruct()
    {
        $this->source->destroy();
    }

    /**
     * @inheritDoc
     */
    public function continue(): Promise
    {
        return $this->source->continue();
    }

    /**
     * @inheritDoc
     */
    public function dispose()
    {
        $this->source->dispose();
    }
}
