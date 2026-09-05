<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('.git')
    ->exclude('vendor');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,

        'array_syntax' => [
            'syntax' => 'short',
        ],

        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],

        'blank_line_after_opening_tag' => true,

        'braces_position' => [
            'classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
            'functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
            'control_structures_opening_brace' => 'same_line',
        ],

        'concat_space' => [
            'spacing' => 'one',
        ],

        'method_chaining_indentation' => true,

        'no_multiple_statements_per_line' => true,

        'single_quote' => true,

        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
    ])
    ->setFinder($finder);
