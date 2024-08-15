<?php

$header = <<<EOF
This file is part of the "composer-exclude-files" plugin.

© Chauncey McAskill <chauncey@mcaskill.ca>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->exclude('Fixtures');

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0'                         => true,
        'header_comment'                     => [
            'header' => $header,
        ],
        'include'                            => true,
        'no_blank_lines_after_phpdoc'        => true,
        'no_empty_statement'                 => true,
        'no_extra_blank_lines'               => true,
        'no_leading_namespace_whitespace'    => true,
        'no_unused_imports'                  => true,
        'object_operator_without_whitespace' => true,
        'phpdoc_align'                       => true,
        'phpdoc_indent'                      => true,
        'phpdoc_no_access'                   => true,
        'phpdoc_no_package'                  => true,
        'phpdoc_order'                       => true,
        'phpdoc_scalar'                      => true,
        'phpdoc_trim'                        => true,
        'phpdoc_types'                       => true,
        'psr_autoloading'                    => true,
        'standardize_not_equals'             => true,
        'trailing_comma_in_multiline'        => true,
    ])
    ->setFinder($finder);
