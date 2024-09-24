# lucite/model
A simple data fetching layer that uses PDO.

## Installation
`composer require lucite/model`

## Usage

A model defines 5 methods:

- ->fetchOne(int|string $id): array
- ->fetchMany(): array
- ->create(array $data): array
- ->update(int|string $id, array $data): array
- ->delete(int|string $id): void

To create a model, implement a class that extends `Lucite\Model\Model` and define 3 static attributes:

- public static string $tableName # the name of the table to use
- public static string $primaryKey # the name of the table's primary key (compound primary keys are not yet supported)
- public static array $columns # the table's columns, excluding the primary key.

You must also define two methods that are used to apply permissions:

- public function applyPermissionValues(array &$data): void
- public function getPermissionFilter(array &$args): string

If the table in question will never have any permissions enforced, you can use Lucite\Model\NoPermissionCheckTrait to add dummy implementations of these methods.

## Instantiating a model
The model's constructor requires two parameters:

- PDO $db
- Psr\Log\LoggerInterface $logger

You may want to look at lucite/mocklogger for a simple psr-3 logger that can be used for unit testing

## Debugging

Every time a model runs a query (which might be multiple times per method), two messages are logged using the ->debug method of the logger passed to the model constructor:

- The query, including placeholders (eg, `SELECT id FROM table WHERE id=:id`)
- The args passed to the PDO::Statement ->execute function (eg, `["id" => 4]`)


## Implementing permissions
Details coming soon, but there is a simple example in `src/Tests/ModelWithPermissionsTest.php`;

## Future features
### Add support for 4 parameters to fetchMany

- `int $page`
- `int $pageSize`
- `string $sortColumn`
- `array $filters` (format tbd)

### Support for multicolumn filtering

Details tbd.
