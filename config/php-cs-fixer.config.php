<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * This PHP-CS-Fixer config file is used by the TemplateLinter for userland
 * code when say make:controller is run. If a user does not have a php-cs-fixer
 * config file, this one is used on the generated PHP files.
 *
 * It should not be confused by the root level .php-cs-fixer.dist.php config
 * which is used to maintain the MakerBundle codebase itself.
 */
return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'native_function_invocation' => false,
        'blank_line_before_statement' => ['statements' => ['break', 'case', 'continue', 'declare', 'default', 'do', 'exit', 'for', 'foreach', 'goto', 'if', 'include', 'include_once', 'phpdoc', 'require', 'require_once', 'return', 'switch', 'throw', 'try', 'while', 'yield', 'yield_from']],
        'array_indentation' => true,
    ])
    ->setRiskyAllowed(true)
;
