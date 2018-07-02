# Component: Command line interface

### Instructions

Initial Setup: See sections _Requirements_ and _Setup_

Provides a standalone CLI framework featuring:

- Colored console output with an easy way to add your own CLI style for your commands

- Argument-objects with easy and clear insight to custom argument handling

- Command-objects with easy and clear insight to custom command handlers

- 4 lines of code to get your CLI working

- For a detailed example executable, checkout [example-executable.php](https://github.com/schilffarth/command-line-interface/blob/master/example/example-executable.php)

Example code implementation:

```php
$objectManager = new \Schilffarth\DependencyInjection\Source\ObjectManager();
$app = $objectManager->getSingleton(\Schilffarth\CommandLineInterface\Source\App::class);
$app->includeCommandDir('Path/For/Commands/To/Be/Registered', 'Namespace\Of\Your\Commands');
$app->execute($argv);
```

What we're doing...

First, initialize the CLI application

```php
// Of course you can use any automatic constructor dependency injection, such as Symfonys DI
// Build a new object manager for initializing the CLI application
$objectManager = new \Schilffarth\DependencyInjection\Source\ObjectManager();

// Get the CLI app object
$app = $objectManager->getSingleton(\Schilffarth\CommandLineInterface\Source\App::class);
```

Secondly, add your commands

```php
// Specify as many commands you want to be available when your executable PHP file is run from the console
// First argument is the directory your command classes are located at
// Second argument is the commands namespace
$app->includeCommandDir('Path/For/Commands/To/Be/Registered', 'Namespace\Of\Your\Commands');

// Execute the CLI
$app->execute($argv);
```

... that's it!

### Releases

- 1.0.0

First release

### Requirements

- [composer](https://getcomposer.org/doc/01-basic-usage.md)

- PHP 7.2 or newer

### Composer dependencies

- Component: [Exception](https://github.com/schilffarth/exception)

- Component: [Automatic constructor dependency injection](https://github.com/schilffarth/dependency-injection)

### Setup

- Add the requirement to your project's composer.json and run `composer update`

```
"require": {
  "schilffarth/command-line-interface": "^1.0.0"
},
"repositories": [
  {"type": "git", "url": "https://github.com/schilffarth/command-line-interface"}
],
```

### Authors

Roland Schilffarth [roland@schilffarth.org](mailto:roland@schilffarth.org)

### License

[GNU General Public License version 3](https://opensource.org/licenses/GPL-3.0)
