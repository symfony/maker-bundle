The Symfony MakerBundle
=======================

The MakerBundle is the fastest way to generate the most common code you'll
need in a Symfony app: commands, controllers, form classes, event subscribers
and more!

Documentation
-------------

[Read the documentation on Symfony.com][1]

Backwards Compatibility Promise
-------------------------------

This bundle shares the [backwards compatibility promise][2] from
Symfony. But, with a few clarifications.

A) The input arguments or options to a command *may* change between
   minor releases. If you're using the commands in an automated,
   scripted way, be aware of this.

B) The generated code itself may change between minor releases. This
   will allow us to continuously improve the generated code!

[1]: https://symfony.com/doc/current/bundles/SymfonyMakerBundle/index.html
[2]: https://symfony.com/doc/current/contributing/code/bc.html

---

Build Documentation Locally
---------------------------

This is not needed for contributing, but it's useful if you would like to debug some
issue in the docs or if you want to read MakerBundles Documentation offline.

```bash
cd _docs_build/

composer install

php build.php
```

After generating docs, serve them with the internal PHP server:

```bash
php -S localhost:8000 -t output/
```

Browse `http://localhost:8000` to read the docs.
