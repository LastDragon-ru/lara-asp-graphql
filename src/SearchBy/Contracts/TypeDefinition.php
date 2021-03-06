<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SearchBy\Contracts;

use GraphQL\Language\AST\TypeDefinitionNode;

interface TypeDefinition {
    public function get(string $name, string $scalar = null, bool $nullable = null): ?TypeDefinitionNode;
}
