<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/bin')
    ->in(__DIR__ . '/app')
    ->in(__DIR__ . '/docker')
    ->in(__DIR__ . '/tests')
    ->exclude('_temp')
    ->exclude('_generated');

return (new PhpCsFixer\Config())
    ->setCacheFile(__DIR__ . '/temp/.php-cs-fixer.cache')
    ->setRules([
        '@Symfony' => true,
        'yoda_style' => false,
        'phpdoc_separation' => false,
        'phpdoc_to_comment' => [
            'ignored_tags' => ['var'],
        ],
        'global_namespace_import' => [
            'import_classes' => true,
        ],
        'not_operator_with_successor_space' => true,
    ])
    ->setFinder($finder)
    ->setLineEnding("\n");
