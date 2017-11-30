The Symfony MakerBundle
=======================

Symfony Maker helps you create empty commands, controllers, form classes,
tests and more so you can forget about writing boilerplate code. This
bundle is an alternative to `SensioGeneratorBundle`_ for modern Symfony
applications and requires using Symfony 3.4 or newer and `Symfony Flex`_.

Installation
------------

Run this command to install and enable this bundle in your application:

.. code-block:: terminal

    $ composer require maker

Usage
-----

This bundle provides several commands under the ``make:`` namespace. List them
all executing this command:

.. code-block:: terminal

    $ php bin/console list make

     make:command            Creates a new console command class
     make:controller         Creates a new controller class
     make:entity             Creates a new Doctrine entity class

     [...]

     make:validator          Creates a new validator and constraint class
     make:voter              Creates a new security voter class

The names of the commands are self-explanatory, but some of them include
optional arguments and options. Check them out with the ``--help`` option:

.. code-block:: terminal

    $ php bin/console make:controller --help

Creating your Own Makers
------------------------

In case your applications need to generate custom boilerplate code, you can
create your own ``make:...`` command reusing the tools provided by this bundle.
Imagine that you need to create a ``make:report`` command. First, create a
class that implements :class:`Symfony\\Bundle\\MakerBundle\\MakerInterface`::

    // src/Maker/ReportMaker.php
    namespace App\Maker;

    use Symfony\Bundle\MakerBundle\ConsoleStyle;
    use Symfony\Bundle\MakerBundle\DependencyBuilder;
    use Symfony\Bundle\MakerBundle\InputConfiguration;
    use Symfony\Bundle\MakerBundle\MakerInterface;
    use Symfony\Bundle\MakerBundle\Str;
    use Symfony\Bundle\MakerBundle\Validator;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputArgument;
    use Symfony\Component\Console\Input\InputInterface;

    class ReportMaker implements MakerInterface
    {
        public static function getCommandName(): string
        {
            return 'make:report';
        }

        public function configureCommand(Command $command, InputConfiguration $inputConf): void
        {
            $command
                ->setDescription('Creates a new report')
                ->addArgument('name', InputArgument::OPTIONAL, 'Choose the report format', 'pdf')
            ;
        }

        public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
        {
        }

        public function getParameters(InputInterface $input): array
        {
            return [];
        }

        public function getFiles(array $params): array
        {
            return [];
        }

        public function writeNextStepsMessage(array $params, ConsoleStyle $io): void
        {
        }

        public function configureDependencies(DependencyBuilder $dependencies): void
        {
        }
    }

For examples of how to complete this class, see the `core maker commands`_.
Make sure your class is registered as a service and tagged with ``maker.command``.
If you're using the standard Symfony ``services.yaml`` configuration, this
will be done automatically.

.. _`SensioGeneratorBundle`: https://github.com/sensiolabs/SensioGeneratorBundle
.. _`Symfony Flex`: https://symfony.com/doc/current/setup/flex.html
.. _`core maker commands`: https://github.com/symfony/maker-bundle/tree/master/src/Maker
