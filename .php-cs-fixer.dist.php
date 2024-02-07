<?php

if (!file_exists(__DIR__.'/src')) {
    exit(0);
}

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('vendor')
    ->exclude('tests/tmp')
    ->exclude('fixtures')
    // the PHP template files are a bit special
    ->notName('*.tpl.php')
;

return (new PhpCsFixer\Config())
    ->setRules(array(
        '@PHP80Migration' => true,
        '@PHPUnit84Migration:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'header_comment' => [
            'header' => <<<EOF
This file is part of the Symfony MakerBundle package.

(c) Fabien Potencier <fabien@symfony.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF
        ],
        'protected_to_private' => false,
        'semicolon_after_instruction' => false,
        'trailing_comma_in_multiline' => [
            'elements' => [
                'arrays',
                'parameters'
            ],
        ],
    ))
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
