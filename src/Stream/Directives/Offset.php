<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Stream\Directives;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\Parser;
use LastDragon_ru\LaraASP\Core\Utils\Cast;
use LastDragon_ru\LaraASP\GraphQL\Builder\Context;
use LastDragon_ru\LaraASP\GraphQL\Builder\ManipulatorFactory;
use LastDragon_ru\LaraASP\GraphQL\Builder\Traits\WithSource;
use LastDragon_ru\LaraASP\GraphQL\Stream\Contracts\FieldArgumentDirective;
use LastDragon_ru\LaraASP\GraphQL\Stream\Exceptions\Client\CursorInvalidPath;
use LastDragon_ru\LaraASP\GraphQL\Stream\Offset as StreamOffset;
use LastDragon_ru\LaraASP\GraphQL\Stream\Types\Offset as OffsetType;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\ArgManipulator;
use Override;

use function array_map;
use function implode;
use function is_int;
use function max;

/**
 * @implements FieldArgumentDirective<StreamOffset>
 */
class Offset extends BaseDirective implements ArgManipulator, FieldArgumentDirective {
    use WithSource;

    public function __construct(
        private readonly ManipulatorFactory $manipulatorFactory,
    ) {
        // empty
    }

    #[Override]
    public static function definition(): string {
        $name = DirectiveLocator::directiveName(static::class);

        return <<<GRAPHQL
            directive @{$name} on ARGUMENT_DEFINITION
        GRAPHQL;
    }

    #[Override]
    public function manipulateArgDefinition(
        DocumentAST &$documentAST,
        InputValueDefinitionNode &$argDefinition,
        FieldDefinitionNode &$parentField,
        ObjectTypeDefinitionNode|InterfaceTypeDefinitionNode &$parentType,
    ): void {
        $manipulator = $this->manipulatorFactory->create($documentAST);
        $context     = new Context();
        $source      = $this->getFieldArgumentSource($manipulator, $parentType, $parentField, $argDefinition);
        $type        = Parser::typeReference($manipulator->getType(OffsetType::class, $source, $context));

        $manipulator->setArgumentType(
            $parentType,
            $parentField,
            $argDefinition,
            $type,
        );

        $argDefinition->description ??= Parser::stringLiteral(
            <<<'STRING'
            """
            The cursor or offset within the stream to start.
            """
            STRING,
        );
    }

    #[Override]
    public function getFieldArgumentValue(ResolveInfo $info, mixed $value): mixed {
        $path = $this->getPath($info);

        if ($value instanceof StreamOffset) {
            if ($path !== $value->path) {
                throw new CursorInvalidPath($path, $value->path);
            }

            // fixme(graphql)!: if args given, probable we need to compare hash
            //      of them with the hash from `$cursor` and throw an error if
            //      doesn't match.
        } elseif (is_int($value)) {
            $value = Cast::toInt($value);
            $value = new StreamOffset($path, max(0, $value));
        } else {
            $value = new StreamOffset($path, 0);
        }

        return $value;
    }

    protected function getPath(ResolveInfo $info): string {
        $path = array_map(static fn ($path) => is_int($path) ? '*' : $path, $info->path);
        $path = implode('.', $path);

        return $path;
    }
}
