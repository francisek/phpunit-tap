# PHPUnit TAP Printer

Since PHPUnit 6.0, TAP (Test Anything Protocol) format has been removed.
This library is a simple port of the old code ported to be compliant with PHPUnit >= 6.0.

This is mostly a copy / paste from Sebastian Bergmann's old code (https://github.com/sebastianbergmann/phpunit). 
All credits go to him.

## Installation

Install this library in your project with composer:

```$bash
$ php composer.phar --dev require francisek/phpunit-tap 
```

## Usage

### phpunit.xml

Just add the following to your phpunit configuration file:

```xml
<listeners>
  <listener class="Francisek\\PHPUnitTap\\TapPrinter" file="/optional/path/to/TapPrinter.php">
  </listener>
</listeners>
```

### Command line

Just define the printer as a parameter to PHPUnit:

```$bash
$ ./vendor/bin/phpunit --printer Francisek\\PHPUnit\\TapPrinter path/to/tests/ 
```

