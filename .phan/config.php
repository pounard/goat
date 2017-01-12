<?php
return [
    'allow_missing_properties' => false,
    'backward_compatibility_checks' => false,
    'quick_mode' => true,
    'dead_code_detection' => true,
    'generic_types_enabled' => true,
    // you can't set the following to true, because Phan shoots itself in the
    // foot and is not able to distinguish what's written in PHP doc and PHP7
    // strict typing, which forces you to NOT set the type whenever you want
    // to do polymorphism
    'analyze_signature_compatibility' => false,
    'minimum_severity' => \Phan\Issue::SEVERITY_LOW,
    'allow_missing_properties' => false,
    'null_casts_as_any_type' => true,
    'scalar_implicit_cast' => false,
    'ignore_undeclared_variables_in_global_scope' => false,
    'suppress_issue_types' => [],
    'whitelist_issue_types' => [],
    'processes' => 5,
    'exclude_analysis_directory_list' => [
        'cache',
        'compat',
        'Tests',
        'vendor',
    ],
];

