#!/usr/bin/env php
<?php

require __DIR__ . '/../../vendor/autoload.php';

use Amp\Delayed;
use Amp\Loop;
use Amp\PipelineSource;

Loop::run(function () {
    try {
        /** @psalm-var PipelineSource<int> $source */
        $source = new PipelineSource;

        Loop::defer(function () use ($source) {
            // Source emits all values at once without awaiting back-pressure.
            $source->emit(1);
            $source->emit(2);
            $source->emit(3);
            $source->emit(4);
            $source->emit(5);
            $source->emit(6);
            $source->emit(7);
            $source->emit(8);
            $source->emit(9);
            $source->emit(10);
            $source->complete();
        });

        $pipeline = $source->pipe();

        while (null !== $value = yield $pipeline->continue()) {
            \printf("Pipeline source yielded %d\n", $value);
            yield new Delayed(100); // Listener consumption takes 100 ms.
        }
    } catch (\Throwable $exception) {
        \printf("Exception: %s\n", (string) $exception);
    }
});
