The Symfony MakerBundle
=======================

Symfony Maker helps you create empty commands, controllers, form classes,
tests and more so you can forget about writing boilerplate code. This bundle
assumes you're using a standard Symfony directory structure, but many
commands can generate code into any application.

Installation
------------

Run this command to install and enable this bundle in your application:

.. code-block:: terminal

    $ composer require --dev symfony/maker-bundle

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

.. caution::

    ``make:entity`` requires ``doctrine/orm`` to be installed and configured. This maker support only ORM, not ODM.

Linting Generated Code
______________________

MakerBundle uses php-cs-fixer to enforce coding standards when generating ``.php``
files. When running a ``make`` command, MakerBundle will use a ``php-cs-fixer``
version and configuration that is packaged with this bundle.

You can explicitly set a custom path to a php-cs-fixer binary and/or configuration
file by their respective environment variables:

- ``MAKER_PHP_CS_FIXER_BINARY_PATH`` e.g. tools/vendor/bin/php-cs-fixer
- ``MAKER_PHP_CS_FIXER_CONFIG_PATH`` e.g. .php-cs-fixer.config.php


.. tip::

    Is PHP-CS-Fixer installed globally? To avoid needing to set these in every
    project, you can instead set these on your operating system.


Configuration
-------------

This bundle doesn't require any configuration. But, you *can* override the default
configuration:

.. code-block:: yaml

    # config/packages/maker.yaml
    when@dev:
        maker:
            root_namespace: 'App'
            generate_final_classes: true
            generate_final_entities: false

root_namespace
~~~~~~~~~~~~~~

**type**: ``string`` **default**: ``App``

The root namespace used when generating all of your classes
(e.g. ``App\Entity\Article``, ``App\Command\MyCommand``, etc). Changing
this to ``Acme`` would cause MakerBundle to create new classes like
(e.g. ``Acme\Entity\Article``, ``Acme\Command\MyCommand``, etc).

generate_final_classes
~~~~~~~~~~~~~~~~~~~~~~

**type**: ``boolean`` **default**: ``true``

By default, MakerBundle will generate all of your classes with the
``final`` PHP keyword except for doctrine entities. Set this to ``false``
to override this behavior for all maker commands.

See https://www.php.net/manual/en/language.oop5.final.php

.. code-block:: php

    final class MyVoter
    {
        ...
    }

.. versionadded:: 1.61

    ``generate_final_classes`` was introduced in MakerBundle 1.61


generate_final_entities
~~~~~~~~~~~~~~~~~~~~~~~

**type**: ``boolean`` **default**: ``false``

By default, MakerBundle will not generate any of your doctrine entity
classes with the ``final`` PHP keyword. Set this to ``true``
to override this behavior for all maker commands that create
entities.

See https://www.php.net/manual/en/language.oop5.final.php

.. code-block:: php

    #[ORM\Entity(repositoryClass: TaskRepository::class)]
    final class Task extends AbstractEntity
    {
        ...
    }

.. versionadded:: 1.61

    ``generate_final_entities`` was introduced in MakerBundle 1.61.

Creating your Own Makers
------------------------

In case your applications need to generate custom boilerplate code, you can
create your own ``make:...`` command reusing the tools provided by this bundle.
To do that, you should create a class that extends
`AbstractMaker`_ in your ``src/Maker/``
directory. And this is really it!

For examples of how to complete your new maker command, see the `core maker commands`_.
Make sure your class is registered as a service and tagged with ``maker.command``.
If you're using the standard Symfony ``services.yaml`` configuration, this
will be done automatically.

Overriding the Generated Code
-----------------------------

Generated code can never be perfect for everyone. The MakerBundle tries to balance
adding "extension points" with keeping the library simple so that existing commands
can be improved and new commands can be added.

For that reason, in general, the generated code cannot be modified. In many cases,
adding your *own* maker command is so easy, that we recommend that. However, if there
is some extension point that you'd like, please open an issue so we can discuss!

.. _`AbstractMaker`: https://github.com/symfony/maker-bundle/blob/main/src/Maker/AbstractMaker.php
.. _`core maker commands`: https://github.com/symfony/maker-bundle/tree/main/src/Maker
