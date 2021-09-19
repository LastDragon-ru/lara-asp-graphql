<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SortBy\Directives;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Laravel\Scout\Builder as ScoutBuilder;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Database\Sorter as DatabaseSorter;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Manipulator;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Scout\Sorter as ScoutSorter;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Scout\ScoutBuilderDirective;
use Nuwave\Lighthouse\Support\Contracts\ArgBuilderDirective;
use Nuwave\Lighthouse\Support\Contracts\ArgManipulator;

class Directive extends BaseDirective implements ArgManipulator, ArgBuilderDirective, ScoutBuilderDirective {
    public const Name          = 'SortBy';
    public const TypeDirection = 'SortByDirection';

    public function __construct(
        protected Container $container,
    ) {
        // empty
    }

    public static function definition(): string {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            """
            Convert Input into Sort Clause.
            """
            directive @sortBy on ARGUMENT_DEFINITION
        GRAPHQL;
    }

    public function manipulateArgDefinition(
        DocumentAST &$documentAST,
        InputValueDefinitionNode &$argDefinition,
        FieldDefinitionNode &$parentField,
        ObjectTypeDefinitionNode &$parentType,
    ): void {
        $this->container
            ->make(Manipulator::class, ['document' => $documentAST])
            ->update($argDefinition);
    }

    /**
     * @inheritdoc
     */
    public function handleBuilder($builder, mixed $value): EloquentBuilder|QueryBuilder {
        return $this->container->make(DatabaseSorter::class)->handle($builder, $value);
    }

    public function handleScoutBuilder(ScoutBuilder $builder, mixed $value): ScoutBuilder {
        return $this->container->make(ScoutSorter::class)->handle($builder, $value);
    }
}
