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
