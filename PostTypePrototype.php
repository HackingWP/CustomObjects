<?php

namespace HackingWP\CustomObjects;

/**
 * Custom Taxonomy Registrator Class
 *
 * @see http://codex.wordpress.org/Function_Reference/register_post_type
 *
 * Usage: Extend class and redeclare functions as needed. Initiallize by
 * creating a new object.
 *
 */
abstract class PostTypePrototype
{
    use Common;

    protected $post_type;
    protected $post_type_plural;
    protected $domain;

    protected $json_api;
    protected $json_api_taxonomies = array();

    const RESERVED = '["post","page","attachment","revision","nav_menu_item","action","order","theme"]';

    public function __construct($post_type = '', $domain = 'default', $post_type_plural = '')
    {
        // Use predefined if possible
        if (empty($post_type) && !empty($this->post_type)) {
            $post_type = $this->post_type;
        }
        if (empty($post_type_plural) && !empty($this->post_type_plural)) {
            $post_type_plural = $this->post_type_plural;
        }
        if (empty($domain) && !empty($this->domain)) {
            $domain = $this->domain;
        }

        // Check values for errors
        if (! $this->valid_string_value($post_type)) {
            throw new \Exception('`$post_type` must be a non-empty string. `'.$post_type.'` provided.');
        }

        if (in_array($post_type, json_decode(self::RESERVED)) && $this->post_type_args__builtin()!==true) {
            throw new \Exception('`$post_type` name is served and not specified as `_builtin`. `'.$post_type.'` provided.');
        }

        if (! $this->valid_string_value($domain)) {
            throw new \Exception('Text `$domain` must be a non-empty string. `'.$domain.'` provided.');
        }

        if (! $this->valid_string_value($post_type_plural) && !empty($post_type_plural)) {
            throw new \Exception('`$post_type_plural` must be a non-empty string or leave it empty to generate it automatically. `'.$post_type_plural.'` provided.');
        }

        $post_type = trim($post_type);
        $domain    = trim($domain);

        // If the post type contains dashes you will not be able to add columns to the cpt admin page (using the 'manage_<CPT Name>_posts_columns' action).
        $post_type_singular = preg_replace('|-+|', '', strtolower($this->singular($post_type)));

        if ($post_type_singular!==$post_type) {
            wp_die('`$post_type` must be singular and all lowercase, without dashes, e.g.: status, link, movie...');
        }

        if (empty($post_type_plural)) {
            $post_type_plural = $this->plural($post_type);
        }

        $this->post_type        = $post_type;
        $this->domain           = $domain;
        $this->post_type_plural = $post_type_plural;

        // Create object but init registration only for new objects
        if ($this->post_type_args__builtin()!==true) {
            add_action('init', array($this, 'register_post_type'));
        }

        return $this;
    }

    protected function do_after_registration() {
        return $this;
    }

    protected function do_before_registration() {
        return $this;
    }

    public function register_post_type()
    {
        $this->do_before_registration();

        $post_type_args = array(
            'label'                => $this->post_type_args_label(),
            'labels'               => $this->post_type_args_labels(),
            'description'          => $this->post_type_args_description(),
            'public'               => $this->post_type_args_public(),
            'exclude_from_search'  => $this->post_type_args_exclude_from_search(),
            'publicly_queryable'   => $this->post_type_args_publicly_queryable(),
            'show_ui'              => $this->post_type_args_show_ui(),
            'show_in_nav_menus'    => $this->post_type_args_show_in_nav_menus(),
            'show_in_menu'         => $this->post_type_args_show_in_menu(),
            'show_in_admin_bar'    => $this->post_type_args_show_in_admin_bar(),
            'menu_position'        => $this->post_type_args_menu_position(),
            'menu_icon'            => $this->post_type_args_menu_icon(),
            'capability_type'      => $this->post_type_args_capability_type(),
            'capabilities'         => $this->post_type_args_capabilities(),
            'map_meta_cap'         => $this->post_type_args_map_meta_cap(),
            'hierarchical'         => $this->post_type_args_hierarchical(),
            'supports'             => $this->post_type_args_supports(),
            'register_meta_box_cb' => $this->post_type_args_register_meta_box_cb(),
            'taxonomies'           => $this->post_type_args_taxonomies(),
            'has_archive'          => $this->post_type_args_has_archive(),
            'permalink_epmask'     => $this->post_type_args_permalink_epmask(),
            'rewrite'              => $this->post_type_args_rewrite(),
            'query_var'            => $this->post_type_args_query_var(),
            'can_export'           => $this->post_type_args_can_export(),
            '_builtin'             => $this->post_type_args__builtin(),
            '_edit_link'           => $this->post_type_args__edit_link(),
        );

        // Create object but init registration only for custom objects
        if ($this->post_type_args__builtin()!==true) {
            register_post_type( $this->post_type, $post_type_args );
        }

        add_action( 'wp_json_server_before_serve', array($this, 'registerAPI'), 10, 1);

        $this->do_after_registration();

        return $this;
    }

    public function registerAPI()
    {
        global $wp_json_server;

        // Register API for custom post type
        $this->json_api['post_type'] = new PostTypeJSON($wp_json_server, $this->post_type);

        // Register API for taxonomies
        $taxonomies = $this->post_type_args_taxonomies();

        if (class_exists(__NAMESPACE__.'\TaxonomyJSON')) {
            if (count($taxonomies)>0) {
                foreach ($taxonomies as $taxonomy) {
                    $this->json_api_taxonomies = new TaxonomyJSON($wp_json_server, $taxonomy, $this->post_type);
                }
            }
        }

        return $this;
    }

    protected function post_type_args_label()
    {
        return ucfirst(preg_replace('|_+|', ' ', $this->label($this->post_type_plural)));
    }

    protected function post_type_args_labels()
    {
        $labels = $this->labels_array($this->post_type);

        return array(
            'name'               => _x($labels['name'], 'post type general name', $this->domain),
            'singular_name'      => _x($labels['singular_name'], 'post type singular name', $this->domain),
            'add_new'            => _x($labels['add_new'], 'Post', $this->domain),
            'add_new_item'       => __($labels['add_new_item'], $this->domain),
            'edit_item'          => __($labels['edit_item'], $this->domain),
            'new_item'           => __($labels['new_item'], $this->domain),
            'all_items'          => __($labels['all_items'], $this->domain),
            'view_item'          => __($labels['view_item'], $this->domain),
            'search_items'       => __($labels['search_items'], $this->domain),
            'not_found'          => __($labels['not_found'], $this->domain),
            'not_found_in_trash' => __($labels['not_found_in_trash'], $this->domain),
            'parent_item_colon'  => __($labels['parent_item_colon'], $this->domain ),
            'menu_name'          => __($labels['menu_name'], $this->domain),
        );
    }

    protected function post_type_args_description()
    {
        return sprintf(__('%s post content type', $this->domain), $this->post_type_args_label());
    }

    protected function post_type_args_public() { return false; }

    protected function post_type_args_exclude_from_search()
    {
        return ! $this->post_type_args_public();
    }

    protected function post_type_args_publicly_queryable()
    {
        return $this->post_type_args_public();
    }

    protected function post_type_args_show_ui()
    {
        return $this->post_type_args_public();
    }

    protected function post_type_args_show_in_nav_menus()
    {
        return $this->post_type_args_public();
    }

    protected function post_type_args_show_in_menu()
    {
        return $this->post_type_args_show_ui();
    }

    protected function post_type_args_show_in_admin_bar()
    {
        return $this->post_type_args_show_in_menu();
    }

    protected function post_type_args_menu_position()
    {
        return null;
    }

    /**
     * @see http://melchoyce.github.io/dashicons/
     *
     */
    protected function post_type_args_menu_icon()
    {
        return null;
    }

    protected function post_type_args_capability_type()
    {
        return 'post';
    }

    protected function post_type_args_capabilities()
    {
        return array();
    }

    protected function post_type_args_map_meta_cap()
    {
        return null;
    }

    protected function post_type_args_hierarchical()
    {
        return false;
    }

    protected function post_type_args_supports()
    {
        return array(
            'title',
            'editor'
        );
    }

    protected function post_type_args_register_meta_box_cb() { return null; }

    protected function post_type_args_taxonomies() { return null; }

    protected function post_type_args_has_archive() { return false; }

    protected function post_type_args_permalink_epmask() { return EP_PERMALINK; }

    protected function post_type_args_rewrite() { return true; }

    protected function post_type_args_query_var() { return true; }

    protected function post_type_args_can_export() { return true; }

    protected function post_type_args__builtin() { return false; }

    protected function post_type_args__edit_link() { return 'post.php?post=%d'; }

    public function label($post_type_plural)
    {
        if (! $this->valid_string_value($post_type_plural) && !empty($post_type_plural)) {
            throw new \Exception('`$post_type_plural` must be a non-empty string or leave it empty to generate it automatically.');
        }

        return ucfirst(preg_replace('|_+|', ' ', $post_type_plural));
    }

    public function labels_array($post_type, $post_type_plural = '')
    {
        if (! $this->valid_string_value($post_type)) {
            throw new \Exception('`$post_type` must be a non-empty string');
        }

        if (! $this->valid_string_value($post_type_plural) && !empty($post_type_plural)) {
            throw new \Exception('`$post_type_plural` must be a non-empty string or leave it empty to generate it automatically.');
        }

        if (empty($post_type_plural)) {
            $post_type_plural = $this->plural($post_type);
        }

        $item  = ucfirst(preg_replace('|_+|', ' ', $post_type));
        $items = ucfirst(preg_replace('|_+|', ' ', $post_type_plural));

        return array(
            'name'               => "$items",
            'singular_name'      => "$item",
            'add_new'            => "Add New",
            'add_new_item'       => "Add New $item",
            'edit_item'          => "Edit $item",
            'new_item'           => "New $item",
            'all_items'          => "All $items",
            'view_item'          => "View $item",
            'search_items'       => "Search $items",
            'not_found'          => "No ".strtolower($items)." found",
            'not_found_in_trash' => "No ".strtolower($items)." found in Trash",
            'parent_item_colon'  => "Parent $item:",
            'menu_name'          => "$items"
        );
    }
}
