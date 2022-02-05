<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Settings;

use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Contracts\DirectiveFilter;

class DefaultSettings extends GraphQLSettings {
    protected string           $space                             = ' ';
    protected string           $indent                            = '    ';
    protected string           $fileEnd                           = "\n";
    protected string           $lineEnd                           = "\n";
    protected int              $lineLength                        = 80;
    protected bool             $printDirectives                   = true;
    protected bool             $printDirectiveDefinitions         = true;
    protected bool             $printDirectivesInDescription      = false;
    protected bool             $printUnusedDefinitions            = false;
    protected bool             $normalizeSchema                   = true;
    protected bool             $normalizeUnions                   = true;
    protected bool             $normalizeEnums                    = true;
    protected bool             $normalizeInterfaces               = true;
    protected bool             $normalizeFields                   = true;
    protected bool             $normalizeArguments                = true;
    protected bool             $normalizeDescription              = true;
    protected bool             $normalizeDirectiveLocations       = true;
    protected bool             $alwaysMultilineUnions             = true;
    protected bool             $alwaysMultilineInterfaces         = true;
    protected bool             $alwaysMultilineDirectiveLocations = true;
    protected ?DirectiveFilter $directiveFilter                   = null;
}