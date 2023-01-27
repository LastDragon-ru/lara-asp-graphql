<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\SchemaPrinter;

use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Introspection;
use GraphQL\Type\Schema;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Contracts\PrintedSchema;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Misc\DirectiveResolver;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Block;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\ListBlock;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\Settings;
use LastDragon_ru\LaraASP\GraphQLPrinter\Settings\DefaultSettings;
use LastDragon_ru\LaraASP\GraphQLPrinter\Settings\ImmutableSettings;

/**
 * Introspection schema printer.
 *
 * Following settings has no effects:
 * - {@see Settings::getTypeDefinitionFilter()}
 * - {@see Settings::getDirectiveFilter}
 * - {@see Settings::getDirectiveDefinitionFilter}
 * - {@see Settings::isPrintUnusedDefinitions}
 * - {@see Settings::isPrintDirectiveDefinitions}
 */
class IntrospectionSchemaPrinter extends SchemaPrinter {
    public function setSettings(?Settings $settings): static {
        return parent::setSettings(
            ImmutableSettings::createFrom($settings ?? new DefaultSettings())
                ->setPrintUnusedDefinitions(true)
                ->setPrintDirectiveDefinitions(true)
                ->setTypeDefinitionFilter(null)
                ->setDirectiveDefinitionFilter(null)
                ->setDirectiveFilter(null),
        );
    }

    protected function getPrintedSchema(DirectiveResolver $resolver, Schema $schema, Block $content): PrintedSchema {
        return new PrintedIntrospectionSchemaImpl($resolver, $schema, $content);
    }

    protected function getTypeDefinitions(Schema $schema): ListBlock {
        $blocks = $this->getDefinitionList();

        foreach (Introspection::getTypes() as $type) {
            $blocks[] = $this->getDefinitionBlock($type);
        }

        return $blocks;
    }

    protected function getDirectiveDefinitions(DirectiveResolver $resolver, Schema $schema): ListBlock {
        $blocks     = $this->getDefinitionList();
        $directives = $schema->getDirectives();

        foreach ($directives as $directive) {
            if (Directive::isSpecifiedDirective($directive)) {
                $blocks[] = $this->getDefinitionBlock($directive);
            }
        }

        return $blocks;
    }
}
