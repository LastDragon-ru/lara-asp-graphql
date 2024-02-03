<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SortBy\Operators\Extra;

use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\BuilderPropertyResolver;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Context;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Handler;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeProvider;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeSource;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;
use LastDragon_ru\LaraASP\GraphQL\Builder\Traits\HandlerOperator;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Contracts\SorterFactory;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Enums\Nulls;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Operators\FieldContextNulls;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Operators\Operator;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Types\Clause;
use Nuwave\Lighthouse\Execution\Arguments\Argument;
use Override;

class NullsFirst extends Operator {
    use HandlerOperator;

    /**
     * @param SorterFactory<object> $factory
     */
    public function __construct(
        protected readonly SorterFactory $factory,
        BuilderPropertyResolver $resolver,
    ) {
        parent::__construct($resolver);
    }

    #[Override]
    public static function getName(): string {
        return 'nullsFirst';
    }

    #[Override]
    public function getFieldType(TypeProvider $provider, TypeSource $source, Context $context): string {
        return $provider->getType(Clause::class, $source, $context);
    }

    #[Override]
    public function getFieldDescription(): string {
        return 'NULLs first';
    }

    #[Override]
    public function isAvailable(string $builder, Context $context): bool {
        return (bool) $this->factory->create($builder)?->isNullsSupported();
    }

    #[Override]
    public function call(
        Handler $handler,
        object $builder,
        Property $property,
        Argument $argument,
        Context $context,
    ): object {
        return $this->handle($handler, $builder, $property->getParent(), $argument, $context->override([
            FieldContextNulls::class => new FieldContextNulls(Nulls::First),
        ]));
    }
}
