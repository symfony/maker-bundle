SymfonyMakerBundle
==================

Symfony Maker helps you creating empty commands, controllers, form classes,
tests and more so you can forget about the required boilerplate code. This
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
Imagine that you need to create a ``make:report`` command. First, create an
empty command:

.. code-block:: terminal

    $ cd your-project/
    $ php bin/console make:command 'make:report'

Then, change the generated command to extend from
:class:`Symfony\\Bundle\\MakerBundle\\Command\\AbstractCommand`, which is the
base command used by all ``make:`` commands:

.. code-block:: diff

    // ...
    -use Symfony\Component\Console\Command\Command;
    +use Symfony\Bundle\MakerBundle\Command\AbstractCommand;

    -class MakeReportCommand extends Command
    +class MakeReportCommand extends AbstractCommand
    {
        protected static $defaultName = 'make:report';

        // ...
    }

Finally, implement the methods required by the ``AbstractCommand`` class::

    // ...
    use Symfony\Bundle\MakerBundle\Command\AbstractCommand;
    use Symfony\Bundle\MakerBundle\ConsoleStyle;
    use Symfony\Bundle\MakerBundle\DependencyBuilder;

    class MakeReportCommand extends AbstractCommand
    {
        protected static $defaultName = 'make:report';

        // ...

        // Returns pairs of name-value parameters used to fill in the
        // skeleton files of the generated code and the success/error messages
        protected function getParameters(): array
        {
            return [
                'filename' => sprintf('report-%s.txt', date('YmdHis')),
            ];
        }

        // Returns pairs of skeleton files (absolute paths) and their corresponding
        // generated files (with paths relative to the app)
        protected function getFiles(array $params): array
        {
            return [
                __DIR__.'/../Resources/skeleton/report.txt' => 'reports/'.$params['filename'];
            ];
        }

        // Optionally, display some message after the generation of code
        protected function writeNextStepsMessage(array $params, ConsoleStyle $io)
        {
            $io->text(sprintf('A new report was generated in the %s file.', $params['filename']));
        }

        // Optionally, define which classes must exist in the application to make
        // this command work (useful to ensure that needed dependencies are installed)
        protected function configureDependencies(DependencyBuilder $dependencies)
        {
            $dependencies->addClassDependency(PdfGenerator::class, ['acme-pdf-generator'], true);
        }
    }

.. _`SensioGeneratorBundle`: https://github.com/sensiolabs/SensioGeneratorBundle
.. _`Symfony Flex`: https://symfony.com/doc/current/setup/flex.html
