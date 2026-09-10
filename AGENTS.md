# Agents.md

Every maker must also work with `--no-interaction`. `Command::run()` skips `interact()`
entirely in that mode, so anything only set there is missing in `generate()`.

## The one rule

Never keep a value on a property. Put it on the `InputInterface` as an option or
argument. `interact()` asks only when the value is missing and writes the answer onto the
input. `generate()` reads the value from the input, never from a property.

```php
public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
{
    if (!$input->getOption('controller-class')) {
        $input->setOption('controller-class', $io->ask('...', self::getDefaultControllerClass()));
    }
}

public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
{
    $controllerClass = $input->getOption('controller-class') ?: self::getDefaultControllerClass();
}
```

A typed property with no default, assigned only in `interact()` and read in `generate()`,
throws `Typed property ... must not be accessed before initialization` under
`--no-interaction`. That shape is what to grep for.

## Guesses need a silent half

A guesser like `InteractiveSecurityHelper::guessUserClass()` returns silently when the
answer is unambiguous, and prompts otherwise with no default. That prompt cannot run
under `--no-interaction`. Extract the silent half into its own method that returns `null`
instead of prompting, have the `guess*` method call it first, and have `generate()` call
it directly and throw a `RuntimeCommandException` naming the option when it returns
`null`.

## Errors must be `RuntimeCommandException`

`ConsoleErrorSubscriber` renders that type onto stdout with exit code 1. Anything else,
including `\InvalidArgumentException` from `Validator::*`, goes to stderr and is invisible
to a caller capturing stdout.

## Booleans are `VALUE_NONE` options, not string arguments

No maker file declares `strict_types`, so a string fed into a `bool` parameter coerces
silently: `"false"` and `"no"` both become `true`.

## Prefer the generic argument prompt

`MakerCommand::interact()` already prompts for any empty declared argument, using its
description as the question, before your maker's own `interact()` runs. If that's good
enough, skip `setArgumentAsNonInteractive()` and the manual `$io->ask()`, and just return
early when the argument is already set. Reach for the manual version only when the
generic prompt isn't enough: autocompletion, a specific validator, a choice list, or
something that must happen before the question is asked.

## Watch for a second `composer require` in one process

If both `interact()` and `generate()` might install a package, don't call
`installDependencyIfNeeded()` unconditionally from both. Its `class_exists()` guard can't
see a class installed earlier in the same process, so a second call installs the package
again, and the second `composer require` corrupts the DI container cache mid-run. Guard
the second call some other way, such as checking whether the config file the recipe
writes already exists.

## Testing

Add a non-interactive happy path per option: `$runner->runMaker([], '<args> --no-interaction')`.
For values with no sensible default, add a rejection case with `allowedToFail: true`,
asserting the message and that nothing was written. On Windows, `cmd.exe` does not treat
single quotes as quoting, use double quotes in test arguments.
