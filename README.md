---
permalink: index.html
layout: "default"
---

# PDOx

PDOx is a small PDO extension that enables developers to simple run a single query to map a table to the desired object.
For this purpose it utilizes either native PDO or laminas-hydrator.

## Installation

Simply run `composer require jinya/pdox` in your project.

## Usage

PDOx contains three methods apart from the default PDO methods.

### `$pdo->fetchObject($query, $prototype, $parameters, $strategies)`

This method allows you to fetch one single object from the query and hydrate the result into a class of the type
of `$prototype`. An example call can look like this:

```php
<?php
use Laminas\Hydrator\Strategy\BooleanStrategy;

require_once __DIR__ . '/vendor/autoload.php';

$pdox = new Jinya\PDOx\PDOx('sqlite::memory:');
$result = $pdox->fetchObject('SELECT * FROM table_test WHERE id = ?', new MyObject(), [1], [
    'active' => new BooleanStrategy('1','0'),
]);
var_dump($result);
```

The code above is really simple, as first parameter you give it the query, as second parameter a new instance of the
object the PDOx should hydrate into, as third the parameters of your query and lastly the strategies. Strategies are a
component of laminas hydrator and you can find our more about
them [here](https://docs.laminas.dev/laminas-hydrator/v4/strategy/).

### `$pdo->fetchIterator($query, $prototype, $parameters, $strategies)`

This method allows you to fetch an iterator of objects from the query and hydrate the results into a class of the type
of `$prototype`. An example call can look like this:

```php
<?php
use Laminas\Hydrator\Strategy\BooleanStrategy;

require_once __DIR__ . '/vendor/autoload.php';

$pdox = new Jinya\PDOx\PDOx('sqlite::memory:');
$result = $pdox->fetchIterator('SELECT * FROM table_test WHERE id = ?', new MyObject(), [1], [
    'active' => new BooleanStrategy('1','0'),
]);
var_dump($result);
```

The method call for `fetchIterator` and `fetchObject` is basically the same. You only need to replace the method name
and PDOx does the rest for you.

### `$pdo->fetchArray($query, $prototype, $parameters, $strategies)`

This method allows you to fetch an iterator of objects from the query and hydrate the results into a class of the type
of `$prototype`. An example call can look like this:

```php
<?php
use Laminas\Hydrator\Strategy\BooleanStrategy;

require_once __DIR__ . '/vendor/autoload.php';

$pdox = new Jinya\PDOx\PDOx('sqlite::memory:');
$result = $pdox->fetchArray('SELECT * FROM table_test WHERE id = ?', new MyObject(), [1], [
    'active' => new BooleanStrategy('1','0'),
]);
var_dump($result);
```

The method call for `fetchArray` and `fetchIterator` is basically the same. You only need to replace the method name and
PDOx does the rest for you.

## Configuration

PDOx introduces two new fields for the PDO options. These are passed in the constructor. Using these new options you can
control whether PDOx should transform the field names and how it should handle empty results by returning `null` or
throwing an exception.

### `PDOx::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE`

Possible Value: `true` or `false`

Purpose: Controls whether the casing should be converted between underscore case in the database to CamelCase in PHP

### `PDOx::PDOX_NO_RESULT_BEHAVIOR`

Possible value: `PDOx::PDOX_NO_RESULT_BEHAVIOR_NULL` or `PDOx::PDOX_NO_RESULT_BEHAVIOR_EXCEPTION`

Purpose: Controls how PDOx should handle no result in `fetchObject`

## Found a bug?

If you found a bug feel free to create an issue on Github or on our Taiga
instance: [https://taiga.imanuel.dev/project/pdox/](https://taiga.imanuel.dev/project/pdox/)

## License

Like all other Jinya projects, PDOx is distributed under the MIT License.
