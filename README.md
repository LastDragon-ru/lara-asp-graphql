# The GraphQL Extensions

> This package is the part of Awesome Set of Packages for Laravel.
>
> [Read more](https://github.com/LastDragon-ru/lara-asp).

This package provides highly powerful `@searchBy` and `@sortBy`  directives for [lighthouse-php](https://lighthouse-php.com/). The `@searchBy` directive provides basic conditions like `=`, `>`, `<`, etc, relations, `not (<condition>)`, enums, and custom operators support. All are strictly typed so you no need to use `Mixed` type anymore. The `@sortBy` is not only about standard sorting by columns but also allows use relations. 😎

# Installation

```shell
composer require lastdragon-ru/lara-asp-graphql
```

# Configuration

Config can be used, for example, to customize supported operators for each type. Before this, you need to publish it via the following command, and then you can edit `config/lara-asp-graphql.php`.

```shell
php artisan vendor:publish --provider=LastDragon_ru\\LaraASP\\GraphQL\\Provider --tag=config
```

# `@searchBy` directive

At this moment this is probably the most powerful directive to provide search (`where` conditions) for your GraphQL queries.

## Basic usage

Out the box directives provides following features:

* Strictly typed - you can define supported operators for each Scalar;
* Eloquent Builder, Query Builder and Custom Builder support;
* Support almost all `where` operators;
* Enums support;
* `not (<condition>)` support;
* Relations support (Eloquent Builder only);
* Custom operators support
* easy to use and safe.

Let's start:

```graphql
scalar Date @scalar(class: "Nuwave\\Lighthouse\\Schema\\Types\\Scalars\\Date")

type Query {
    users(where: _ @searchBy): [User!]! @all
    comments(where: CommentsQuery @searchBy): [Comment!]! @all
}

input UsersQuery {
    id: ID!
    name: String!
}

input CommentsQuery {
    text: String!
    user: UsersQuery
    date: Date
}

type User {
    id: ID!
    name: String!
}

input Comment {
    text: String!
    user: User
    date: Date
}
```

That's all, just search 😃 (or look at [generated GraphQL schema](./src/SearchBy/Directives/DirectiveTest~example-expected.graphql))

```graphql
query {
    # WHERE name = "LastDragon"
    users(where: {
        name: {equal: "LastDragon"}
    }) {
        id
    }

    # WHERE name != "LastDragon"
    users(where: {
        name: {notEqual: "LastDragon"}
    }) {
        id
    }

    # WHERE name = "LastDragon" or name = "Aleksei"
    users(where: {
        anyOf: [
            {name: {equal: "LastDragon"}}
            {name: {equal: "Aleksei"}}
        ]
    }) {
        id
    }

    # WHERE NOT (name = "LastDragon" or name = "Aleksei")
    users(where: {
        not: {
            anyOf: [
                {name: {equal: "LastDragon"}}
                {name: {equal: "Aleksei"}}
            ]
        }
    }) {
        id
    }

    # WHERE date IS NULL
    users(where: {
        date: {isNull: yes}
    }) {
        id
    }

    # Relation: WHERE EXIST (related table)
    comments(where: {
        user: {
            where: {
                date: {between: {min: "2021-01-01", max: "2021-04-01"}}
            }
        }
    }) {
        id
    }

    # Relation: WHERE COUNT (related table) = 2
    comments(where: {
        user: {
            where: {
                date: {between: {min: "2021-01-01", max: "2021-04-01"}}
            }
            count: {
                equal: 2
            }
        }
    }) {
        id
    }
}
```

## Input type auto-generation

As you can see in the example above you can use the special placeholder `_` instead of real `input`. In this case, `@searchBy` will generate `input` automatically by the actual `type` of the query. While converting `type` into `input` following fields will be excluded:

* unions
* with `@field` directive
* with `@searchByIgnored` directive
* with any directive that implements [`Ignored`](./src/SearchBy/Contracts/Ignored.php)
* any `Type` that implements [`Ignored`](./src/SearchBy/Contracts/Ignored.php)

## Operators

There are three types of operators:

* Comparison - used to compare column with value(s), eg `{equal: "value"}`, `{lt: 2}`, etc. To add your own you just need to implement [`Operator`](./src/Builder/Contracts/Operator.php) and add it to type(s);
* Extra - used to add additional fields, by default package provides few Logical operators which allow you to do eg `anyOf([{equal: "a"}, {equal: "b"}])`. Adding your own is the same: implement [`Operator`](./src/Builder/Contracts/Operator.php) and add it to `Operators::Extra` type;
* Condition - used to create conditions for nested Input types and allow implement any logic eg `whereHas`, `whereDoesntHave`, etc. All the same, but these operators should be explicitly added to the fields/input types, by default the [`Relation`](./src/SearchBy/Operators/Complex/Relation.php) operator will be used:

    ```graphql
    type Query {
        users(where: UsersQuery @searchBy): ID! @all
        comments(where: CommentsQuery @searchBy): ID! @all
    }

    input UsersQuery {
        id: ID!
        name: String!
    }

    input CommentsQuery {
        text: String!
        user: UsersQuery @myComplexOperator
    }
    ```

By default, the package provide list of predefined operators for each build-in type. To extend/replace the list, you can use config and/or add directives to scalar/enum inside the schema. Directives is the recommended way and have priority over the config.

The package also defines a few own types in addition to the standard GraphQL types:

* `SearchByCondition` / [`Operators::Condition`](./src/SearchBy/Operators.php) - List of known Condition operators. If no directive is found, the first supported operator from the list will be used.
* `SearchByNumber` / [`Operators::Number`](./src/SearchBy/Operators.php) - Any operator for this type will be available for `Int` and `Float`.
* `SearchByNull` / [`Operators::Null`](./src/SearchBy/Operators.php) - Additional operators available for nullable fields.
* `SearchByExtra` / [`Operators::Extra`](./src/SearchBy/Operators.php) - List of additional extra operators for all types.
* `SearchByEnum` / [`Operators::Enum`](./src/SearchBy/Operators.php) - Default operators for enums.

```php
<?php declare(strict_types = 1);

use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Operator;

/**
 * -----------------------------------------------------------------------------
 * GraphQL Settings
 * -----------------------------------------------------------------------------
 *
 * @var array{
 *      search_by: array{
 *          operators: array<string, array<string|class-string<Operator>>>
 *      }
 *      } $settings
 */
$settings = [
    /**
     * Settings for {@see \LastDragon_ru\LaraASP\GraphQL\SearchBy\Definitions\SearchByDirective @searchBy} directive.
     */
    'search_by' => [
        /**
         * Operators
         * ---------------------------------------------------------------------
         *
         * You can (re)define types and supported operators here.
         *
         * @see Operator
         */
        'operators' => [
            // You can define a list of operators for each type
            'Date'     => [
                Equal::class,
                Between::class,
                MyCustomOperator::class,
            ],

            // Or re-use existing type
            'DateTime' => [
                'Date',
            ],

            // You can also use enum name to redefine default operators for it:
            'MyEnum' => [
                'Boolean',
            ],
        ],
    ],
];

return $settings;
```

```graphql
# Only two operators will be available.
enum MyEnum
@searchByOperatorEqual
@searchByOperatorNotEqual
{
    Enabled
    Disabled
}

# All operators from `Int` type and `@myOperator` will be available.
scalar MyScalar
@searchByOperators(type: "Int")
@myOperator
```

# `@sortBy` directive

## Basic usage

How to use (and [generated GraphQL schema](./src/SortBy/Directives/DirectiveTest~example-expected.graphql)):

```graphql
type Query {
    "You can use normal input type"
    users(order: UsersSort @sortBy): ID! @all

    "or `_` to generate type automatically 😛"
    comments(order: _ @sortBy): [Comment!]! @all
}

input UsersSort {
    id: ID!
    name: String!
}

type Comment {
    text: String
    user: User
}

type User {
    id: ID!
    name: String!
}
```

And:

```graphql
query {
    # ORDER BY user.name ASC, text DESC
    comments(order: [
        {user: {name: asc}}
        {text: desc}
    ])
}
```

## Input type auto-generation

As you can see in the example above you can use the special placeholder `_` instead of real `input`. In this case, `@sortBy` will generate `input` automatically by the actual `type` of the query. While converting `type` into `input` following fields will be excluded:

* unions
* with list/array type
* with `@field` directive
* with `@sortByIgnored` directive
* with any directive that implements [`Ignored`](./src/SortBy/Contracts/Ignored.php)
* any `Type` that implements [`Ignored`](./src/SortBy/Contracts/Ignored.php)

## Operators

The package defines only one's own type. To extend/replace the list of its operators, you can use config and/or add directives to scalar/enum inside the schema. Directives is the recommended way and have priority over the config.

* `SortByExtra` / [`Operators::Extra`](./src/SortBy/Operators.php) - List of additional extra operators for all types. The list is empty by default.

## Eloquent/Database

### Supported Relations

The main feature - the ability to sort results by relation properties, at the moment supported the following relation types:

* `HasOne` (<https://laravel.com/docs/eloquent-relationships#one-to-one>)
* `HasMany` (<https://laravel.com/docs/eloquent-relationships#one-to-many>)
* `HasManyThrough` (<https://laravel.com/docs/eloquent-relationships#has-many-through>)
* `BelongsTo` (<https://laravel.com/docs/eloquent-relationships#one-to-many-inverse>)
* `BelongsToMany` (<https://laravel.com/docs/eloquent-relationships#many-to-many>)
* `MorphOne` (<https://laravel.com/docs/eloquent-relationships#one-of-many-polymorphic-relations>)
* `MorphMany` (<https://laravel.com/docs/eloquent-relationships#one-to-many-polymorphic-relations>)
* `MorphToMany` (<https://laravel.com/docs/eloquent-relationships#many-to-many-polymorphic-relations>)
* `HasOneThrough` (<https://laravel.com/docs/eloquent-relationships#has-one-through>)

### Order by random

It is also possible to sort records in random order, but it is not enabled by default. To enable it you just need to add [`Random`](./src/SortBy/Operators/Extra/Random.php)/`@sortByOperatorRandom` operator/directive to `Extra` type:

```php
<?php declare(strict_types = 1);

use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Operator;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Operators as SortByOperators;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Operators\Extra\Random;

/**
 * -----------------------------------------------------------------------------
 * GraphQL Settings
 * -----------------------------------------------------------------------------
 *
 * @var array{
 *      sort_by: array{
 *          operators: array<string, array<string|class-string<Operator>>>
 *      },
 *      } $settings
 */
$settings = [
    /**
     * Settings for {@see \LastDragon_ru\LaraASP\GraphQL\SortBy\Definitions\SortByDirective @sortBy} directive.
     */
    'sort_by'   => [
        /**
         * Operators
         * ---------------------------------------------------------------------
         *
         * You can (re)define types and supported operators here.
         *
         * @see Operator
         */
        'operators' => [
            SortByOperators::Extra => [
                Random::class,
            ],
        ],
    ],
];

return $settings;
```

or

```graphql
scalar SortByExtra
@sortByOperatorRandom
```

And after this, you can 🎉

```graphql
query {
    # ORDER BY RANDOM()
    comments(order: [
        {random: yes}
    ])
}
```

# Scout

[Scout](https://laravel.com/docs/scout) is also supported 🤩. By default `@searchBy`/`@sortBy` will convert nested/related properties into dot string: eg `{user: {name: asc}}` will be converted into `user.name`. You can redefine this behavior by [`FieldResolver`](./src/Builder/Contracts/Scout/FieldResolver.php):

```php
// AppProvider

$this->app->bind(
    LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Scout\FieldResolver::class,
    MyScoutColumnResolver::class,
);
```

# Relations

Important note about relations: they must have proper type-hint, so:

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

# Printer

The package provides bindings for [`Printer`](../graphql-printer/README.md) so you can simply use:

```php
<?php declare(strict_types = 1);

use Illuminate\Container\Container;
use LastDragon_ru\LaraASP\GraphQLPrinter\Printer;
use Nuwave\Lighthouse\Schema\SchemaBuilder;

$schema  = Container::getInstance()->make(SchemaBuilder::class)->schema();
$printer = Container::getInstance()->make(Printer::class);
$printed = $printer->printSchema($schema);
```

There are also few great new [GraphQL Assertions](./src/Testing/GraphQLAssertions.php).

| Name                               | Description              |
|------------------------------------|--------------------------|
| `assertDefaultGraphQLSchemaEquals` | Compares default schema. |
