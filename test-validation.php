<?php
/**
 * Test file for validation logic
 * This file can be used to test the validation functionality
 */

// Include the main function file
require_once 'function.php';

// Test cases
$test_cases = [
    'form_without_css_class' => [
        'cssClass' => 'other-class',
        'confirmations' => [
            'default' => [
                'type' => 'page',
                'pageId' => 123
            ]
        ]
    ],
    'form_with_css_class_and_text_confirmation' => [
        'cssClass' => 'gf_confirmation_popup other-class',
        'confirmations' => [
            'default' => [
                'type' => 'message',
                'message' => 'Thank you for your submission!'
            ]
        ]
    ],
    'form_with_css_class_and_page_confirmation' => [
        'cssClass' => 'gf_confirmation_popup',
        'confirmations' => [
            'default' => [
                'type' => 'page',
                'pageId' => 123
            ]
        ]
    ],
    'form_with_css_class_and_redirect_confirmation' => [
        'cssClass' => 'gf_confirmation_popup',
        'confirmations' => [
            'default' => [
                'type' => 'redirect',
                'url' => 'https://example.com'
            ]
        ]
    ],
    'form_with_css_class_and_conditional_page_confirmation' => [
        'cssClass' => 'gf_confirmation_popup',
        'confirmations' => [
            'default' => [
                'type' => 'message',
                'message' => 'Default message'
            ],
            'conditional' => [
                'type' => 'page',
                'pageId' => 123,
                'conditionalLogic' => [
                    'actionType' => 'show',
                    'logicType' => 'all',
                    'rules' => [
                        [
                            'fieldId' => 1,
                            'operator' => 'is',
                            'value' => 'yes'
                        ]
                    ]
                ]
            ]
        ]
    ],
    'form_with_css_class_and_empty_conditional_page_confirmation' => [
        'cssClass' => 'gf_confirmation_popup',
        'confirmations' => [
            'default' => [
                'type' => 'message',
                'message' => 'Default message'
            ],
            'conditional' => [
                'type' => 'page',
                'pageId' => 123,
                'conditionalLogic' => [
                    'actionType' => 'show',
                    'logicType' => 'all',
                    'rules' => []
                ]
            ]
        ]
    ]
];

$has_problematic = gf_popup_confirmations_has_problematic_confirmations($test_cases);
$status = $has_problematic ? 'PROBLEMATIC' : 'OK';

// All echo statements and debug output removed

// The original code had echo statements for debugging.
// Since the instructions are to remove all echo statements,
// the code will now be silent unless used in production logic.
// The variable $status is still calculated, but its output is removed.
// The function call $has_problematic is still executed,
// but its result is not used for any output.
// The original code had a final echo statement.
// Since all echo statements are removed, this final echo is also removed.
// The file will now be silent. 