# GraphQL Extensions for Lighthouse

This package provides highly powerful `@searchBy` and `@sortBy`  directives for [lighthouse-php](https://lighthouse-php.com/). The `@searchBy` directive provides basic conditions like `=`, `>`, `<`, etc, relations, `not (<condition>)`, enums, and custom operators support. All are strictly typed so you no need to use `Mixed` type anymore. The `@sortBy` is not only about standard sorting by columns but also allows use relations. 😎

[include:exec]: <../../dev/artisan lara-asp-documentator:requirements>
[//]: # (start: 876a9177c0e8e3722ac84e8f3888245fc9070a64a87dedfe7c9d9ba2a13b374b)
[//]: # (warning: Generated automatically. Do not edit.)

# Requirements

| Requirement  | Constraint          | Supported by |
|--------------|---------------------|------------------|
|  PHP  | `^8.3` |   `HEAD ⋯ 5.0.0`   |
|  | `^8.2` |   `HEAD ⋯ 2.0.0`   |
|  | `^8.1` |   `HEAD ⋯ 2.0.0`   |
|  | `^8.0` |   `4.6.0 ⋯ 2.0.0`   |
|  | `^8.0.0` |   `1.1.2 ⋯ 0.12.0`   |
|  | `>=8.0.0` |   `0.11.0 ⋯ 0.5.0`   |
|  Laravel  | `^10.0.0` |   `HEAD ⋯ 2.1.0`   |
|  | `^9.21.0` |   `5.4.0 ⋯ 5.0.0-beta.1`   |
|  | `^9.0.0` |   `5.0.0-beta.0 ⋯ 0.12.0`   |
|  | `^8.22.1` |   `3.0.0 ⋯ 0.5.0`   |
|  Lighthouse  | `^6.5.0` |   `HEAD ⋯ 5.0.0-beta.0`   |
|  | `^6.0.0` |   `4.6.0 ⋯ 4.0.0`   |
|  | `^5.68.0` |   `3.0.0 ⋯ 2.0.0`   |
|  | `^5.8.0` |   `1.1.2 ⋯ 0.13.0`   |
|  | `^5.6.1` |  `0.12.0`  ,  `0.11.0`   |
|  | `^5.4` |   `0.10.0 ⋯ 0.5.0`   |

[//]: # (end: 876a9177c0e8e3722ac84e8f3888245fc9070a64a87dedfe7c9d9ba2a13b374b)

[include:template]: ../../docs/Shared/Installation.md ({"data": {"package": "graphql"}})
[//]: # (start: 3672f37b7c4365391f12ad7a15e2e629126d25f774c584c29e7930695f44040a)
[//]: # (warning: Generated automatically. Do not edit.)

# Installation

```shell
composer require lastdragon-ru/lara-asp-graphql
```

[//]: # (end: 3672f37b7c4365391f12ad7a15e2e629126d25f774c584c29e7930695f44040a)

# Configuration

Config can be used, for example, to customize supported operators for each type. Before this, you need to publish it via the following command, and then you can edit `config/lara-asp-graphql.php`.

```shell
php artisan vendor:publish --provider=LastDragon_ru\\LaraASP\\GraphQL\\Provider --tag=config
```

# Directives

[include:document-list]: ./docs/Directives
[//]: # (start: 73f7f4a1d86b7731354837c827f1b9f9aa729879639aeab4fe63985913469f48)
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

[//]: # (end: 73f7f4a1d86b7731354837c827f1b9f9aa729879639aeab4fe63985913469f48)

# Scalars

> [!IMPORTANT]
>
> You should register the Scalar before use, it can be done via [`AstManipulator`](./src/Utils/AstManipulator.php) (useful while AST manipulation), [`TypeRegistry`](https://lighthouse-php.com/master/digging-deeper/adding-types-programmatically.html#using-the-typeregistry), or as a custom scalar inside the Schema:
>
> ```graphql
> scalar JsonString
> @scalar(
>     class: "LastDragon_ru\\LaraASP\\GraphQL\\Scalars\\JsonStringType"
> )
> ```

[include:document-list]: ./docs/Scalars
[//]: # (start: 12e162fc2ab7e9e247529882b53731fb8f8aacc4c5532610d40d36e90977b8f2)
[//]: # (warning: Generated automatically. Do not edit.)

## `JsonString`

Represents [JSON](https://json.org) string.

[Read more](<docs/Scalars/JsonString.md>).

[//]: # (end: 12e162fc2ab7e9e247529882b53731fb8f8aacc4c5532610d40d36e90977b8f2)

# Scout

[Scout](https://laravel.com/docs/scout) is also supported 🤩. By default `@searchBy`/`@sortBy` will convert nested/related properties into dot string: eg `{user: {name: asc}}` will be converted into `user.name`. You can redefine this behavior by [`FieldResolver`](./src/Builder/Contracts/Scout/FieldResolver.php):

```php
// AppProvider

$this->app->bind(
    LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Scout\FieldResolver::class,
    MyScoutColumnResolver::class,
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
[//]: # (start: 1484174aafe709bdcddfbae43df1e4400693ef8a41420873835aea07d36a63b3)
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

[//]: # (end: 1484174aafe709bdcddfbae43df1e4400693ef8a41420873835aea07d36a63b3)

# Printer

The package provides bindings for [`Printer`][pkg:graphql-printer] so you can simply use:

[include:example]: ./docs/Examples/Printer.php
[//]: # (start: 518647e0d4a82c1d00956d3649304d6a454b183b3a926a8b403e94d33fb4301c)
[//]: # (warning: Generated automatically. Do not edit.)

```php
<?php declare(strict_types = 1);

use Illuminate\Container\Container;
use LastDragon_ru\LaraASP\Dev\App\Example;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\DirectiveFilter;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\Printer;
use LastDragon_ru\LaraASP\GraphQLPrinter\Settings\DefaultSettings;
use Nuwave\Lighthouse\Schema\SchemaBuilder;

$schema   = Container::getInstance()->make(SchemaBuilder::class)->schema();
$printer  = Container::getInstance()->make(Printer::class);
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
    | ENUM
    | INPUT_FIELD_DEFINITION
    | SCALAR

directive @searchByOperatorAnyOf
on
    | ENUM
    | INPUT_FIELD_DEFINITION
    | SCALAR

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
    | ENUM
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

directive @searchByOperatorProperty
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
Available conditions for `type User` (only one property allowed at a time).
"""
input SearchByConditionUser {
    """
    All of the conditions must be true.
    """
    allOf: [SearchByConditionUser!]
    @searchByOperatorAllOf

    """
    Any of the conditions must be true.
    """
    anyOf: [SearchByConditionUser!]
    @searchByOperatorAnyOf

    """
    Property condition.
    """
    id: SearchByScalarID
    @searchByOperatorProperty

    """
    Property condition.
    """
    name: SearchByScalarString
    @searchByOperatorProperty

    """
    Not.
    """
    not: SearchByConditionUser
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
        where: SearchByConditionUser
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

[//]: # (end: 518647e0d4a82c1d00956d3649304d6a454b183b3a926a8b403e94d33fb4301c)

There are also few great new [GraphQL Assertions](./src/Testing/GraphQLAssertions.php).

| Name                        | Description              |
|-----------------------------|--------------------------|
| `assertGraphQLSchemaEquals` | Compares default schema. |

[include:file]: ../../docs/Shared/Contributing.md
[//]: # (start: 057ec3a599c54447e95d6dd2e9f0f6a6621d9eb75446a5e5e471ba9b2f414b89)
[//]: # (warning: Generated automatically. Do not edit.)

# Contributing

This package is the part of Awesome Set of Packages for Laravel. Please use the [main repository](https://github.com/LastDragon-ru/lara-asp) to [report issues](https://github.com/LastDragon-ru/lara-asp/issues), send [pull requests](https://github.com/LastDragon-ru/lara-asp/pulls), or [ask questions](https://github.com/LastDragon-ru/lara-asp/discussions).

[//]: # (end: 057ec3a599c54447e95d6dd2e9f0f6a6621d9eb75446a5e5e471ba9b2f414b89)

[include:file]: ../../docs/Shared/Links.md
[//]: # (start: 6c180b37114202a8766bad1a59a3c0699948cc875617c85fd14a024e3bca64fe)
[//]: # (warning: Generated automatically. Do not edit.)

[pkg:graphql#@searchBy]: https://github.com/LastDragon-ru/lara-asp/tree/main/packages/graphql/docs/Directives/@searchBy.md

[pkg:graphql#@sortBy]: https://github.com/LastDragon-ru/lara-asp/tree/main/packages/graphql/docs/Directives/@sortBy.md

[pkg:graphql-printer]: https://github.com/LastDragon-ru/lara-asp/tree/main/packages/graphql-printer

[//]: # (end: 6c180b37114202a8766bad1a59a3c0699948cc875617c85fd14a024e3bca64fe)
