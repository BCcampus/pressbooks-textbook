<?php


/**
 * Licenses Database
 *
'slug'   =>  array(         // slug (unique for each license)
        'url' => '',    // URL to license page
        'name' => '',   // Name of the license
        'button_url' => '', // URL to license button
        'button_compact_url' => '', // URL to a small license button
        'deed_url' => '',        // URL to license deed
        'text' => '',
        'extended_text' => '',
        'additional_perms_text' => ''
    ),
 *
 */
function bccl_get_license( $slug ) {

    $licenses = array(
        'arr'   =>  array(         // slug (unique for each license)
            'url' => '',    // URL to license page
            'name' => '',   // Name of the license
            'button_url' => '', // URL to license button
            'button_compact_url' => '', // URL to a small license button
            'deed_url' => '',        // URL to license deed
            'text' => __('Copyright &copy; %s - All Rights Reserved', 'cc-configurator'),  // expects args: 1:year
            'extended_text' => '',
            'additional_perms_text' => '<br />'.__('Information about licensing this work may be available at', 'cc-configurator')
        ),
        'cc0'   =>  array(         // slug (unique for each license)
            'url' => 'http://creativecommons.org/publicdomain/zero/1.0/',    // URL to license page
            'name' => 'CC0',   // Name of the license
            'button_url' => 'http://i.creativecommons.org/p/zero/1.0/88x31.png', // URL to license button
            'button_compact_url' => 'http://i.creativecommons.org/p/zero/1.0/80x15.png', // URL to a small license button
            'deed_url' => 'http://creativecommons.org/publicdomain/zero/1.0/',        // URL to license deed
            'text' => __('To the extent possible under law, the creator has waived all copyright and related or neighboring rights to this work.', 'cc-configurator'), // no args
            'extended_text' => __('To the extent possible under law, %s has waived all copyright and related or neighboring rights to %s.', 'cc-configurator'), // expects args: 1:creator link 2:work link
            'additional_perms_text' => ''
        ),
        //  Find way to add this:  This work is published from: Greece.

    );

    return $licenses[$slug];
}


