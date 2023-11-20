<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\Client;

use GraphQL\Error\ClientAware;
use LastDragon_ru\LaraASP\GraphQL\Builder\Exceptions\BuilderException;
use LastDragon_ru\LaraASP\GraphQL\Package;
use Override;

abstract class ClientException extends BuilderException implements ClientAware {
    #[Override]
    public function isClientSafe(): bool {
        return true;
    }

    public function getCategory(): string {
        return Package::Name;
    }
}
