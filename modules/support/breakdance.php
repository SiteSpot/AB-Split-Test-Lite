<?php
if ( ! defined( 'ABSPATH' ) ) exit;


//breakdance conversion optimization!

use function Breakdance\Elements\control;
use function Breakdance\Elements\controlSection;

add_filter('breakdance_element_controls', 'abst_bd', 169, 2);

/**
 * @param Control[] $controls
 * @param \Breakdance\Elements\Element $element
 * @return Control[]
 */
function abst_bd($controls, $element)
{

    $controls['settingsSections'][] = controlSection(
        'abtest',
        'Split Test',
        [
            control('test_name', 'Split Test',         
                ['type' => 'post_chooser', 
                'layout' => 'inline', 
                'postChooserOptions' => ['multiple' => false, 'showThumbnails' => false, 'postType' => 'bt_experiments']],
            ),
            control('test_var_name', 'Variation Name',
                ['type' => 'text',
                'layout' => 'vertical'],
            ),
            // Opens the create-test pop-up (js/builderhelper.js); the new test can then be
            // picked in Split Test above.
            control('test_create', 'New Test',
                ['type' => 'alert_box',
                'layout' => 'vertical',
                'alertBoxOptions' => [
                    'style' => 'info',
                    'content' => '<a class="new-on-page-test-button" href="javascript:void(0);" style="display:inline-block;padding:6px 12px;border-radius:3px;background:#7046db;color:#fff;font-size:12px;font-weight:600;line-height:1.4;text-decoration:none;">+ ' . esc_html__( 'Create a new test', 'ab-split-test-lite' ) . '</a>',
                ]],
            ),
        ],
        ['isExternal' => true]
    );

    return $controls;
}





add_filter('breakdance_element_attributes', 'abst_bd_attributes', 100, 1);

/**
 * @param  ElementAttribute[] $attributes
 *
 * @return array
 */
function abst_bd_attributes($attributes)
{
    $attributes[] = [
        "name" => "bt-eid",
        "propertyPath" => "settings.abtest.test_name",
    ];
    $attributes[] = [
        "name" => "bt-variation",
        "propertyPath" => "settings.abtest.test_var_name",
    ];

    return $attributes;
}

