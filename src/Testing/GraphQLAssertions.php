<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQL\Testing;

use GraphQL\Type\Schema;
use Illuminate\Contracts\Config\Repository;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\Contracts\Printer;
use LastDragon_ru\LaraASP\GraphQL\SchemaPrinter\PrintedSchema;
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
        GraphQLExpectedSchema|SplFileInfo|string $expected,
        PrintedSchema|Schema|SplFileInfo|string $schema,
        string $message = '',
    ): void {
        // Prepare
        if (!($expected instanceof GraphQLExpectedSchema)) {
            $expected = new GraphQLExpectedSchema($expected);
        }

        // GraphQL
        $actual = $this->printGraphQLSchema($schema);

        self::assertEquals(
            Args::content($expected->getSchema()),
            (string) $actual,
            $message,
        );

        // Used types
        $usedTypes = $expected->getUsedTypes();

        if ($usedTypes !== null) {
            self::assertEquals(
                array_combine($usedTypes, $usedTypes),
                $actual->getUsedTypes(),
            );
        }

        // Unused types
        $unusedTypes = $expected->getUnusedTypes();

        if ($unusedTypes !== null) {
            self::assertEquals(
                array_combine($unusedTypes, $unusedTypes),
                $actual->getUnusedTypes(),
            );
        }

        // Used directives
        $usedDirectives = $expected->getUsedDirectives();

        if ($usedDirectives !== null) {
            self::assertEquals(
                array_combine($usedDirectives, $usedDirectives),
                $actual->getUsedDirectives(),
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
        $schema = Args::content($schema);

        $this->override(SchemaSourceProvider::class, static function () use ($schema): SchemaSourceProvider {
            return new TestSchemaProvider($schema);
        });

        return $this;
    }

    protected function getGraphQLSchema(SplFileInfo|string $schema): Schema {
        $this->useGraphQLSchema($schema);

        $graphql = $this->app->make(SchemaBuilder::class);
        $schema  = $graphql->schema();

        return $schema;
    }

    protected function getDefaultGraphQLSchema(): Schema {
        $this->override(SchemaSourceProvider::class, function (): SchemaSourceProvider {
            return new SchemaStitcher(
                $this->app->make(Repository::class)->get('lighthouse.schema.register', ''),
            );
        });

        $graphql = $this->app->make(SchemaBuilder::class);
        $schema  = $graphql->schema();

        return $schema;
    }

    protected function printGraphQLSchema(PrintedSchema|Schema|SplFileInfo|string $schema): PrintedSchema {
        if ($schema instanceof PrintedSchema) {
            return $schema;
        }

        if (!($schema instanceof Schema)) {
            $schema = $this->getGraphQLSchema($schema);
        }

        return $this->getGraphQLSchemaPrinter()->print($schema);
    }

    protected function printDefaultGraphQLSchema(): PrintedSchema {
        return $this->printGraphQLSchema($this->getDefaultGraphQLSchema());
    }

    protected function getGraphQLSchemaPrinter(): Printer {
        return $this->app->make(Printer::class)->setSettings(new TestSettings());
    }
    // </editor-fold>
}
