<?php

namespace Amp\Test\Pipeline;

use Amp\Delayed;
use Amp\Failure;
use Amp\PHPUnit\AsyncTestCase;
use Amp\PHPUnit\TestException;
use Amp\Pipeline;
use Amp\Success;

class FromIterableTest extends AsyncTestCase
{
    const TIMEOUT = 10;

    public function testSuccessfulPromises()
    {
        $expected = \range(1, 3);
        $pipeline = Pipeline\fromIterable([new Success(1), new Success(2), new Success(3)]);

        while (null !== $value = yield $pipeline->continue()) {
            $this->assertSame(\array_shift($expected), $value);
        }
    }

    public function testFailedPromises()
    {
        $exception = new \Exception;
        $iterator = Pipeline\fromIterable([new Failure($exception), new Failure($exception)]);

        $this->expectExceptionObject($exception);

        yield $iterator->continue();
    }

    public function testMixedPromises()
    {
        $exception = new TestException;
        $expected = \range(1, 2);
        $pipeline = Pipeline\fromIterable([new Success(1), new Success(2), new Failure($exception), new Success(4)]);

        try {
            while (null !== $value = yield $pipeline->continue()) {
                $this->assertSame(\array_shift($expected), $value);
            }
            $this->fail("A failed promise in the iterable should fail the pipeline and be thrown from continue()");
        } catch (TestException $reason) {
            $this->assertSame($exception, $reason);
        }

        $this->assertEmpty($expected);
    }

    public function testPendingPromises()
    {
        $expected = \range(1, 4);
        $pipeline = Pipeline\fromIterable([
            new Delayed(30, 1),
            new Delayed(10, 2),
            new Delayed(20, 3),
            new Success(4),
        ]);

        while (null !== $value = yield $pipeline->continue()) {
            $this->assertSame(\array_shift($expected), $value);
        }
    }

    public function testTraversable()
    {
        $expected = \range(1, 4);
        $generator = (static function () {
            foreach (\range(1, 4) as $value) {
                yield $value;
            }
        })();

        $pipeline = Pipeline\fromIterable($generator);

        while (null !== $value = yield $pipeline->continue()) {
            $this->assertSame(\array_shift($expected), $value);
        }

        $this->assertEmpty($expected);
    }

    /**
     * @dataProvider provideInvalidIteratorArguments
     */
    public function testInvalid($arg)
    {
        $this->expectException(\TypeError::class);

        Pipeline\fromIterable($arg);
    }

    public function provideInvalidIteratorArguments(): array
    {
        return [
            [null],
            [new \stdClass],
            [32],
            [false],
            [true],
            ["string"],
        ];
    }

    public function testInterval()
    {
        $count = 3;
        $pipeline = Pipeline\fromIterable(\range(1, $count), self::TIMEOUT);

        $i = 0;
        while (null !== $value = yield $pipeline->continue()) {
            $this->assertSame(++$i, $value);
        }

        $this->assertSame($count, $i);
    }

    /**
     * @depends testInterval
     */
    public function testSlowConsumer()
    {
        $count = 5;
        $pipeline = Pipeline\fromIterable(\range(1, $count), self::TIMEOUT);

        for ($i = 0; $value = yield $pipeline->continue(); ++$i) {
            yield new Delayed(self::TIMEOUT * 2);
        }

        $this->assertSame($count, $i);
    }
}
