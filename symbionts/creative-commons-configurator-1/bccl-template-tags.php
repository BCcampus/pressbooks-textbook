<?php

/*
$work: The work that is licensed can be defined by the user.
$css_class : The user can define the CSS class that will be used to
$show_button: (default, yes, no)
format the license block. (if empty, the default cc-block is used)
*/
function bccl_license_block($work = "", $css_class = "", $show_button = "default", $button = "default") {
    echo bccl_add_placeholders(bccl_get_license_block($work, $css_class, $show_button, $button));
}


/*
Displays the full HTML code of the license
*/  
function bccl_full_html_license($button = "default") {
    echo bccl_add_placeholders(bccl_get_full_html_license($button));
}


/*
Displays the licence summary page from creative commons in an iframe
*/
function bccl_license_legalcode($width = "100%", $height = "600px", $css_class= "cc-frame") {
    printf('
        <iframe src="%slegalcode" frameborder="0" width="%s" height="%s" class="%s"></iframe>
        ', bccl_get_license_url(), $width, $height, $css_class);
}


/*
Displays the licence summary page from creative commons in an iframe
*/
function bccl_license_summary($width = "100%", $height = "600px", $css_class= "cc-frame") {
    printf('
        <iframe src="%s" frameborder="0" width="%s" height="%s" class="%s"></iframe>
        ', bccl_get_license_url(), $width, $height, $css_class);
}


/*
Displays Full IMAGE hyperlink to License <a href=...><img...</a>
*/
function bccl_license_image_hyperlink($button = "default") {
    echo bccl_add_placeholders(bccl_get_license_image_hyperlink($button));
}


/*
Displays Full TEXT hyperlink to License <a href=...>...</a>
*/
function bccl_license_text_hyperlink() {
    echo bccl_add_placeholders(bccl_get_license_text_hyperlink());
}


