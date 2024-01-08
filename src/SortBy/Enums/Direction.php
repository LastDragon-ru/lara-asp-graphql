<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SortBy\Enums;

use GraphQL\Type\Definition\Deprecated;
use GraphQL\Type\Definition\Description;

#[Description('Sort direction.')]
enum Direction: string {
    case Asc  = 'Asc';
    case Desc = 'Desc';

    /**
     * @deprecated 5.4.0 Please use {@link Direction::Asc} instead.
     * @internal
     */
    #[Deprecated('Please use `Asc` instead.')]
    #[Description('')]
    case asc = 'asc';

    /**
     * @deprecated 5.4.0 Please use {@link Direction::Desc} instead.
     * @internal
     */
    #[Deprecated('Please use `Desc` instead.')]
    #[Description('')]
    case desc = 'desc';
}
