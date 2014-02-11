<?php





/**
 * Helper function that returns an array containing the post types that are
 * supported by CC-Configurator. These include:
 *
 *   - post
 *   - page
 *   - attachment
 *
 * And also to ALL public custom post types which have a UI.
 *
 */
function bccl_get_supported_post_types() {
    $supported_builtin_types = array('post', 'page', 'attachment');
    $public_custom_types = get_post_types( array('public'=>true, '_builtin'=>false, 'show_ui'=>true) );
    $supported_types = array_merge($supported_builtin_types, $public_custom_types);

    // Allow filtering of the supported content types.
    $supported_types = apply_filters( 'bccl_supported_post_types', $supported_types );

    return $supported_types;
}



/**
 * Helper function that returns an array containing the post types
 * on which the Metadata metabox should be added.
 *
 *   - post
 *   - page
 *
 * And also to ALL public custom post types which have a UI.
 *
 * NOTE ABOUT attachments:
 * The 'attachment' post type does not support saving custom fields like other post types.
 * See: http://www.codetrax.org/issues/875
 */
function bccl_get_post_types_for_metabox() {
    // Get the post types supported by Creative-Commons-Configurator
    $supported_builtin_types = bccl_get_supported_post_types();
    // The 'attachment' post type does not support saving custom fields like
    // other post types. See: http://www.codetrax.org/issues/875
    // So, the 'attachment' type is removed (if exists) so as not to add a metabox there.
    $attachment_post_type_key = array_search( 'attachment', $supported_builtin_types );
    if ( $attachment_post_type_key !== false ) {
        // Remove this type from the array
        unset( $supported_builtin_types[ $attachment_post_type_key ] );
    }
    // Get public post types
    $public_custom_types = get_post_types( array('public'=>true, '_builtin'=>false, 'show_ui'=>true) );
    $supported_types = array_merge($supported_builtin_types, $public_custom_types);

    // Allow filtering of the supported content types.
    $supported_types = apply_filters( 'bccl_metabox_post_types', $supported_types );     // Leave this filter out of the documentation for now.

    return $supported_types;
}




function bccl_get_creator_pool() {
    $creator_arr = array(
        "blogname"    => __('Blog Name', 'cc-configurator'),
        "firstlast"    => __('First + Last Name', 'cc-configurator'),
        "lastfirst"    => __('Last + First Name', 'cc-configurator'),
        "nickname"    => __('Nickname', 'cc-configurator'),
        "displayedname"    => __('Displayed Name', 'cc-configurator'),
        );
    return $creator_arr;
}



/**
 * Return the creator/publisher of the licensed work according to the user-defined option (cc-creator)
 */
function bccl_get_the_creator($what) {

    $author_name = '';
    if ($what == "blogname") {
        $author_name = get_bloginfo("name");
    } elseif ($what == "firstlast") {
        $author_name = get_the_author_meta('first_name') . " " . get_the_author_meta('last_name');
    } elseif ($what == "lastfirst") {
        $author_name = get_the_author_meta('last_name') . " " . get_the_author_meta('first_name');
    } elseif ($what == "nickname") {
        $author_name = get_the_author_meta('nickname');
    } elseif ($what == "displayedname") {
        $author_name = get_the_author_meta('display_name');
    } else {
        $author_name = get_the_author_meta('display_name');
    }
    // If we do not have an author name, revert to the display name.
    if ( trim($author_name) == '' ) {
        return get_the_author();
    }
    return $author_name;
}


function bccl_add_placeholders($data, $what = "html") {
    if (!(trim($data))) { return ""; }
    if ($what = "html") {
        return sprintf( PHP_EOL . "<!-- Creative Commons License -->" . PHP_EOL . "%s" . PHP_EOL . "<!-- /Creative Commons License -->" . PHP_EOL , trim($data) );
    } else {
        return sprintf( PHP_EOL . "<!--" . PHP_EOL . "%s" . PHP_EOL . "-->" . PHP_EOL, trim($data) );
    }
}


