# Changelog

## [v1.46.0](https://github.com/symfony/maker-bundle/releases/tag/v1.46.0)

*September 23rd, 2022*

### Feature

- [#1204](https://github.com/symfony/maker-bundle/pull/1204) - [make:crud] use save instead of add repository methods - *@jrushlow*
- [#1202](https://github.com/symfony/maker-bundle/pull/1202) - [reset-password] use higher level "options" in ChangePasswordFormType.tpl.php - *@seb-jean*
- [#1019](https://github.com/symfony/maker-bundle/pull/1019) - Add `make:twig-component` maker - *@kbond*
### Bug

- [#1199](https://github.com/symfony/maker-bundle/pull/1199) - [make:entity] fix compatibility with api-platform 3.0 - *@yobrx*
- [#1176](https://github.com/symfony/maker-bundle/pull/1176) - [make:entity] Fix error while making blob in entity - *@mdoutreluingne*

## [v1.45.0](https://github.com/symfony/maker-bundle/releases/tag/v1.45.0)

*July 26th, 2022*

### Feature

- [#1136](https://github.com/symfony/maker-bundle/pull/1136) - use method add() instead of [] in collection adder-method - *@HKandulla*
### Bug

- [#1154](https://github.com/symfony/maker-bundle/pull/1154) - [make:entity] remove empty parenthesis on `id` - *@jrushlow*
- [#1153](https://github.com/symfony/maker-bundle/pull/1153) - [make:registration-form] Fix escape text-strings - *@mdoutreluingne*

## [v1.44.0](https://github.com/symfony/maker-bundle/releases/tag/v1.44.0)

*July 13th, 2022*

### Feature

- [#1147](https://github.com/symfony/maker-bundle/pull/1147) - [make:entity] Property types, Types:: constant & type guessing - *@weaverryan*
- [#1139](https://github.com/symfony/maker-bundle/pull/1139) - [make:entity] Improve uid support - *@HypeMC*
- [#1129](https://github.com/symfony/maker-bundle/pull/1129) - [tests] bring test suite up to PHP8 standards - *@jrushlow*
- [#1128](https://github.com/symfony/maker-bundle/pull/1128) - improve PHP 8 support w/ rector, removes legacy code, deprecates unused methods - *@jrushlow*
- [#1126](https://github.com/symfony/maker-bundle/pull/1126) - drop annotation support with entities - *@jrushlow*
- [#1125](https://github.com/symfony/maker-bundle/pull/1125) - [csm] strict typing && legacy code removal - *@jrushlow*
- [#1122](https://github.com/symfony/maker-bundle/pull/1122) - drop PHP 7.x support - *@jrushlow*
- [#940](https://github.com/symfony/maker-bundle/pull/940) - [make:subscriber] Improve MakeSubscriber to use KernelEvents constant instead hardcoded event - *@bdaler*

## [v1.43.0](https://github.com/symfony/maker-bundle/releases/tag/v1.43.0)

*May 17th, 2022*

### Feature

- [#1120](https://github.com/symfony/maker-bundle/pull/1120) - [make:controller] Return a JsonResponse instead of a Response with --no-template - *@l-vo*
- [#1117](https://github.com/symfony/maker-bundle/pull/1117) - [make:crud] adding repository counts for crud testRemove  - *@dr-matt-smith*

### Bug

- [#1118](https://github.com/symfony/maker-bundle/pull/1118) - Fix errors when enable_authenticator_manager is not set - *@l-vo*
- [#1042](https://github.com/symfony/maker-bundle/pull/1042) - [CSM] fix: Handle enum as values - *@Geekimo*

## [v1.42.0](https://github.com/symfony/maker-bundle/releases/tag/v1.42.0)

*May 9th, 2022*

### Feature

- [#1114](https://github.com/symfony/maker-bundle/pull/1114) - [make:entity] _em will be private in ORM 3.0, use getEntityManager() - *@jrushlow*
- [#456](https://github.com/symfony/maker-bundle/pull/456) - Use prefix "is" for getters on boolean fields - *@MaximePinot*
- [#307](https://github.com/symfony/maker-bundle/pull/307) - [make:crud][experimental] Add generated tests to make:crud - *@ckrack*

### Bug

- [#1115](https://github.com/symfony/maker-bundle/pull/1115) - [make:crud] fix broken controller with custom repository - *@jrushlow*

## [v1.41.0](https://github.com/symfony/maker-bundle/releases/tag/v1.41.0)

*May 4th, 2022*

### Feature

- [#1110](https://github.com/symfony/maker-bundle/pull/1110) - [make:user] Don't add to passwork_hashers if default recipe is in use - *@nicolas-grekas*
- [#1109](https://github.com/symfony/maker-bundle/pull/1109) - Add missing types in code templates - *@nicolas-grekas*
- [#1107](https://github.com/symfony/maker-bundle/pull/1107) - [make:user] Legacy <= 5.3 & Doctrine Cleanup - *@jrushlow*
- [#1104](https://github.com/symfony/maker-bundle/pull/1104) - [make:auth] drop guard support and legacy code cleanup  - *@jrushlow*
- [#1075](https://github.com/symfony/maker-bundle/pull/1075) - [make:stimulus-controller] New Stimulus controller Maker command - *@JabriAbdelilah*
- [#1028](https://github.com/symfony/maker-bundle/pull/1028) - Add typed properties for make:reset-password and make:registration - *@seb-jean*
- [#872](https://github.com/symfony/maker-bundle/pull/872) - Use object typehint when generating entities - *@HypeMC*
- [#858](https://github.com/symfony/maker-bundle/pull/858) - [make:controller] avoid require doctrine/annotation when can use attributes - *@Jibbarth*

### Bug

- [#1108](https://github.com/symfony/maker-bundle/pull/1108) - [make:test] Removal of the condition requiring a php version < 8.1 - *@mdoutreluingne*
- [#1087](https://github.com/symfony/maker-bundle/pull/1087) - change signature method add/remove repository - *@JB-oclock*
- [#1054](https://github.com/symfony/maker-bundle/pull/1054) - make:entity Use the namespace instead of the full class name for MappingDriver - *@michaelphillpotts*
- [#903](https://github.com/symfony/maker-bundle/pull/903) - [make:auth] use userIdentifier instead of username on login_form - *@seb-jean*

## [v1.40.1](https://github.com/symfony/maker-bundle/releases/tag/v1.40.1)

*April 23rd, 2022*

### Bug

- [#1102](https://github.com/symfony/maker-bundle/pull/1102) - Lower symfony/finder & symfony/yaml requirements - *@bobvandevijver*

## [v1.40.0](https://github.com/symfony/maker-bundle/releases/tag/v1.40.0)

*April 22nd, 2022*

### Feature

- [#1098](https://github.com/symfony/maker-bundle/pull/1098) - drop symfony 4.4 support bump minimum Symfony version to 5.4.7 - *@jrushlow*


## [v1.39.0](https://github.com/symfony/maker-bundle/releases/tag/v1.39.0)

*April 21st, 2022*

### Feature

- [#1088](https://github.com/symfony/maker-bundle/pull/1088) - Add `@extends` tag to Doctrine repository template. - *@hhamon*
- [#1080](https://github.com/symfony/maker-bundle/pull/1080) - [make:twig-extension] reference twig 3.x docs in generated extension - *@BahmanMD*
### Bug

- [#1084](https://github.com/symfony/maker-bundle/pull/1084) - [make:docker:database] Fix link docker compose file ports - *@mdoutreluingne*

## [v1.38.0](https://github.com/symfony/maker-bundle/releases/tag/v1.38.0)

*February 24th, 2022*

### Feature

- [#1076](https://github.com/symfony/maker-bundle/pull/1076) - [make:registration-form] Translate reasons for VerifyEmailBundle if translator available - *@bocharsky-bw*
- [#1015](https://github.com/symfony/maker-bundle/pull/1015) - Update ApiTestCase to be compliant with ApiPlatform v3.0 - *@laryjulien*
- [#1007](https://github.com/symfony/maker-bundle/pull/1007) - [make:controller][make:crud] Make route names start with 'app_' - *@robmeijer*
- [#939](https://github.com/symfony/maker-bundle/pull/939) - [make:crud] Improve controller generation - *@bdaler*

### Bug Fix

- [#1046](https://github.com/symfony/maker-bundle/pull/1046) - [make:entity] Exclude inherited embedded class properties - *@Vincz*
- [#910](https://github.com/symfony/maker-bundle/pull/910) - [YamlSourceManipulator] Tweak regex pattern for regex key - *@lubo13*
- [#830](https://github.com/symfony/maker-bundle/pull/830) - [make:validator] Fix @var typehint comments - *@mmarton*

## [v1.37.0](https://github.com/symfony/maker-bundle/releases/tag/v1.37.0)

*February 16th, 2022*

### Feature

- [#1062](https://github.com/symfony/maker-bundle/pull/1062) - [MakeRegistration] add support for verify email attributes - *@jrushlow*
- [#1059](https://github.com/symfony/maker-bundle/pull/1059) - [make:reset-password] Translate exception reasons provided by ResetPasswordBundle - *@bocharsky-bw*
- [#1057](https://github.com/symfony/maker-bundle/pull/1057) - [Voter] Refactor attributes - *@mdoutreluingne*
- [#1040](https://github.com/symfony/maker-bundle/pull/1040) - [make:entity] Changing getter PHPDoc return type on Collection - *@mehdibo*

### Bug Fix

- [#1060](https://github.com/symfony/maker-bundle/pull/1060) - Add missing Passport use statement - *@bocharsky-bw*
- [#1032](https://github.com/symfony/maker-bundle/pull/1032) - [reset-password] Coding standards - Twig - *@seb-jean*
- [#1031](https://github.com/symfony/maker-bundle/pull/1031) - [verify-email] Coding standards - Twig - *@seb-jean*
- [#1027](https://github.com/symfony/maker-bundle/pull/1027) - Fixing wrong messaging in make:auth about checking password in final steps - *@weaverryan*
- [#985](https://github.com/symfony/maker-bundle/pull/985) - [make:auth] fix security controller attributes - *@jrushlow*

## [v1.36.4](https://github.com/symfony/maker-bundle/releases/tag/v1.36.4)

*November 30th, 2021*

### Bug Fix

- [#1023](https://github.com/symfony/maker-bundle/pull/1023) - Allow deprecation-contracts 3 - *@derrabus*
- [#1026](https://github.com/symfony/maker-bundle/pull/1026) - preventing Guard auth method from exploding in 6.0 - *@weaverryan*

## [v1.36.3](https://github.com/symfony/maker-bundle/releases/tag/v1.36.3)

*November 22nd, 2021*

### Bug Fix

- [#1017](https://github.com/symfony/maker-bundle/pull/1017) - [reset-password] fix missing entity manager di - *@jrushlow*

## [v1.36.2](https://github.com/symfony/maker-bundle/releases/tag/v1.36.2)

*November 22nd, 2021*

### Bug Fix

- [#1016](https://github.com/symfony/maker-bundle/pull/1016) - Fix PHP 8.1 deprecations - *@derrabus*

## [v1.36.1](https://github.com/symfony/maker-bundle/releases/tag/v1.36.1)

*November 16th, 2021*

### Bug Fix

- [#1014](https://github.com/symfony/maker-bundle/pull/1014) - hiding php8 file so it doesn't throw autoloading warning - *@weaverryan*

## [v1.36.0](https://github.com/symfony/maker-bundle/releases/tag/v1.36.0)

*November 16th, 2021*

### Feature

- [#1010](https://github.com/symfony/maker-bundle/pull/1010) - Raising minimum Symfony version to 4.4 & refactoring of internal test classes - *@weaverryan*

### Bug Fix

- [#1010](https://github.com/symfony/maker-bundle/pull/1010) - Various fixes for deprecated code that was generated & fixes for Symfony 6 - *@weaverryan*
- [#993](https://github.com/symfony/maker-bundle/pull/993) - Avoid iterating on null for DoctrineBundle 2.2 and lower - *@weaverryan*
- [#1004](https://github.com/symfony/maker-bundle/pull/1004) - Fix FQCN of 'security.authentication.success' event - *@AlexBevilacqua*

## [v1.35.0](https://github.com/symfony/maker-bundle/releases/tag/v1.35.0)

*November 12th, 2021*

### Feature

- [#1006](https://github.com/symfony/maker-bundle/pull/1006) - Allowing Symfony 6 - *@tacman*

### Bug Fix

- [#992](https://github.com/symfony/maker-bundle/pull/992) - Renaming variable $userPasswordHasherInterface -> $userPasswordHasher - *@weaverryan*

## [v1.34.1](https://github.com/symfony/maker-bundle/releases/tag/v1.34.1)

*October 17th, 2021*

### Bug Fix

- [#991](https://github.com/symfony/maker-bundle/pull/991) - Check if json_array type exists before unsetting it - *@HypeMC*
- [#988](https://github.com/symfony/maker-bundle/pull/988) - Fixed typo in Security52EmptyAuthenticator - *@lyrixx*

## [v1.34.0](https://github.com/symfony/maker-bundle/releases/tag/v1.34.0)

*September 27th, 2021*

### Feature

- [#978](https://github.com/symfony/maker-bundle/pull/978) - Adding Entity attribute support - *@simonmarx*, *@geekimo*, *@adlpz*, *@weaverryan*, *@jrushlow*
- [#970](https://github.com/symfony/maker-bundle/pull/970) - make PhpCompatUtil::getPhpVersion() public - *@nikophil*
- [#968](https://github.com/symfony/maker-bundle/pull/968) - [make:entity] APIP: use new attribute if exists - *@nikophil*
- [#963](https://github.com/symfony/maker-bundle/pull/963) - add return types for symfony 6 - *@jrushlow*
- [#925](https://github.com/symfony/maker-bundle/pull/925) - [templates] Add void return types - *@seb-jean*
- [#923](https://github.com/symfony/maker-bundle/pull/923) - use password hasher for make:registration & make:reset-password, includes other improvements - *@jrushlow*

### Bug Fix

- [#974](https://github.com/symfony/maker-bundle/pull/974) - Fix method call definition - *@ajgarlag*
- [#973](https://github.com/symfony/maker-bundle/pull/973) - Fix we we typo - *@karser*
- [#933](https://github.com/symfony/maker-bundle/pull/933) - [make:entity] Remove deprecated json_array type from available list types. - *@bdaler*
- [#930](https://github.com/symfony/maker-bundle/pull/930) - Add all missed dependencies to make:reset-password - *@upyx*
- [#870](https://github.com/symfony/maker-bundle/pull/870) - [make:crud] Fix templates path use in include - *@leblanc-simon*

## [v1.33.0](https://github.com/symfony/maker-bundle/releases/tag/v1.33.0)

*June 30th, 2021*

### Feature

- [#895](https://github.com/symfony/maker-bundle/pull/895) - [make:crud] send the proper HTTP status codes and use renderForm() when available - *@dunglas*
- [#889](https://github.com/symfony/maker-bundle/pull/889) - [make:user] Use password_hashers instead of encoders - *@wouterj*

### Bug Fix

- [#913](https://github.com/symfony/maker-bundle/pull/913) - [make:registration] conditionally generate verify email flash in template - *@jrushlow*
- [#881](https://github.com/symfony/maker-bundle/pull/881) - [make:entity] Fix error when API-Platform is installed. - *@MichaelBrauner*

## [v1.32.0](https://github.com/symfony/maker-bundle/releases/tag/v1.32.0)

*June 18th, 2021*

### Feature

- [#877](https://github.com/symfony/maker-bundle/pull/877) - [make:entity] Default to "datetime_immutable" when creating entities - *@nicolas-grekas*

### Bug Fix

- [#899](https://github.com/symfony/maker-bundle/pull/899) - Use proper name for parameter of upgradePassword - *@Tobion*
- [#896](https://github.com/symfony/maker-bundle/pull/896) - Fix keys not found when surrounded by quotes - *@valentinloiseau*
- [#890](https://github.com/symfony/maker-bundle/pull/890) - [make:user] Keep implementing deprecated username methods - *@wouterj*

## [v1.31.1](https://github.com/symfony/maker-bundle/releases/tag/v1.31.1)

*May 12th, 2021*

### Security

- [security](https://github.com/symfony/maker-bundle/releases/tag/v1.31.1) - CVE-2021-21424 Prevent user enumeration - *@chalasr*

## [v1.31.0](https://github.com/symfony/maker-bundle/releases/tag/v1.31.0)

*May 5th, 2021*

### Feature

- [#864](https://github.com/symfony/maker-bundle/pull/864) - [make:command] template: add void return type to configure method - *@duboiss*
- [#862](https://github.com/symfony/maker-bundle/pull/862) - [make:user] implement getUserIdentifier if required - *@jrushlow*
- [#860](https://github.com/symfony/maker-bundle/pull/860) - Add support for Symfony UX Turbo - *@dunglas*
- [#859](https://github.com/symfony/maker-bundle/pull/859) - use attributes for API Platform when using PHP 8+ - *@dunglas*
- [#855](https://github.com/symfony/maker-bundle/pull/855) - [reset-password] allow anyone to access check email - *@jrushlow*
- [#853](https://github.com/symfony/maker-bundle/pull/853) - [make:voter] generate type hints - *@jrushlow*
- [#849](https://github.com/symfony/maker-bundle/pull/849) - [make:user] user entities implement PasswordAuthenticatedUserInterface - *@jrushlow*
- [#826](https://github.com/symfony/maker-bundle/pull/826) - Add autocomplete html tag to forms - *@duboiss*
- [#822](https://github.com/symfony/maker-bundle/pull/822) - [make:command] lets use attributes if possible - *@jrushlow*

### Bug Fix

- [#869](https://github.com/symfony/maker-bundle/pull/869) - [make:serializer:encoder] set public constant visibility modifier - *@jrushlow*
- [#818](https://github.com/symfony/maker-bundle/pull/818) - [MakeDocker] add support for .yml docker-compose files - *@jrushlow*

1.30
====

* [make:crud] Ask a new question - controller name - to allow that to
  be customized - #840 thanks to @weaverryan

* [make:crud] Make the delete form submit via a normal POST request
  instead of delete - #825 thanks to @jrushlow

* Dropped support for Symfony 3 - #819 thanks to @jrushlow

1.29
====

* [make:test] Added a new command that interactively asks you between
  several different styles of test classes. See #807 thanks to @dunglas.
* [make:unit-test] Deprecated the maker in favor of `make:test`.
* [make:functional-test] Deprecated the maker

1.28
====

* Sort entity auto-completion in various commands - thanks to @zorn-v

1.27
====

* [make:registration-form] Added a new question to generate code that will allow
  users to click on the "verify email" link in their email without needing to be
  authenticated - #776 thanks to @jrushlow!

1.26
====

* [make:auth] Added support to make:auth for the new "authenticator" security mode in
  Symfony 5.2 - #736 thanks to @jrushlow!

1.25
====

* Add support for doctrine/inflector v2 (v1 is still allowed) - #758 thanks to @jrushlow!
* [make:entity] Fixed setting a null value for OneToMany - #755 thanks to @Kocal!

1.24
====

* Use PHP 8 Route attributes when using PHP 8 - #725 thanks to @jrushlow!
* Improve version detection by reading config.platform.php - #728 thanks to @jrushlow!

1.23
====

* Added experimental PHP 8 support. The bundle now allows php 8 and all
  maker commands (whose dependencies allow PHP 8) now having passing tests.

1.22
====

* [make:entity] Optimized how the `removeXXXX()` methods are generated
  for relationships - #675 thanks to @mhabibi!

* [make:serializer:normalizer] Generated a better template, trying to
  guess the class you might be normalizing - #672 thanks to @BatsaxIV

1.21
====

* [make:docker:database] When using MySQL, a "main" database is now created
  automatically for you - #656 thanks to @robmeijer!

* [make:voter] Better generated entity "guess" - #658 thanks to @yahyaerturan!

* [make:command] Use the new Command::SUCCESS when available - #664
  thanks to @Chi-teck!


1.20
====

* [make:docker:database] Added a new command to generate a database service
  in your `docker-compose.yaml` file - #640 thanks to @jrushlow!

1.19
====

* Added "email verification/confirmation" option to `make:registration-form` - see #603
  thanks to @jrushlow!

1.18
====

* Reverted support for `doctrine/inflector` 2.0 - #611 thanks to @weaverryan

1.17
====

* PHP 7.1 is now the required minimum version - #598 thanks to @weaverryan

* MakerBundle now allows `doctrine/inflector` 2.0 - #600 thanks to @alcaeus

1.16
====

* [make:entity] Generated entities will now use the RelationName::class
  syntax when generating relationships (e.g.
  `targetEntity=RelationName::class`) - #573 thanks to @rogeriolino.

* When listing generated files in the console, if a file link formatter
  is configured, the links will now be clickable - #559 thanks to @l-vo.

* [make:entity] Added UUID and GUID default type to entity maker - if you name a
  field `uuid` or `guid`, the Maker will guess those types by default - #593
  thanks to @thomas-miceli.

1.15
====

* [make:reset-password] New `make:reset-password` to generate an
  entire "reset password" controller, forms, template setup - #567
  thanks to @jrushlow and @romaricdrigon

* [make:message] New `make:message` command to generate a
  Messenger messaage & handler class - #338 thanks to @nikophil

* [make:messenger-middleware] New `make:messenger-middleware`
  command to generate a middleware for Messenger

1.14
====

* Added support for Symfony 5

1.13
====

* [make:functional-test] Use Panther when available - #417
  thanks to @adrienlucas

* Allow rehashing passwords when possible and needed - #389
  thanks to @nicolas-grekas

1.12
====

* Use `[make:*-test]` Use the new WebTestAssertionsTrait methods in the generated
  functional tests - #381 thanks to @adrienlucas

* Add a agree terms checkbox to `make:registration-form` - #394
  thanks to @ismail1432

* Template generation respects `twig.default_path` - #346
  thanks to @LeJeanbono

* [Serializer] Normalizer now implements CacheableSupportsMethodInterface
  with condition - #399 thanks to @jojotjebaby

* Deprecate Argon2i encoder used in `make:user` and use
  `auto` instead - #398 thanks to @nicolas-grekas

* [make:auth] Added logout support and help for logged in user -
  in #406 thanks to @St0iK

* Use new event class names instead of strings in `make:event-subscriber` -
  in #403 thanks to @jojotjebaby

1.11
====

* Add `make:registration-form` command - #333 thanks to @weaverryan

1.10
====

* Add `make:serializer:normalizer` command - #298 thanks
    to @lyrixx

* Add a `--no-template` option to `make:controller` to skip
    generating a template - #280 thanks to @welcoMattic

* Add support for rendering additional date types in make:crud
    - #241 thanks to @sadikoff

* Better errors when trying to use reserved words for classes
    - #306 thanks to @SerkanYildiz

1.9
===

* Allow make:form to work with non-entities - #301 thanks to @ckrack

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
