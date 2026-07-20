# PHPColliderScope

PHP namespace collision detection utility. Scans a directory tree of `.php`
files and reports classes, interfaces, traits, enums, and functions that are
declared more than once under the same fully-qualified name — the kind of
thing that silently breaks autoloading when two packages (or two copies of
the same package) define the same class.

## Install

```sh
composer require --dev s-mcdonald/phpcolliderscope
```

## CLI usage

```sh


# No path given -> scans the current working directory
/bin/collider

# Scan a directory and display summary
/bin/collider /path/to/dir

# List every occurrence of each colliding symbol
/bin/collider /path/to/dir --full
```

Files that fail to parse are reported as warnings and skipped, rather than
aborting the scan.

## Programmatic usage: Building a file list with `FileSet`

Scanning "a directory" isn't always what you want — real projects need to
skip `vendor/`, test fixtures, generated code, etc. `FsKit\FileSet` builds an
explicit, de-duplicated file list from multiple directories/files with
exclusions, which you hand to `inspectFileSetForCollisions()` instead of a
single `Directory`:

```php
use FsKit\FileSet;
use PHPColliderScope\PHPDocumentParser\Parser;

$fileSet = FileSet::createFromDir(__DIR__ . '/src')
    ->addDir(__DIR__ . '/modules')
    ->addFile(__DIR__ . '/legacy/bootstrap.php')
    ->excludeDirectoryFile(__DIR__ . '/src/Generated')
    ->excludeFile(__DIR__ . '/src/DoNotCheck.php');

$report = $parser->inspectFileSetForCollisions($fileSet);
```

Each `add*`/`exclude*` call returns a new `FileSet` (they're immutable), and
only `.php` files are ever inspected — everything else in the set is ignored
automatically.

## CI/CD

The `check` command exits with a non-zero status when it finds collisions
(or is given a bad path), so you can drop it straight into a pipeline as a
gate — no extra scripting needed:

| Exit code | Meaning |
|-----------|---|
| `0`       | No collisions found |
| `1`       | Given path does not exist |
| `2`       | Collisions found |

### GitHub Actions

```yaml
name: Collision check

on: [push, pull_request]

jobs:
  collider:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - run: composer install --no-progress
      - run: vendor/bin/collider src --full
```

### GitLab CI

```yaml
collider:
  image: php:8.3-cli
  script:
    - composer install --no-progress
    - vendor/bin/collider src --full
```

Any CI system that fails a job on a non-zero exit code will work the same
way — just run `vendor/bin/collider <path> --full` as a step.

## Requirements

- PHP >= 8.3
