<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Stream\Streams;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use LastDragon_ru\LaraASP\GraphQL\Stream\Offset;
use LastDragon_ru\LaraASP\GraphQL\Stream\Utils\Page;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\Data\Models\TestObjectSearchable;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\Data\Models\WithTestObject;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\Requirements\RequiresLaravelScout;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\TestCase;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;

use function array_slice;
use function max;
use function usort;

/**
 * @internal
 */
#[CoversClass(Scout::class)]
#[RequiresLaravelScout]
final class ScoutTest extends TestCase {
    use WithQueryLog;
    use WithTestObject;

    // <editor-fold desc="Prepare">
    // =========================================================================
    /**
     * @inheritDoc
     */
    #[Override]
    protected function defineEnvironment($app): void {
        parent::defineEnvironment($app);

        $app->make(Repository::class)
            ->set('scout.driver', 'database');
    }
    // </editor-fold>

    // <editor-fold desc="Tests">
    // =========================================================================
    public function testGetItems(): void {
        $limit   = $this->getFaker()->numberBetween(1, 4);
        $limit   = max(1, $limit);
        $offset  = $this->getFaker()->numberBetween(0, 2);
        $offset  = max(0, $offset);
        $builder = TestObjectSearchable::search();
        $stream  = new Scout($builder, $builder->model->getKeyName(), $limit, new Offset('path', $offset, null));
        $objects = [
            TestObjectSearchable::factory()->create(),
            TestObjectSearchable::factory()->create(),
            TestObjectSearchable::factory()->create(),
            TestObjectSearchable::factory()->create(),
            TestObjectSearchable::factory()->create(),
        ];

        usort(
            $objects,
            static fn (TestObjectSearchable $a, TestObjectSearchable $b): int => $a->getKey() <=> $b->getKey(),
        );

        $expected = array_slice($objects, $offset, $limit);
        $query    = $this->getQueryLog();
        $items    = $stream->getItems();
        $page     = new Page($limit, $offset);
        $limit    = $page->pageSize;
        $offset   = $limit * ($page->pageNumber - 1);

        $stream->getItems(); // should be cached

        self::assertEquals($expected, [...$items]);
        self::assertQueryLogEquals(
            [
                [
                    'query'    => 'select count(*) as aggregate from "test_objects"',
                    'bindings' => [],
                ],
                [
                    'query'    => <<<SQL
                        select *
                        from "test_objects"
                        order by "id" asc, "test_objects"."id" desc
                        limit {$limit} offset {$offset}
                    SQL
                    ,
                    'bindings' => [],
                ],
            ],
            $query,
        );
    }

    public function testGetLength(): void {
        $count   = $this->getFaker()->numberBetween(1, 5);
        $builder = TestObjectSearchable::search();
        $stream  = new Scout($builder, $builder->model->getKeyName(), 1, new Offset('path', 0, null));

        for ($i = 0; $i < $count; $i++) {
            TestObjectSearchable::factory()->create();
        }

        $query  = $this->getQueryLog();
        $length = $stream->getLength();

        $stream->getLength(); // should be cached

        self::assertEquals($count, $length);
        self::assertQueryLogEquals(
            [
                [
                    'query'    => 'select count(*) as aggregate from "test_objects"',
                    'bindings' => [],
                ],
                [
                    'query'    => <<<'SQL'
                        select *
                        from "test_objects"
                        order by "id" asc, "test_objects"."id" desc
                        limit 1 offset 0
                    SQL
                    ,
                    'bindings' => [],
                ],
            ],
            $query,
        );
    }

    public function testGetCurrentOffset(): void {
        $builder = TestObjectSearchable::search();
        $offset  = new Offset('path', 0, null);
        $stream  = new Scout($builder, 'key', 2, $offset);

        self::assertSame($offset, $stream->getCurrentOffset());
    }

    public function testGetPreviousOffset(): void {
        // Offset = 0
        $builder = TestObjectSearchable::search();
        $offset  = new Offset('path', 0, null);
        $stream  = new Scout($builder, 'key', 2, $offset);

        self::assertNull($stream->getPreviousOffset());

        // Offset > 0
        $offset = new Offset('path', 1, null);
        $stream = new Scout($builder, 'key', 2, $offset);

        self::assertEquals(
            new Offset('path', 0, null),
            $stream->getPreviousOffset(),
        );
    }

    public function testGetNextOffset(): void {
        // Page.end = 0 & no more pages
        $builder   = TestObjectSearchable::search();
        $offset    = new Offset('path', 0, null);
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $paginator
            ->shouldReceive('hasMorePages')
            ->once()
            ->andReturn(false);
        $stream = Mockery::mock(Scout::class, [$builder, 'key', 2, $offset]);
        $stream->shouldAllowMockingProtectedMethods();
        $stream->makePartial();
        $stream
            ->shouldReceive('getPaginator')
            ->once()
            ->andReturn($paginator);

        self::assertNull($stream->getNextOffset());

        // Page.end > 0 & no more pages
        $builder = TestObjectSearchable::search();
        $offset  = new Offset('path', 1234, null);
        $stream  = Mockery::mock(Scout::class, [$builder, 'key', 123, $offset]);
        $stream->shouldAllowMockingProtectedMethods();
        $stream->makePartial();
        $stream
            ->shouldReceive('getPaginator')
            ->never();

        self::assertEquals(
            new Offset('path', 1234 + 123, null),
            $stream->getNextOffset(),
        );

        // Page.end = 0 & has more pages
        $builder   = TestObjectSearchable::search();
        $offset    = new Offset('path', 0, null);
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $paginator
            ->shouldReceive('hasMorePages')
            ->once()
            ->andReturn(true);
        $stream = Mockery::mock(Scout::class, [$builder, 'key', 2, $offset]);
        $stream->shouldAllowMockingProtectedMethods();
        $stream->makePartial();
        $stream
            ->shouldReceive('getPaginator')
            ->once()
            ->andReturn($paginator);

        self::assertEquals(
            new Offset('path', 2, null),
            $stream->getNextOffset(),
        );
    }
    // </editor-fold>
}
