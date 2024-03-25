<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SearchBy\Types\Condition;

use LastDragon_ru\LaraASP\GraphQL\Builder\Context\HandlerContextBuilderInfo;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Context;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeSource;
use LastDragon_ru\LaraASP\GraphQL\Package;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Directives\Directive;
use Override;

use function trigger_deprecation;

// phpcs:disable PSR1.Files.SideEffects

trigger_deprecation(Package::Name, '5.5.0', 'Please migrate to the new query structure.');

/**
 * @deprecated 5.5.0 Please migrate to the new query structure.
 */
class V5 extends Type {
    #[Override]
    public function getTypeName(TypeSource $source, Context $context): string {
        $name          = 'Condition';
        $typeName      = $source->getTypeName();
        $builderName   = $context->get(HandlerContextBuilderInfo::class)?->value->getName() ?? 'Unknown';
        $directiveName = Directive::Name;

        return "{$directiveName}{$builderName}{$name}{$typeName}";
    }
}
