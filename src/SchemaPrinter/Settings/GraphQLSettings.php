<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Settings;

use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Contracts\DirectiveFilter;

/**
 * These settings close as possible to {@see \GraphQL\Utils\SchemaPrinter}.
 */
class GraphQLSettings extends ImmutableSettings {
    protected string           $space                             = ' ';
    protected string           $indent                            = '  ';
    protected string           $fileEnd                           = "\n";
    protected string           $lineEnd                           = "\n";
    protected int              $lineLength                        = 80;
    protected bool             $printDirectives                   = true;
    protected bool             $printDirectiveDefinitions         = false;
    protected bool             $printDirectivesInDescription      = false;
    protected bool             $printUnusedDefinitions            = true;
    protected bool             $normalizeSchema                   = true;
    protected bool             $normalizeUnions                   = false;
    protected bool             $normalizeEnums                    = false;
    protected bool             $normalizeInterfaces               = false;
    protected bool             $normalizeFields                   = false;
    protected bool             $normalizeArguments                = false;
    protected bool             $normalizeDescription              = false;
    protected bool             $normalizeDirectiveLocations       = false;
    protected bool             $alwaysMultilineUnions             = false;
    protected bool             $alwaysMultilineInterfaces         = false;
    protected bool             $alwaysMultilineDirectiveLocations = false;
    protected ?DirectiveFilter $directiveFilter                   = null;

    public function __construct() {
        parent::__construct();

        $this->directiveFilter = new GraphQLDirectiveFilter();
    }
}