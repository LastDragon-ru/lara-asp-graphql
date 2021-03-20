<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SearchBy;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Contracts\Operator;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\Equal;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Comparison\GreaterThan;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Complex\ComplexOperator;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Logical\LogicalAnd;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Logical\LogicalOperator;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Logical\LogicalOr;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Operators\Not;
use LastDragon_ru\LaraASP\GraphQL\Testing\BuilderDataProvider;
use LastDragon_ru\LaraASP\GraphQL\Testing\TestCase;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;

use function count;

/**
 * @internal
 * @coversDefaultClass \LastDragon_ru\LaraASP\GraphQL\SearchBy\SearchBuilder
 */
class SearchBuilderTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::process
     *
     * @dataProvider dataProviderProcess
     *
     * @param array<mixed> $conditions
     */
    public function testProcess(
        array|Exception $expected,
        Closure $builder,
        array $conditions = [],
        string $tableAlias = null,
    ): void {
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        $search  = new SearchBuilder([
            $this->app->make(Not::class),
            $this->app->make(Equal::class),
            $this->app->make(GreaterThan::class),
            $this->app->make(LogicalAnd::class),
            $this->app->make(LogicalOr::class),
        ]);
        $builder = $builder($this);
        $builder = $search->process($builder, $conditions, $tableAlias);
        $actual  = [
            'sql'      => $builder->toSql(),
            'bindings' => $builder->getBindings(),
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::processNotOperator
     *
     * @dataProvider dataProviderProcessNotOperator
     *
     * @param array<mixed> $expected
     */
    public function testProcessNotOperator(array $expected, Closure $builder): void {
        $not = Mockery::mock(Not::class);
        $not
            ->shouldReceive('getName')
            ->once()
            ->andReturn('not');
        $not
            ->shouldReceive('apply')
            ->once()
            ->andReturnUsing(
                static function (
                    EloquentBuilder|QueryBuilder $builder,
                    Closure $nested,
                ): EloquentBuilder|QueryBuilder {
                    return $builder->whereRaw('not (1 = 1)');
                },
            );

        $builder = $builder($this);
        $search  = new SearchBuilder([$not]);
        $builder = $search->processNotOperator($builder, $not, [1, 2]);
        $actual  = [
            'sql'      => $builder->toSql(),
            'bindings' => $builder->getBindings(),
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::processComparison
     *
     * @dataProvider dataProviderProcessComparison
     *
     * @param array<mixed> $conditions
     */
    public function testProcessComparison(
        array|Exception $expected,
        Closure $builder,
        string $property,
        array $conditions = [],
        string $tableAlias = null,
    ): void {
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        $search  = new SearchBuilder([
            $this->app->make(Not::class),
            $this->app->make(Equal::class),
            $this->app->make(GreaterThan::class),
        ]);
        $builder = $builder($this);
        $builder = $search->processComparison($builder, $property, $conditions, $tableAlias);
        $actual  = [
            'sql'      => $builder->toSql(),
            'bindings' => $builder->getBindings(),
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::processLogicalOperator
     *
     * @dataProvider dataProviderProcessLogicalOperator
     *
     * @param array<mixed> $expected
     */
    public function testProcessLogicalOperator(array $expected, Closure $builder): void {
        $conditions = [1, 2];
        $logical    = Mockery::mock(LogicalOperator::class, Operator::class);

        $logical
            ->shouldReceive('getName')
            ->once()
            ->andReturn('and');

        $search = new SearchBuilder([$logical]);

        $logical
            ->shouldReceive('apply')
            ->times(count($conditions))
            ->andReturnUsing(
                static function (
                    EloquentBuilder|QueryBuilder $builder,
                    Closure $nested,
                ): EloquentBuilder|QueryBuilder {
                    return $builder->whereRaw('(1 = 1)');
                },
            );

        $builder = $builder($this);
        $builder = $search->processLogicalOperator($builder, $logical, $conditions);
        $actual  = [
            'sql'      => $builder->toSql(),
            'bindings' => $builder->getBindings(),
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::processComplexOperator
     *
     * @dataProvider dataProviderProcessComplexOperator
     *
     * @param array<mixed> $expected
     */
    public function testProcessComplexOperator(array $expected, Closure $builder): void {
        $conditions = [1, 2, 4];
        $property   = 'property';
        $complex    = Mockery::mock(ComplexOperator::class, Operator::class);

        $complex
            ->shouldReceive('getName')
            ->once()
            ->andReturn('test');

        $search = new SearchBuilder([$complex]);

        $complex
            ->shouldReceive('apply')
            ->with($search, Mockery::any(), $property, $conditions)
            ->once()
            ->andReturnUsing(
                static function (
                    SearchBuilder $search,
                    EloquentBuilder|QueryBuilder $builder,
                ): EloquentBuilder|QueryBuilder {
                    return $builder->whereRaw('1 = 1');
                },
            );

        $builder = $builder($this);
        $builder = $search->processComplexOperator($builder, $complex, $property, $conditions);
        $actual  = [
            'sql'      => $builder->toSql(),
            'bindings' => $builder->getBindings(),
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getNotOperator
     */
    public function testGetNotOperator(): void {
        $search  = new SearchBuilder([$this->app->make(Not::class)]);
        $with    = ['not' => 'yes'];
        $without = [];

        $this->assertNotNull($search->getNotOperator($with));
        $this->assertEmpty($with);

        $this->assertNull($search->getNotOperator($without));
    }

    /**
     * @covers ::getComplexOperator
     */
    public function testGetComplexOperator(): void {
        $complex = new class() implements Operator, ComplexOperator {
            /**
             * @inheritdoc
             */
            public function apply(
                SearchBuilder $search,
                EloquentBuilder|QueryBuilder $builder,
                string $property,
                array $conditions,
            ): EloquentBuilder|QueryBuilder {
                return $builder;
            }

            public function getName(): string {
                return 'test';
            }

            /**
             * @inheritdoc
             */
            public function getDefinition(array $map, string $scalar, bool $nullable): string {
                return '';
            }
        };
        $search  = new SearchBuilder([$complex]);

        $this->assertSame($complex, $search->getComplexOperator([
            $complex->getName() => 'yes',
        ]));

        $this->assertNull($search->getComplexOperator([]));
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderProcess(): array {
        return (new CompositeDataProvider(
            new BuilderDataProvider(),
            new ArrayDataProvider([
                'valid condition'                  => [
                    [
                        'sql'      => 'select * from "tmp" where (not ((("a" != ?) and ((("a" = ?) or ("b" != ?))))))',
                        'bindings' => [
                            1,
                            2,
                            3,
                        ],
                    ],
                    [
                        'not' => 'yes',
                        'and' => [
                            [
                                'a' => [
                                    'eq'  => 1,
                                    'not' => 'yes',
                                ],
                            ],
                            [
                                'or' => [
                                    [
                                        'a' => [
                                            'eq' => 2,
                                        ],
                                    ],
                                    [
                                        'b' => [
                                            'eq'  => 3,
                                            'not' => 'yes',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    null,
                ],
                'valid condition with table alias' => [
                    [
                        'sql'      => 'select * from "tmp" where ('.
                            'not ((("alias"."a" != ?) and ((("alias"."a" = ?) or ("alias"."b" != ?)))))'.
                            ')',
                        'bindings' => [
                            1,
                            2,
                            3,
                        ],
                    ],
                    [
                        'not' => 'yes',
                        'and' => [
                            [
                                'a' => [
                                    'eq'  => 1,
                                    'not' => 'yes',
                                ],
                            ],
                            [
                                'or' => [
                                    [
                                        'a' => [
                                            'eq' => 2,
                                        ],
                                    ],
                                    [
                                        'b' => [
                                            'eq'  => 3,
                                            'not' => 'yes',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'alias',
                ],
            ]),
        ))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderProcessNotOperator(): array {
        return (new CompositeDataProvider(
            new BuilderDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    [
                        'sql'      => 'select * from "tmp" where (not (1 = 1))',
                        'bindings' => [],
                    ],
                ],
            ]),
        ))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderProcessLogicalOperator(): array {
        return (new CompositeDataProvider(
            new BuilderDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    [
                        'sql'      => 'select * from "tmp" where ((1 = 1) and (1 = 1))',
                        'bindings' => [],
                    ],
                ],
            ]),
        ))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderProcessComplexOperator(): array {
        return (new CompositeDataProvider(
            new BuilderDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    [
                        'sql'      => 'select * from "tmp" where (1 = 1)',
                        'bindings' => [],
                    ],
                ],
            ]),
        ))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderProcessComparison(): array {
        return (new CompositeDataProvider(
            new BuilderDataProvider(),
            new ArrayDataProvider([
                'empty'                            => [
                    new SearchLogicException(
                        'Search condition cannot be empty.',
                    ),
                    'property',
                    [],
                    null,
                ],
                'empty (not only)'                 => [
                    new SearchLogicException(
                        'Search condition cannot be empty.',
                    ),
                    'property',
                    [
                        'not' => 'yes',
                    ],
                    null,
                ],
                'more than one condition'          => [
                    new SearchLogicException(
                        'Only one comparison operator allowed, found: `eq`, `in`.',
                    ),
                    'property',
                    [
                        'eq' => 'yes',
                        'in' => [1, 2],
                    ],
                    null,
                ],
                'unknown operator'                 => [
                    new SearchLogicException(
                        'Operator `unk` not found.',
                    ),
                    'property',
                    [
                        'unk' => 'yes',
                    ],
                    null,
                ],
                'operator cannot be used with not' => [
                    new SearchLogicException(
                        'Operator `gt` cannot be used with `not`.',
                    ),
                    'property',
                    [
                        'gt'  => 'yes',
                        'not' => 'yes',
                    ],
                    null,
                ],
                'valid condition'                  => [
                    [
                        'sql'      => 'select * from "tmp" where "property" = ?',
                        'bindings' => [
                            123,
                        ],
                    ],
                    'property',
                    [
                        'eq' => 123,
                    ],
                    null,
                ],
                'valid condition with table alias' => [
                    [
                        'sql'      => 'select * from "tmp" where "alias"."property" = ?',
                        'bindings' => [
                            123,
                        ],
                    ],
                    'property',
                    [
                        'eq' => 123,
                    ],
                    'alias',
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}