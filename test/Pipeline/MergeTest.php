<?php

namespace Amp\Test\Pipeline;

use Amp\AsyncGenerator;
use Amp\Delayed;
use Amp\PHPUnit\AsyncTestCase;
use Amp\PHPUnit\TestException;
use Amp\Pipeline;

class MergeTest extends AsyncTestCase
{
    public function getArrays(): array
    {
        return [
            [[\range(1, 3), \range(4, 6)], [1, 4, 2, 5, 3, 6]],
            [[\range(1, 5), \range(6, 8)], [1, 6, 2, 7, 3, 8, 4, 5]],
            [[\range(1, 4), \range(5, 10)], [1, 5, 2, 6, 3, 7, 4, 8, 9, 10]],
        ];
    }

    /**
     * @dataProvider getArrays
     *
     * @param array $pipelines
     * @param array $expected
     */
    public function testMerge(array $pipelines, array $expected)
    {
        $pipelines = \array_map(static function (array $iterator): Pipeline {
            return Pipeline\fromIterable($iterator);
        }, $pipelines);

        $pipeline = Pipeline\merge($pipelines);

        while (null !== $value = yield $pipeline->continue()) {
            $this->assertSame(\array_shift($expected), $value);
        }
    }

    /**
     * @depends testMerge
     */
    public function testMergeWithDelayedYields()
    {
        $pipelines = [];
        $values1 = [new Delayed(10, 1), new Delayed(50, 2), new Delayed(70, 3)];
        $values2 = [new Delayed(20, 4), new Delayed(40, 5), new Delayed(60, 6)];
        $expected = [1, 4, 5, 2, 6, 3];

        $pipelines[] = new AsyncGenerator(function (callable $yield) use ($values1) {
            foreach ($values1 as $value) {
                yield $yield(yield $value);
            }
        });

        $pipelines[] = new AsyncGenerator(function (callable $yield) use ($values2) {
            foreach ($values2 as $value) {
                yield $yield(yield $value);
            }
        });

        $pipeline = Pipeline\merge($pipelines);

        while (null !== $value = yield $pipeline->continue()) {
            $this->assertSame(\array_shift($expected), $value);
        }
    }

    /**
     * @depends testMerge
     */
    public function testMergeWithFailedPipeline()
    {
        $exception = new TestException;
        $generator = new AsyncGenerator(static function (callable $yield) use ($exception) {
            yield $yield(1); // Emit once before failing.
            throw $exception;
        });

        $pipeline = Pipeline\merge([$generator, Pipeline\fromIterable(\range(1, 5))]);

        try {
            /** @noinspection PhpStatementHasEmptyBodyInspection */
            while (yield $pipeline->continue()) {
                ;
            }
            $this->fail("The exception used to fail the pipeline should be thrown from continue()");
        } catch (TestException $reason) {
            $this->assertSame($exception, $reason);
        }
    }

    public function testNonPipeline()
    {
        $this->expectException(\TypeError::class);

        /** @noinspection PhpParamsInspection */
        Pipeline\merge([1]);
    }
}
