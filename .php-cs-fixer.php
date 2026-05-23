<?php

$finder = PhpCsFixer\Finder::create()
    ->in(array(
        __DIR__ . '/config',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ))
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules(array(
        'blank_line_after_opening_tag' => true,
        'full_opening_tag' => true,
        'no_trailing_whitespace' => true,
        'single_blank_line_at_eof' => true,
    ))
    ->setFinder($finder);
