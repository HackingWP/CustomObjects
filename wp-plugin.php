<?php

/**
 * Plugin Name:     Hacking Wordpress Custom Objects
 * Plugin URI:      http://github.com/HackingWP/CustomObjects
 * Description:     Abstract classes (prototypes) for creating custom post types and taxonomies and registerign WP API automatically
 * Version:         v0.1.0
 * Author:          Martin Adamko
 * Author URI:      http://twitter.com/martin_adamko
 * License:         MIT (Codeigniter Inflector Helper licenced under OSL 3.0)
 */

if (strnatcmp(phpversion(),'5.4.0') >= 0)
{
    // Trait
    require_once dirname(__FILE__).'/Common.php';

    // require_once custom post type Prototype class
    require_once dirname(__FILE__).'/PostTypePrototype.php';

    // require_once custom taxonomy Prototype class
    require_once dirname(__FILE__).'/TaxonomyPrototype.php';

    // require_once JSON API Prototype class for custom taxonomy
    add_action('wp_json_server_before_serve', function() {
        require_once dirname(__FILE__).'/TaxonomyJSON.php';
        require_once dirname(__FILE__).'/PostTypeJSON.php';
    });
} else {
    trigger_error('Requires PHP 5.4 or higher.', E_USER_ERROR);
}
