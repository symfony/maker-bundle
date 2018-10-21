1.8
===

* make:auth can now generate an entire form login system with
  authenticator, login form and controller! #266 thanks to @nikophil

* make:auth now registers your guard authenticator in your security.yaml
  file automatically - #261 thanks to @nikophil

* Generate more explicit voter attribute names - #283 thanks to @lyrixx

* Fixing incorrect dependency for make:crud - #256 thanks to @ckrack

* Fix self-referencing relation issue - #278 thanks to @codedmonkey

* Fix edge-case bad template name in make:crud - #286 thanks
  to @thlbaut

1.7
===

* Added `make:user` command that generates a `User` class, generates
  a user provider (when needed) and configures your the `providers`
  and `encoders` section in your `security.yaml` file - #250 thanks
  to @weaverryan

* Properly extend `AbstractController` and use core `@Route` annotation
  in `make:crud` - #246 thanks to @royklutman 

* Fixed a bug when a class name contained the namespace `App\` - #243
  thanks to @gmanen

* Fix bug with `make:entity` when generating inside a sub-directory - #245
  thanks to @nikophil

1.6
===

* Fixing bad empty line when source file uses tabs - #238 thanks to @weaverryan

* Str case mismatch - #190 thanks to @localheinz

* Preserve existing class metadata - #197 thanks to @ro0NL

* Fixing a bug where having relativizePath failed - #214 thanks to @weaverryan

* Do not prefix Command Class Name by 'App' if the prefix is app: - #205 thanks to @lyrixx

* make:entity: Add return type to getId() - #215 thanks to @gharlan

* Don't make Twig filters safe for HTML by default - #222, #202 thanks to @lyrixx

* Remove support for the deprecated json_array Doctrine type - #224 thanks to @javiereguiluz

* Extend from AbstractController when using Symfony 4.1 or higher - #221 thanks to @javiereguiluz

* Don't use :contains in the functional test tpl - #226 thanks to @dunglas

1.5
===

* Before 1.5, the `App\` namespace prefix was always assumed so that
  when you type a short class name, it is converted into a full class
  name with this prefix. Now, this is configurable #173 thanks to @upyx

* Added an option to to `make:enity` to make your class automatically
  an ApiPlatform resource. Pass `--api-resource` #178 thanks to @dunglas

* Fixed `make:entity` when your class uses traits or a mapped
  super class #181 thanks to @andrewtch

* Improved messages when you need to pass a fully-qualified class
  name #188 & #171 - thanks to @sadikoff and @LeJeanbono

* Fixed a bug where `make:crud` would not render the correct form
  names when your property included an underscore.

1.4
===

* Removed our tests from the archive to avoid polluting the
  user's auto-completion of classes #147

* Fixed some minor bugs! #150 #145

1.3
===

* Drastically improved `make:entity` command, which now supports
  adding fields, relationships, updating existing entities, and
  generating (with the `--regenerate` flag) missing
  properties/getters/setters (effectively replaces `doctrine:generate:entities`)
  - thanks to @weaverryan in #104

1.2
===

* New maker command! `make:crud` - thanks to @sadikoff in #113.

* Greatly improved `make:form` command that auto-adds fields if
  your form is bound to an entity class - thanks to @sadikoff in #113.

1.1
===

* [BC BREAK] The MakerInterface changed: `getParameters()`, `getFiles()`
  and `writeNextStepsMessage()` were removed and `generate()` was added
  in their place. We recommend extending `AbstractMaker` instead of implementing
  the interface directly, and use `$this->writeSuccessMessage()` to get
  the normal "success" message after the command #120 via @weaverryan

* Added new `make:migration` command, which wraps the normal
  `doctrine:migrations:diff` command #97 via @weaverryan

* Added new `make:fixtures` command to generate an empty fixtures class
  #105 via @javiereguiluz

* Added PHPDoc to generated entity repositories so that your IDE knows
  what type of objects are returned #116 @enleur

* Allowed generation of all classes into sub-namespaces #120 via @weaverryan
