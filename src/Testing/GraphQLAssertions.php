<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Testing;

use GraphQL\Type\Schema;
use Illuminate\Contracts\Config\Repository;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Contracts\PrintedSchema;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Contracts\Printer;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Contracts\Settings;
use LastDragon_ru\LaraASP\GraphQL\Testing\Package\SchemaPrinter\TestSettings;
use LastDragon_ru\LaraASP\Testing\Utils\Args;
use Nuwave\Lighthouse\Schema\SchemaBuilder;
use Nuwave\Lighthouse\Schema\Source\SchemaSourceProvider;
use Nuwave\Lighthouse\Schema\Source\SchemaStitcher;
use Nuwave\Lighthouse\Testing\MocksResolvers;
use Nuwave\Lighthouse\Testing\TestSchemaProvider;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

use function array_combine;

/**
 * @mixin TestCase
 */
trait GraphQLAssertions {
    use MocksResolvers;

    // <editor-fold desc="Assertions">
    // =========================================================================
    /**
     * Compares two GraphQL schemas.
     */
    public function assertGraphQLSchemaEquals(
        GraphQLExpectedSchema|PrintedSchema|Schema|SplFileInfo|string $expected,
        PrintedSchema|Schema|SplFileInfo|string $schema,
        string $message = '',
    ): void {
        // GraphQL
        $output   = $expected;
        $settings = null;

        if ($output instanceof GraphQLExpectedSchema) {
            $settings = $output->getSettings();
            $output   = $output->getSchema();
        }

        if ($output instanceof PrintedSchema || $output instanceof Schema) {
            $output = (string) $this->printGraphQLSchema($output, $settings);
        } else {
            $output = Args::content($output);
        }

        $actual = $this->printGraphQLSchema($schema, $settings);

        self::assertEquals($output, (string) $actual, $message);

        // Prepare
        if (!($expected instanceof GraphQLExpectedSchema)) {
            $expected = new GraphQLExpectedSchema($expected);
        }

        // Used types
        $usedTypes = $expected->getUsedTypes();

        if ($usedTypes !== null) {
            self::assertEquals(
                array_combine($usedTypes, $usedTypes),
                $actual->getUsedTypes(),
                'Used Types not match.',
            );
        }

        // Unused types
        $unusedTypes = $expected->getUnusedTypes();

        if ($unusedTypes !== null) {
            self::assertEquals(
                array_combine($unusedTypes, $unusedTypes),
                $actual->getUnusedTypes(),
                'Unused Types not match.',
            );
        }

        // Used directives
        $usedDirectives = $expected->getUsedDirectives();

        if ($usedDirectives !== null) {
            self::assertEquals(
                array_combine($usedDirectives, $usedDirectives),
                $actual->getUsedDirectives(),
                'Used Directives not match.',
            );
        }

        // Unused directives
        $unusedDirectives = $expected->getUnusedDirectives();

        if ($unusedDirectives !== null) {
            self::assertEquals(
                array_combine($unusedDirectives, $unusedDirectives),
                $actual->getUnusedDirectives(),
                'Unused Directives not match.',
            );
        }
    }

    /**
     * Compares GraphQL schema with default (application) schema.
     */
    public function assertDefaultGraphQLSchemaEquals(
        GraphQLExpectedSchema|SplFileInfo|string $expected,
        string $message = '',
    ): void {
        self::assertGraphQLSchemaEquals(
            $expected,
            $this->getDefaultGraphQLSchema(),
            $message,
        );
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected function useGraphQLSchema(SplFileInfo|string $schema): static {
        $schema   = Args::content($schema);
        $provider = new TestSchemaProvider($schema);

        $this->instance(SchemaSourceProvider::class, $provider);

        return $this;
    }

    protected function getGraphQLSchema(SplFileInfo|string $schema): Schema {
        $this->useGraphQLSchema($schema);

        $graphql = $this->app->make(SchemaBuilder::class);
        $schema  = $graphql->schema();

        return $schema;
    }

    protected function getDefaultGraphQLSchema(): Schema {
        $this->instance(
            SchemaSourceProvider::class,
            new SchemaStitcher(
                $this->app->make(Repository::class)->get('lighthouse.schema.register', ''),
            ),
        );

        $graphql = $this->app->make(SchemaBuilder::class);
        $schema  = $graphql->schema();

        return $schema;
    }

    protected function printGraphQLSchema(
        PrintedSchema|Schema|SplFileInfo|string $schema,
        Settings $settings = null,
    ): PrintedSchema {
        if ($schema instanceof PrintedSchema) {
            return $schema;
        }

        if (!($schema instanceof Schema)) {
            $schema = $this->getGraphQLSchema($schema);
        }

        return $this->getGraphQLSchemaPrinter($settings)->print($schema);
    }

    protected function printDefaultGraphQLSchema(Settings $settings = null): PrintedSchema {
        return $this->printGraphQLSchema($this->getDefaultGraphQLSchema(), $settings);
    }

    protected function getGraphQLSchemaPrinter(Settings $settings = null): Printer {
        return $this->app->make(Printer::class)->setSettings($settings ?? new TestSettings());
    }
    // </editor-fold>
}
