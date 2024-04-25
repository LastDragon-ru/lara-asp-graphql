<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SortBy\Directives;

use GraphQL\Language\AST\ListTypeNode;
use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\NonNullTypeNode;
use LastDragon_ru\LaraASP\Core\Application\ConfigResolver;
use LastDragon_ru\LaraASP\Core\Application\ContainerResolver;
use LastDragon_ru\LaraASP\GraphQL\Builder\BuilderInfoDetector;
use LastDragon_ru\LaraASP\GraphQL\Builder\Context\HandlerContextOperators;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Context;
use LastDragon_ru\LaraASP\GraphQL\Builder\Directives\HandlerDirective;
use LastDragon_ru\LaraASP\GraphQL\Builder\Manipulator;
use LastDragon_ru\LaraASP\GraphQL\Builder\Sources\InterfaceFieldArgumentSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Sources\ObjectFieldArgumentSource;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Exceptions\FailedToCreateSortClause;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Operators;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Operators\Root;
use LastDragon_ru\LaraASP\GraphQL\Utils\ArgumentFactory;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Scout\ScoutBuilderDirective;
use Nuwave\Lighthouse\Support\Contracts\ArgBuilderDirective;
use Nuwave\Lighthouse\Support\Contracts\ArgManipulator;
use Override;

use function str_starts_with;

class Directive extends HandlerDirective implements ArgManipulator, ArgBuilderDirective, ScoutBuilderDirective {
    final public const Name = 'SortBy';

    public function __construct(
        protected readonly ContainerResolver $container,
        protected readonly ConfigResolver $config,
        BuilderInfoDetector $detector,
        ArgumentFactory $argumentFactory,
    ) {
        parent::__construct($detector, $argumentFactory);
    }

    #[Override]
    public static function definition(): string {
        $name = DirectiveLocator::directiveName(static::class);

        return <<<GRAPHQL
            """
            Use Input as Sort Conditions for the current Builder.
            """
            directive @{$name} on ARGUMENT_DEFINITION
        GRAPHQL;
    }

    // <editor-fold desc="Manipulate">
    // =========================================================================
    #[Override]
    protected function isTypeName(string $name): bool {
        return str_starts_with($name, self::Name);
    }

    #[Override]
    protected function getArgDefinitionType(
        Manipulator $manipulator,
        DocumentAST $document,
        ObjectFieldArgumentSource|InterfaceFieldArgumentSource $argument,
        Context $context,
    ): ListTypeNode|NamedTypeNode|NonNullTypeNode {
        $context = $context->override([
            HandlerContextOperators::class => new HandlerContextOperators(
                new Operators($this->container, $this->config),
            ),
        ]);
        $type    = $this->getArgumentTypeDefinitionNode($manipulator, $document, $argument, $context, Root::class);

        if (!$type) {
            throw new FailedToCreateSortClause($argument);
        }

        return $type;
    }
    // </editor-fold>
}
