# (Laravel) GraphQL Extensions for Lighthouse

This package provides highly powerful `@searchBy`, `@sortBy`, `@stream` directives for [lighthouse-php](https://lighthouse-php.com/). The `@searchBy` directive provides basic conditions like `=`, `>`, `<`, etc, relations, `not (<condition>)`, enums, and custom operators support. All are strictly typed so you no need to use `Mixed` type anymore. The `@sortBy` is not only about standard sorting by columns but also allows use relations. 😎

[include:artisan]: <lara-asp-documentator:requirements "{$directory}">
[//]: # (start: 0f999169cbabc32d4f47c79c31d74f8b4066c685962719bae5df3c63a08ea382)
[//]: # (warning: Generated automatically. Do not edit.)

# Requirements

| Requirement  | Constraint          | Supported by |
|--------------|---------------------|------------------|
|  PHP  | `^8.3` |   `HEAD ⋯ 5.0.0`   |
|  | `^8.2` |   `HEAD ⋯ 2.0.0`   |
|  | `^8.1` |   `6.4.1 ⋯ 2.0.0`   |
|  | `^8.0` |   `4.6.0 ⋯ 2.0.0`   |
|  | `^8.0.0` |   `1.1.2 ⋯ 0.12.0`   |
|  | `>=8.0.0` |   `0.11.0 ⋯ 0.5.0`   |
|  Laravel  | `^11.0.0` |   `HEAD ⋯ 6.2.0`   |
|  | `^10.34.0` |   `HEAD ⋯ 6.2.0`   |
|  | `^10.0.0` |   `6.1.0 ⋯ 2.1.0`   |
|  | `^9.21.0` |   `5.6.0 ⋯ 5.0.0-beta.1`   |
|  | `^9.0.0` |   `5.0.0-beta.0 ⋯ 0.12.0`   |
|  | `^8.22.1` |   `3.0.0 ⋯ 0.5.0`   |
|  Lighthouse  | `^6.5.0` |   `HEAD ⋯ 5.0.0-beta.0`   |
|  | `^6.0.0` |   `4.6.0 ⋯ 4.0.0`   |
|  | `^5.68.0` |   `3.0.0 ⋯ 2.0.0`   |
|  | `^5.8.0` |   `1.1.2 ⋯ 0.13.0`   |
|  | `^5.6.1` |  `0.12.0`  ,  `0.11.0`   |
|  | `^5.4` |   `0.10.0 ⋯ 0.5.0`   |

[//]: # (end: 0f999169cbabc32d4f47c79c31d74f8b4066c685962719bae5df3c63a08ea382)

[include:template]: ../../docs/Shared/Installation.md ({"data": {"package": "graphql"}})
[//]: # (start: 3f6ad6b1286af0c80a1f759dea7af7dba89516e79ef8c8b08be695e4429c278c)
[//]: # (warning: Generated automatically. Do not edit.)

# Installation

```shell
composer require lastdragon-ru/lara-asp-graphql
```

[//]: # (end: 3f6ad6b1286af0c80a1f759dea7af7dba89516e79ef8c8b08be695e4429c278c)

# Configuration

Config can be used, for example, to customize supported operators for each type. Before this, you need to publish it via the following command, and then you can edit `config/lara-asp-graphql.php`.

```shell
php artisan vendor:publish --provider=LastDragon_ru\\LaraASP\\GraphQL\\Provider --tag=config
```

# Directives

[include:document-list]: ./docs/Directives
[//]: # (start: 0c4e6191f4559b6f9a3934f6dfa16490cf440b719dc752ee8992eb561b40de8b)
[//]: # (warning: Generated automatically. Do not edit.)

## `@searchBy`

Probably the most powerful directive to provide search (`where` conditions) for your GraphQL queries.

[Read more](<docs/Directives/@searchBy.md>).

## `@sortBy`

Probably the most powerful directive to provide sort (`order by` conditions) for your GraphQL queries.

[Read more](<docs/Directives/@sortBy.md>).

## `@stream` 🧪

Unlike the `@paginate` (and similar) directive, the `@stream` provides a uniform way to perform Offset/Limit and Cursor pagination of Eloquent/Query/Scout builders. Filtering and sorting enabled by default via [`@searchBy`][pkg:graphql#@searchBy] and [`@sortBy`][pkg:graphql#@sortBy] directives.

[Read more](<docs/Directives/@stream.md>).

## `@type`

Converts scalar into GraphQL Type. Similar to Lighthouse's `@scalar` directive, but uses Laravel Container to resolve instance and also supports PHP enums.

[Read more](<docs/Directives/@type.md>).

[//]: # (end: 0c4e6191f4559b6f9a3934f6dfa16490cf440b719dc752ee8992eb561b40de8b)

# Scalars

> [!IMPORTANT]
>
> You should register the Scalar before use, it can be done via [`AstManipulator`](./src/Utils/AstManipulator.php) (useful while AST manipulation), [`TypeRegistry`](https://lighthouse-php.com/master/digging-deeper/adding-types-programmatically.html#using-the-typeregistry), or as a custom scalar inside the Schema:
>
> ```graphql
> scalar JsonString
> @type(
>     class: "LastDragon_ru\\LaraASP\\GraphQL\\Scalars\\JsonStringType"
> )
> ```

[include:document-list]: ./docs/Scalars
[//]: # (start: e4b8e1f1458103b7ed6e1a71a8dc3c964afce8484698a88f0d49d6d6121bafc8)
[//]: # (warning: Generated automatically. Do not edit.)

## `JsonString`

Represents [JSON](https://json.org) string.

[Read more](<docs/Scalars/JsonString.md>).

[//]: # (end: e4b8e1f1458103b7ed6e1a71a8dc3c964afce8484698a88f0d49d6d6121bafc8)

# Scout

[Scout](https://laravel.com/docs/scout) is also supported 🤩. You just need to add [`@search`](https://lighthouse-php.com/master/api-reference/directives.html#search) directive to an argument. Please note that available operators depend on [Scout itself](https://laravel.com/docs/scout#where-clauses).

Please note that if the [`@search`](https://lighthouse-php.com/master/api-reference/directives.html#search) directive added, the generated query will expect the Scout builder only. So recommended using non-nullable `String!` type to avoid using the Eloquent builder (it will happen if the search argument missed or `null`; see also [lighthouse#2465](https://github.com/nuwave/lighthouse/issues/2465).

# Input type auto-generation

The type used with the Builder directives like `@searchBy`/`@sortBy` may be Explicit (when you specify the `input` name `field(where: InputTypeName @searchBy): [Object!]!`) or Implicit (when the `_` used, `field(where: _ @searchBy): [Object!]!`). They are processing a bit differently.

For Explicit type, all fields except unions and marked as ignored (if supported by the directive) will be included.

For Implicit type, the following rules are applied (in this order; concrete directive may have differences, please check its docs):

* Union? - exclude
* Has `Operator` of the concrete directive? - include
* Has `Nuwave\Lighthouse\Support\Contracts\FieldResolver`?
  * Yes
    * Is `Nuwave\Lighthouse\Schema\Directives\RelationDirective`? - Include if is the `Object` or list of `Object`
    * Is `Nuwave\Lighthouse\Schema\Directives\RenameDirective`? - Include if allowed, is `scalar`/`enum` (not `Object`), and no arguments
    * Otherwise - exclude
  * No
    * Is `Object` or has arguments - exclude
    * Otherwise - include
* Ignored (if supported)? - exclude

When converting the field, some of the original directives will be copied into the newly generated field. For the Explicit type, all directives except operators of other directives will be copied. For Implicit type, you can use [`builder.allowed_directives`](defaults/config.php) setting to control. Be aware of directive locations - the package doesn't perform any checks to ensure that the copied directive allowed on `INPUT_FIELD_DEFINITION`, it just copies it as is.

# Builder field/column name

By default `@searchBy`/`@sortBy` will convert nested/related fields into dot string: eg `{user: {name: asc}}` will be converted into `user.name`. You can redefine this behavior by [`BuilderFieldResolver`](./src/Builder/Contracts/BuilderFieldResolver.php):

```php
// AppProvider

$this->app->bind(
    LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\BuilderFieldResolver::class,
    MyBuilderFieldResolver::class,
);
```

# Builder type detection

Directives like `@searchBy`/`@sortBy` have a unique set of operators and other features for each type of Builder (Eloquent/Scout/etc). Detection of the current Builder works fine for standard Lighthouse directives like `@all`, `@paginated`, `@search`, etc and relies on proper type hints of Relations/Queries/Resolvers. You may get `BuilderUnknown` error if the type hint is missed or the union type is used.

```php
<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model {
    protected $table = 'comments';

    /**
     * Will NOT work
     */
    public function user() {
        return $this->belongsTo(User::class);
    }

    /**
     * Must be
     */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
```

If you implement custom directives which internally enhance the Builder (like standard directives do), you may get `BuilderUnknown` error because the proper/expected builder type was not detected. In this case, your directive should implement [`BuilderInfoProvider`](./src/Builder/Contracts/BuilderInfoProvider.php) interface and to specify the builder type explicitly.

[include:example]: docs/Examples/BuilderInfoProvider.php
[//]: # (start: 4d2d9fdbfd2e9508604872c153faeb14eab6934d293f7f0880446a95e68d0679)
[//]: # (warning: Generated automatically. Do not edit.)

```php
<?php declare(strict_types = 1);

namespace App\GraphQL\Directives;

use Illuminate\Database\Eloquent\Builder;
use LastDragon_ru\LaraASP\GraphQL\Builder\BuilderInfo;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\BuilderInfoProvider;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\TypeSource;
use Nuwave\Lighthouse\Support\Contracts\Directive;
use Override;

class CustomDirective implements Directive, BuilderInfoProvider {
    #[Override]
    public static function definition(): string {
        return 'directive @custom';
    }

    #[Override]
    public function getBuilderInfo(TypeSource $source): ?BuilderInfo {
        return BuilderInfo::create(Builder::class);
    }

    public function __invoke(): mixed {
        return null;
    }
}
```

[//]: # (end: 4d2d9fdbfd2e9508604872c153faeb14eab6934d293f7f0880446a95e68d0679)

# Printer

The package provides bindings for [`Printer`][pkg:graphql-printer] so you can simply use:

[include:example]: ./docs/Examples/Printer.php
[//]: # (start: 00ac890253748f10fee1ee6b47fa65917a45100cc9ee42b420e5d8c0219213e5)
[//]: # (warning: Generated automatically. Do not edit.)

```php
<?php declare(strict_types = 1);

use LastDragon_ru\LaraASP\Dev\App\Example;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\DirectiveFilter;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\Printer;
use LastDragon_ru\LaraASP\GraphQLPrinter\Settings\DefaultSettings;
use Nuwave\Lighthouse\Schema\SchemaBuilder;

$schema   = app()->make(SchemaBuilder::class)->schema();
$printer  = app()->make(Printer::class);
$settings = new DefaultSettings();

$printer->setSettings(
    $settings->setDirectiveDefinitionFilter(
        new class() implements DirectiveFilter {
            #[Override]
            public function isAllowedDirective(string $directive, bool $isStandard): bool {
                return !in_array($directive, ['eq', 'all', 'find'], true);
            }
        },
    ),
);

Example::raw($printer->print($schema), 'graphql');
```

<details><summary>Example output</summary>

The `$printer->print($schema)` is:

```graphql
"""
Use Input as Search Conditions for the current Builder.
"""
directive @searchBy
on
    | ARGUMENT_DEFINITION

directive @searchByOperatorAllOf
on
    | INPUT_FIELD_DEFINITION
    | SCALAR

directive @searchByOperatorAnyOf
on
    | INPUT_FIELD_DEFINITION
    | SCALAR

directive @searchByOperatorCondition
on
    | INPUT_FIELD_DEFINITION

directive @searchByOperatorContains
on
    | ENUM
    | INPUT_FIELD_DEFINITION
    | SCALAR

directive @searchByOperatorEndsWith
on
    | ENUM
    | INPUT_FIELD_DEFINITION
    | SCALAR

directive @searchByOperatorEqual
on
    | ENUM
    | INPUT_FIELD_DEFINITION
    | SCALAR

directive @searchByOperatorField
on
    | INPUT_FIELD_DEFINITION
    | SCALAR

directive @searchByOperatorIn
on
    | ENUM
    | INPUT_FIELD_DEFINITION
    | SCALAR

directive @searchByOperatorLike
on
    | ENUM
    | INPUT_FIELD_DEFINITION
    | SCALAR

directive @searchByOperatorNot
on
    | INPUT_FIELD_DEFINITION
    | SCALAR

directive @searchByOperatorNotContains
on
    | ENUM
    | INPUT_FIELD_DEFINITION
    | SCALAR

directive @searchByOperatorNotEndsWith
on
    | ENUM
    | INPUT_FIELD_DEFINITION
    | SCALAR

directive @searchByOperatorNotEqual
on
    | ENUM
    | INPUT_FIELD_DEFINITION
    | SCALAR

directive @searchByOperatorNotIn
on
    | ENUM
    | INPUT_FIELD_DEFINITION
    | SCALAR

directive @searchByOperatorNotLike
on
    | ENUM
    | INPUT_FIELD_DEFINITION
    | SCALAR

directive @searchByOperatorNotStartsWith
on
    | ENUM
    | INPUT_FIELD_DEFINITION
    | SCALAR

directive @searchByOperatorStartsWith
on
    | ENUM
    | INPUT_FIELD_DEFINITION
    | SCALAR

"""
Available conditions for `type User` (only one field allowed at a time).
"""
input SearchByConditionUser {
    """
    Field condition.
    """
    id: SearchByScalarID
    @searchByOperatorCondition

    """
    Field condition.
    """
    name: SearchByScalarString
    @searchByOperatorCondition
}

"""
Available conditions for `type User` (only one field allowed at a time).
"""
input SearchByRootUser {
    """
    All of the conditions must be true.
    """
    allOf: [SearchByRootUser!]
    @searchByOperatorAllOf

    """
    Any of the conditions must be true.
    """
    anyOf: [SearchByRootUser!]
    @searchByOperatorAnyOf

    """
    Field.
    """
    field: SearchByConditionUser
    @searchByOperatorField

    """
    Not.
    """
    not: SearchByRootUser
    @searchByOperatorNot
}

"""
Available operators for `scalar ID` (only one operator allowed at a time).
"""
input SearchByScalarID {
    """
    Equal (`=`).
    """
    equal: ID
    @searchByOperatorEqual

    """
    Within a set of values.
    """
    in: [ID!]
    @searchByOperatorIn

    """
    Not Equal (`!=`).
    """
    notEqual: ID
    @searchByOperatorNotEqual

    """
    Outside a set of values.
    """
    notIn: [ID!]
    @searchByOperatorNotIn
}

"""
Available operators for `scalar String` (only one operator allowed at a time).
"""
input SearchByScalarString {
    """
    Contains.
    """
    contains: String
    @searchByOperatorContains

    """
    Ends with a string.
    """
    endsWith: String
    @searchByOperatorEndsWith

    """
    Equal (`=`).
    """
    equal: String
    @searchByOperatorEqual

    """
    Within a set of values.
    """
    in: [String!]
    @searchByOperatorIn

    """
    Like.
    """
    like: String
    @searchByOperatorLike

    """
    Not contains.
    """
    notContains: String
    @searchByOperatorNotContains

    """
    Not ends with a string.
    """
    notEndsWith: String
    @searchByOperatorNotEndsWith

    """
    Not Equal (`!=`).
    """
    notEqual: String
    @searchByOperatorNotEqual

    """
    Outside a set of values.
    """
    notIn: [String!]
    @searchByOperatorNotIn

    """
    Not like.
    """
    notLike: String
    @searchByOperatorNotLike

    """
    Not starts with a string.
    """
    notStartsWith: String
    @searchByOperatorNotStartsWith

    """
    Starts with a string.
    """
    startsWith: String
    @searchByOperatorStartsWith
}

type Query {
    """
    Find a single user by an identifying attribute.
    """
    user(
        """
        Search by primary key.
        """
        id: ID
        @eq
    ): User
    @find

    """
    List multiple users.
    """
    users(
        where: SearchByRootUser
        @searchBy
    ): [User!]!
    @all
}

"""
Account of a person who utilizes this application.
"""
type User {
    """
    Unique primary key.
    """
    id: ID!

    """
    Non-unique name.
    """
    name: String!
}
```

</details>

[//]: # (end: 00ac890253748f10fee1ee6b47fa65917a45100cc9ee42b420e5d8c0219213e5)

# Testing Assertions

[include:document-list]: ./docs/Assertions
[//]: # (start: c9953bb428d837e4a82f61878dcfa1a88fc32adcfc3e683dcc228578acec584b)
[//]: # (warning: Generated automatically. Do not edit.)

## `assertGraphQLIntrospectionEquals`

Compares default public schema (as the client sees it through introspection).

[Read more](<docs/Assertions/AssertGraphQLIntrospectionEquals.md>).

## `assertGraphQLSchemaEquals`

Compares default internal schema (with all directives).

[Read more](<docs/Assertions/AssertGraphQLSchemaEquals.md>).

## `assertGraphQLSchemaNoBreakingChanges`

Checks that no breaking changes in the default internal schema (with all directives).

[Read more](<docs/Assertions/AssertGraphQLSchemaNoBreakingChanges.md>).

## `assertGraphQLSchemaNoDangerousChanges`

Checks that no dangerous changes in the default internal schema (with all directives).

[Read more](<docs/Assertions/AssertGraphQLSchemaNoDangerousChanges.md>).

## `assertGraphQLSchemaValid`

Validates default internal schema (with all directives). Faster than `lighthouse:validate-schema` command because loads only used directives.

[Read more](<docs/Assertions/AssertGraphQLSchemaValid.md>).

[//]: # (end: c9953bb428d837e4a82f61878dcfa1a88fc32adcfc3e683dcc228578acec584b)

[include:file]: ../../docs/Shared/Upgrading.md
[//]: # (start: bf9c1ede9e482e5ee353d24490c6493a56ff023fc987625dc02aefe6b298696d)
[//]: # (warning: Generated automatically. Do not edit.)

# Upgrading

Please follow [Upgrade Guide](UPGRADE.md).

[//]: # (end: bf9c1ede9e482e5ee353d24490c6493a56ff023fc987625dc02aefe6b298696d)

[include:file]: ../../docs/Shared/Contributing.md
[//]: # (start: fc88f84f187016cb8144e9a024844024492f0c3a5a6f8d128bf69a5814cc8cc5)
[//]: # (warning: Generated automatically. Do not edit.)

# Contributing

This package is the part of Awesome Set of Packages for Laravel. Please use the [main repository](https://github.com/LastDragon-ru/lara-asp) to [report issues](https://github.com/LastDragon-ru/lara-asp/issues), send [pull requests](https://github.com/LastDragon-ru/lara-asp/pulls), or [ask questions](https://github.com/LastDragon-ru/lara-asp/discussions).

[//]: # (end: fc88f84f187016cb8144e9a024844024492f0c3a5a6f8d128bf69a5814cc8cc5)

[include:file]: ../../docs/Shared/Links.md
[//]: # (start: bab2235c844a18f05f23166e16c25becd344a2bc82159ddae3b818600a6e1884)
[//]: # (warning: Generated automatically. Do not edit.)

[pkg:graphql#@searchBy]: https://github.com/LastDragon-ru/lara-asp/tree/main/packages/graphql/docs/Directives/@searchBy.md

[pkg:graphql#@sortBy]: https://github.com/LastDragon-ru/lara-asp/tree/main/packages/graphql/docs/Directives/@sortBy.md

[pkg:graphql-printer]: https://github.com/LastDragon-ru/lara-asp/tree/main/packages/graphql-printer

[//]: # (end: bab2235c844a18f05f23166e16c25becd344a2bc82159ddae3b818600a6e1884)
