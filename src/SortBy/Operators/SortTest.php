<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SortBy\Operators;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Laravel\Scout\Builder as ScoutBuilder;
use LastDragon_ru\LaraASP\GraphQL\Builder\Context;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\BuilderFieldResolver;
use LastDragon_ru\LaraASP\GraphQL\Builder\Field;
use LastDragon_ru\LaraASP\GraphQL\Config\Config;
use LastDragon_ru\LaraASP\GraphQL\PackageConfig;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Config as SortByConfig;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Contracts\Sorter;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Contracts\SorterFactory;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Directives\Directive;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Enums\Direction;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Enums\Nulls;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Sorters\EloquentSorter;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Sorters\QuerySorter;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Sorters\ScoutSorter;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\DataProviders\BuilderDataProvider;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\OperatorTests;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\Requirements\RequiresLaravelScout;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\TestCase;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Mockery\MockInterface;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use function implode;

/**
 * @internal
 *
 * @phpstan-import-type BuilderFactory from BuilderDataProvider
 */
#[CoversClass(Sort::class)]
final class SortTest extends TestCase {
    use OperatorTests;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @param array{query: string, bindings: array<array-key, mixed>} $expected
     * @param BuilderFactory                                          $builderFactory
     * @param Closure(static): Argument                               $argumentFactory
     * @param Closure(static): Context|null                           $contextFactory
     * @param Closure(object, Field): string|null                     $resolver
     */
    #[DataProvider('dataProviderCall')]
    public function testCall(
        array $expected,
        Closure $builderFactory,
        Field $field,
        Closure $argumentFactory,
        ?Closure $contextFactory,
        ?Closure $resolver,
    ): void {
        $this->testDatabaseOperator(
            Directive::class,
            $expected,
            $builderFactory,
            $field,
            $argumentFactory,
            $contextFactory,
            $resolver,
        );
    }

    public function testCallEloquentBuilder(): void {
        $this->useGraphQLSchema('type Query { test: String! @mock}');

        $this->override(EloquentSorter::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('isNullsSupported')
                ->once()
                ->andReturn(false);
            $mock
                ->shouldReceive('sort')
                ->once()
                ->andReturns();
        });

        $directive = $this->app()->make(Directive::class);
        $field     = new Field();
        $operator  = $this->app()->make(Sort::class);
        $argument  = $this->getGraphQLArgument(
            'Test',
            Direction::Asc,
            'enum Test { Asc }',
        );
        $context   = new Context();
        $builder   = Mockery::mock(EloquentBuilder::class);

        $operator->call($directive, $builder, $field, $argument, $context);
    }

    public function testCallQueryBuilder(): void {
        $this->useGraphQLSchema('type Query { test: String! @mock}');

        $this->override(QuerySorter::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('isNullsSupported')
                ->once()
                ->andReturn(false);
            $mock
                ->shouldReceive('sort')
                ->once();
        });

        $directive = $this->app()->make(Directive::class);
        $field     = new Field();
        $operator  = $this->app()->make(Sort::class);
        $argument  = $this->getGraphQLArgument(
            'Test',
            Direction::Asc,
        );
        $context   = new Context();
        $builder   = Mockery::mock(QueryBuilder::class);

        $operator->call($directive, $builder, $field, $argument, $context);
    }

    #[RequiresLaravelScout]
    public function testCallScoutBuilder(): void {
        $this->useGraphQLSchema('type Query { test: String! @mock}');

        $this->override(ScoutSorter::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('isNullsSupported')
                ->once()
                ->andReturn(false);
            $mock
                ->shouldReceive('sort')
                ->once();
        });

        $directive = $this->app()->make(Directive::class);
        $field     = new Field();
        $operator  = $this->app()->make(Sort::class);
        $argument  = $this->getGraphQLArgument(
            'Test',
            Direction::Asc,
            'enum Test { Asc }',
        );
        $context   = new Context();
        $builder   = Mockery::mock(ScoutBuilder::class);

        $operator->call($directive, $builder, $field, $argument, $context);
    }

    /**
     * @param Closure(static): Sorter<object> $sorterFactory
     * @param Closure(static): Context        $contextFactory
     */
    #[DataProvider('dataProviderGetNulls')]
    public function testGetNulls(
        ?Nulls $expected,
        ?SortByConfig $configuration,
        Closure $sorterFactory,
        Closure $contextFactory,
        Direction $direction,
    ): void {
        if ($configuration !== null) {
            $this->setConfiguration(PackageConfig::class, static function (Config $config) use ($configuration): void {
                $config->sortBy = $configuration;
            });
        }

        $sorter   = $sorterFactory($this);
        $context  = $contextFactory($this);
        $config   = $this->app()->make(PackageConfig::class);
        $factory  = Mockery::mock(SorterFactory::class);
        $resolver = Mockery::mock(BuilderFieldResolver::class);
        $operator = Mockery::mock(Sort::class, [$config, $factory, $resolver]);
        $operator->shouldAllowMockingProtectedMethods();
        $operator->makePartial();

        self::assertSame($expected, $operator->getNulls($sorter, $context, $direction));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<array-key, mixed>
     */
    public static function dataProviderCall(): array {
        $factory = static function (self $test): Argument {
            $test->useGraphQLSchema(
                <<<'GRAPHQL'
                type Query {
                    test(input: Test @sortBy): String! @all
                }

                input Test {
                    a: Int!
                    b: String
                }
                GRAPHQL,
            );

            return $test->getGraphQLArgument(
                'SortByTypeDirection!',
                Direction::Desc,
            );
        };

        return (new CompositeDataProvider(
            new BuilderDataProvider(),
            new ArrayDataProvider([
                'field'              => [
                    [
                        'query'    => 'select * from "test_objects" order by "a" desc',
                        'bindings' => [],
                    ],
                    new Field('a'),
                    $factory,
                    static function (): Context {
                        return new Context();
                    },
                    null,
                ],
                'nulls from Context' => [
                    [
                        'query'    => 'select * from "test_objects" order by "a" DESC NULLS FIRST',
                        'bindings' => [],
                    ],
                    new Field('a'),
                    $factory,
                    static function (): Context {
                        return (new Context())->override([
                            SortContextNulls::class => new SortContextNulls(Nulls::First),
                        ]);
                    },
                    null,
                ],
                'resolver'           => [
                    [
                        'query'    => 'select * from "test_objects" order by "resolved__a" desc',
                        'bindings' => [],
                    ],
                    new Field('a'),
                    $factory,
                    static function (): Context {
                        return new Context();
                    },
                    static function (object $builder, Field $field): string {
                        return 'resolved__'.implode('__', $field->getPath());
                    },
                ],
            ]),
        ))->getData();
    }

    /**
     * @return array<string, array{
     *      ?Nulls,
     *      ?SortByConfig,
     *      Closure(static): Sorter<object>,
     *      Closure(static): Context,
     *      Direction,
     *      }>
     */
    public static function dataProviderGetNulls(): array {
        $contextFactory   = static function (): Context {
            return new Context();
        };
        $getSorterFactory = static function (bool $nullsSortable): Closure {
            return static function () use ($nullsSortable): Sorter {
                return new readonly class($nullsSortable) implements Sorter {
                    public function __construct(
                        private bool $nullsSortable,
                    ) {
                        // empty
                    }

                    #[Override]
                    public function isNullsSupported(): bool {
                        return $this->nullsSortable;
                    }

                    #[Override]
                    public function sort(
                        object $builder,
                        Field $field,
                        Direction $direction,
                        ?Nulls $nulls = null,
                    ): object {
                        throw new Exception('Should not be called.');
                    }
                };
            };
        };

        return [
            'default'                            => [
                null,
                null,
                $getSorterFactory(true),
                $contextFactory,
                Direction::Asc,
            ],
            'nulls are not sortable'             => [
                null,
                new SortByConfig(nulls: Nulls::First),
                $getSorterFactory(false),
                $contextFactory,
                Direction::Asc,
            ],
            'nulls are sortable (asc)'           => [
                Nulls::Last,
                new SortByConfig(nulls: Nulls::Last),
                $getSorterFactory(true),
                $contextFactory,
                Direction::Asc,
            ],
            'nulls are sortable (desc)'          => [
                Nulls::Last,
                new SortByConfig(nulls: Nulls::Last),
                $getSorterFactory(true),
                $contextFactory,
                Direction::Desc,
            ],
            'nulls are sortable (separate)'      => [
                Nulls::First,
                new SortByConfig(nulls: [
                    Direction::Asc->value  => Nulls::Last,
                    Direction::Desc->value => Nulls::First,
                ]),
                $getSorterFactory(true),
                $contextFactory,
                Direction::Desc,
            ],
            'nulls are sortable (Context null)'  => [
                null,
                new SortByConfig(nulls: Nulls::Last),
                $getSorterFactory(true),
                static function (): Context {
                    return (new Context())->override([
                        SortContextNulls::class => new SortContextNulls(null),
                    ]);
                },
                Direction::Desc,
            ],
            'nulls are sortable (Context first)' => [
                Nulls::First,
                new SortByConfig(nulls: Nulls::Last),
                $getSorterFactory(true),
                static function (): Context {
                    return (new Context())->override([
                        SortContextNulls::class => new SortContextNulls(Nulls::First),
                    ]);
                },
                Direction::Desc,
            ],
        ];
    }
    //</editor-fold>
}
