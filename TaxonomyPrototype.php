<?php

namespace HackingWP\CustomObjects;

/**
 * Custom Taxonomy Registrator Class
 *
 * @see http://codex.wordpress.org/Function_Reference/register_taxonomy
 *
 * Usage: Extend class and redeclare functions as needed. Initiallize by
 * creating a new object.
 *
 */
abstract class TaxonomyPrototype
{
    use Common;

    protected $taxonomy;
    protected $taxonomy_plural;
    protected $objects;
    protected $domain;

    const RESERVED = '["attachment","attachment_id","author","author_name","calendar","cat","category","category__and","category__in","category__not_in","category_name","comments_per_page","comments_popup","customize_messenger_channel","customized","cpage","day","debug","error","exact","feed","hour","link_category","m","minute","monthnum","more","name","nav_menu","nonce","nopaging","offset","order","orderby","p","page","page_id","paged","pagename","pb","perm","post","post__in","post__not_in","post_format","post_mime_type","post_status","post_tag","post_type","posts","posts_per_archive_page","posts_per_page","preview","robots","s","search","second","sentence","showposts","static","subpost","subpost_id","tag","tag__and","tag__in","tag__not_in","tag_id","tag_slug__and","tag_slug__in","taxonomy","tb","term","theme","type","w","withcomments","withoutcomments","year"]';

    public function __construct($taxonomy = '', $objects = array(), $domain = 'default', $taxonomy_plural = '')
    {
        // Use predefined if possible
        if (empty($taxonomy) && !empty($this->taxonomy)) {
            $taxonomy = $this->taxonomy;
        }
        if (empty($objects) && !empty($this->objects)) {
            $objects = $this->objects;
        }
        if (empty($taxonomy_plural) && !empty($this->taxonomy_plural)) {
            $taxonomy_plural = $this->taxonomy_plural;
        }
        if (empty($domain) && !empty($this->domain)) {
            $domain = $this->domain;
        }

        // Check values for errors
        if (! $this->valid_string_value($taxonomy)) {
            throw new \Exception('`$taxonomy` must be a non-empty string. `'.$taxonomy.'` provided.');
        }

        if (in_array($taxonomy, json_decode(self::RESERVED)) && $this->taxonomy_args__builtin()!==true) {
            throw new \Exception('`$taxonomy` name is served and not specified as `_builtin`. `'.$taxonomy.'` provided.');
        }

        if (! $this->valid_array_value($objects)) {
            throw new \Exception('`$objects` must be a non-empty array of post types. `'.json_encode($objects).'` provided.');
        }

        if (! $this->valid_string_value($domain)) {
            throw new \Exception('Text `$domain` must be a non-empty string. `'.$domain.'` provided.');
        }

        if (! $this->valid_string_value($taxonomy_plural) && !empty($taxonomy_plural)) {
            throw new \Exception('`$post_type_plural` must be a non-empty string or leave it empty to generate it automatically. `'.$post_type_plural.'` provided.');
        }

        $taxonomy = trim($taxonomy);
        $domain   = trim($domain);

        $taxonomy_singular = strtolower($this->singular($taxonomy));

        if ($taxonomy_singular!==$taxonomy) {
            wp_die('`$taxonomy` must be singular and all lowercase, e.g.: tag, director...');
        }

        if (empty($taxonomy_plural)) {
            $taxonomy_plural = $this->plural($taxonomy);
        }

        $this->taxonomy        = $taxonomy;
        $this->domain           = $domain;
        $this->taxonomy_plural = $taxonomy_plural;

        // Create object but init registration only for new objects
        if ($this->taxonomy_args__builtin()!==true) {
            add_action('init', array($this, 'register_taxonomy'));
        }

        return $this;
    }

    protected function do_after_registration() {
        return $this;
    }

    protected function do_before_registration() {
        return $this;
    }

    public function register_taxonomy()
    {
        $this->do_before_registration();

        $taxonomy_args = array(
            'labels'                => $this->taxonomy_args_labels(),
            'public'                => $this->taxonomy_args_public(),
            'show_ui'               => $this->taxonomy_args_show_ui(),
            'show_in_nav_menus'     => $this->taxonomy_args_show_in_nav_menus(),
            'show_tagcloud'         => $this->taxonomy_args_show_tagcloud(),
            'meta_box_cb'           => $this->taxonomy_args_meta_box_cb(),
            'show_admin_column'     => $this->taxonomy_args_show_admin_column(),
            'hierarchical'          => $this->taxonomy_args_hierarchical(),
            'update_count_callback' => $this->taxonomy_args_update_count_callback(),
            'query_var'             => $this->taxonomy_args_query_var(),
            'rewrite'               => $this->taxonomy_args_rewrite(),
            'capabilities'          => $this->taxonomy_args_capabilities(),
            'sort'                  => $this->taxonomy_args_sort(),
            '_builtin'              => $this->taxonomy_args__builtin(),
        );

        register_taxonomy( $this->taxonomy, $taxonomy_args );

        $this->do_after_registration();

        return $this;
    }

    private function valid_array_value($v)
    {
        return (!is_array($v) || count($v)===0) ? false : true;
    }

    protected function taxonomy_args_labels() {
        $labels = $this->labels_array($this->taxonomy);

        return array(
            'name'                       => _x($labels['name'], 'taxonomy general name', $this->domain),
            'singular_name'              => _x($labels['singular_name'], 'taxonomy singular name', $this->domain),
            'menu_name'                  => _x($labels['menu_name'], 'taxonomy general name', $this->domain),
            'all_items'                  => __($labels['all_items'], $this->domain),
            'edit_item'                  => __($labels['edit_item'], $this->domain),
            'view_item'                  => __($labels['view_item'], $this->domain),
            'update_item'                => __($labels['update_item'], $this->domain),
            'add_new_item'               => __($labels['add_new_item'], $this->domain),
            'new_item_name'              => __($labels['new_item_name'], $this->domain),
            'parent_item'                => __($labels['parent_item'], $this->domain),
            'parent_item_colon'          => __($labels['parent_item_colon'], $this->domain),
            'search_items'               => __($labels['search_items'], $this->domain),
            'popular_items'              => __($labels['popular_items'], $this->domain),
            'separate_items_with_commas' => __($labels['separate_items_with_commas'], $this->domain),
            'add_or_remove_items'        => __($labels['add_or_remove_items'], $this->domain),
            'choose_from_most_used'      => __($labels['choose_from_most_used'], $this->domain),
            'not_found'                  => __($labels['not_found'], $this->domain),
        );
    }

    protected function taxonomy_args_public() { return TRUE; }

    protected function taxonomy_args_show_ui() { return $this->taxonomy_args_public(); }

    protected function taxonomy_args_show_in_nav_menus() { return $this->taxonomy_args_public(); }

    protected function taxonomy_args_show_tagcloud() { return $this->taxonomy_args_show_ui(); }

    protected function taxonomy_args_meta_box_cb() { return null; }

    protected function taxonomy_args_show_admin_column() { return false; }

    protected function taxonomy_args_hierarchical() { return FALSE; }

    protected function taxonomy_args_update_count_callback() { return ''; }

    protected function taxonomy_args_query_var() { return $this->taxonomy; }

    protected function taxonomy_args_rewrite() { return array( 'slug' => $this->taxonomy ); }

    protected function taxonomy_args_capabilities() { return null; }

    protected function taxonomy_args_sort() { return null; }

    protected function taxonomy_args__builtin() { return false; }

    public function labels_array($taxonomy, $taxonomy_plural = '')
    {
        // Check values for errors
        if (! $this->valid_string_value($taxonomy)) {
            throw new \Exception('`$taxonomy` must be a non-empty string. `'.$taxonomy.'` provided.');
        }

        if (! $this->valid_string_value($domain)) {
            throw new \Exception('Text `$domain` must be a non-empty string. `'.$domain.'` provided.');
        }

        if (! $this->valid_string_value($taxonomy_plural) && !empty($taxonomy_plural)) {
            throw new \Exception('`$post_type_plural` must be a non-empty string or leave it empty to generate it automatically. `'.$post_type_plural.'` provided.');
        }

        $item  = ucfirst(preg_replace('|[-_]+|', ' ', $taxonomy));
        $items = ucfirst(preg_replace('|[-_]+|', ' ', $taxonomy_plural));

        return array(
            'name'                       => "$items",
            'singular_name'              => "$item",
            'menu_name'                  => "$items",
            'all_items'                  => "All $items",
            'edit_item'                  => "Edit $item",
            'view_item'                  => "View $item",
            'update_item'                => "Update $item",
            'add_new_item'               => "Add New $item",
            'new_item_name'              => "New $item Name",
            'parent_item'                => "Parent $item",
            'parent_item_colon'          => "Parent $item:",
            'search_items'               => "Search $items",
            'popular_items'              => "Popular $items",
            'separate_items_with_commas' => "Separate $items with commas",
            'add_or_remove_items'        => "Add or remove $items",
            'choose_from_most_used'      => "Choose from the most used $items",
            'not_found'                  => "No $items found"
        );
    }
}
