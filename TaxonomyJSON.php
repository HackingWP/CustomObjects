<?php

namespace HackingWP\CustomObjects;

use \WP_JSON_Taxonomies;
use \WP_JSON_ResponseHandler;
use \WP_JSON_Server;
use \WP_Error;

class TaxonomyJSON extends WP_JSON_Taxonomies
{
    use Common;

    protected $type;
    protected $taxonomy;
    protected $base;

    public function __construct(WP_JSON_ResponseHandler $server, $taxonomy, $type, $base = '')
    {
        // Use predefined if possible
        if (empty($taxonomy) && !empty($this->taxonomy)) {
            $taxonomy = $this->taxonomy;
        }

        if (empty($base) && !empty($this->base)) {
            $base = $this->base;
        }

        if (empty($type) && !empty($this->type)) {
            $type = $this->type;
        }

        // Check values for errors
        if (! $this->valid_string_value($taxonomy)) {
            throw new \Exception('`$taxonomy` must be a non-empty string. `'.$taxonomy.'` provided.');
        }

        if (!$this->valid_string_value($type)) {
            throw new \Exception('`$type` must be a non-empty string and in plural. `'.$type.'` provided.');
        }

        $type_singular = preg_replace('|-+|', '', strtolower($this->singular($type)));

        if ($type_singular!==$type) {
            wp_die('`$type` must be singular and all lowercase, using dashes only, e.g.: post-statuse, bookmarked-link, movie...');
        }

        if (! $this->valid_string_value($base) && !empty($base)) {
            throw new \Exception('`$base` must be a non-empty string or leave it empty to generate it automatically. `'.$base.'` provided.');
        }

        // Generate standard api paths and set
        if (empty($base)) {
            $base = '/'.preg_replace('|[-_]+|', '-', strtolower($this->plural($type)));
        } else {
            $base = '/'.trim($base, '/');
        }

        $this->taxonomy = $taxonomy;
        $this->type     = $type;
        $this->base     = $base;

        if (is_callable('parent::__construct')) {
            parent::__construct($server);
        } else {
            add_filter( 'json_endpoints',      array( $this, 'registerRoutes' ), 2 );
            add_filter( 'json_post_type_data', array( $this, 'add_taxonomy_data' ), 10, 2 );
            add_filter( 'json_prepare_post',   array( $this, 'add_term_data' ), 10, 3 );
        }

        add_filter( 'json_dispatch_args', array($this, 'fix_missing_post_type'), 10, 2);

        return $this;
    }

    public function fix_missing_post_type($args, $callback )
    {
        // Using hacky strstr (should be faster than preg_match)
        if (strstr('^startswith@'.$args['_route'].'/', '^startswith@'.$this->base. '/taxonomies/')) {
            $args['type'] = $this->type;
        }

        return $args;
    }

    /**
     * Register the taxonomy-related routes
     *
     * @param array $routes Existing routes
     * @return array Modified routes
     */
    public function registerRoutes( $routes ) {
        $tax_routes = array(
            $this->base.'/taxonomies' => array(
                array( array( $this, 'getTaxonomies' ), WP_JSON_Server::READABLE ),
            ),
            $this->base.'/taxonomies/(?P<taxonomy>\w+)' => array(
                array( array( $this, 'getTaxonomy' ), WP_JSON_Server::READABLE ),
            ),
            $this->base.'/taxonomies/(?P<taxonomy>\w+)/terms' => array(
                array( array( $this, 'getTerms' ), WP_JSON_Server::READABLE ),
                array( '__return_null', WP_JSON_Server::CREATABLE | WP_JSON_Server::ACCEPT_JSON ),
            ),
            $this->base.'/taxonomies/(?P<taxonomy>\w+)/terms/(?P<term>\w+)' => array(
                array( array( $this, 'getTerm' ), WP_JSON_Server::READABLE ),
                array( '__return_null', WP_JSON_Server::EDITABLE | WP_JSON_Server::ACCEPT_JSON ),
                array( '__return_null', WP_JSON_Server::DELETABLE ),
            ),
        );
        return array_merge( $routes, $tax_routes );
    }

    /**
     * Prepare a taxonomy for serialization
     *
     * @param stdClass $taxonomy Taxonomy data
     * @param boolean $_in_collection Are we in a collection?
     * @return array Taxonomy data
     */
    protected function prepare_taxonomy( $taxonomy, $type, $_in_collection = false ) {
        if ( $taxonomy->public === false )
            return new WP_Error( 'json_cannot_read_taxonomy', __( 'Cannot view taxonomy' ), array( 'status' => 403 ) );

        $base_url = $this->base. '/taxonomies/' . $taxonomy->name;

        $data = array(
            'name' => $taxonomy->label,
            'slug' => $taxonomy->name,
            'labels' => $taxonomy->labels,
            'types' => array(),
            'show_cloud' => $taxonomy->show_tagcloud,
            'hierarchical' => $taxonomy->hierarchical,
            'meta' => array(
                'links' => array(
                    'archives' => json_url( $base_url . '/terms' )
                )
            ),
        );

        if ( $_in_collection ) {
            $data['meta']['links']['self'] = json_url( $base_url );
        }
        else {
            $data['meta']['links']['collection'] = json_url( $base_url );
        }

        return apply_filters( 'json_prepare_taxonomy', $data );
    }

    /**
     * Prepare term data for serialization
     *
     * @param array|object $term The unprepared term data
     * @return array The prepared term data
     */
    protected function prepare_term( $term, $type, $context = 'view' ) {
        $base_url = $this->base. '/taxonomies/' . $term->taxonomy . '/terms';
        $data = array(
            'ID'     => (int) $term->term_taxonomy_id,
            'name'   => $term->name,
            'slug'   => $term->slug,
            'parent' => (int) $term->parent,
            'count'  => (int) $term->count,
            'link'   => get_term_link( $term, $term->taxonomy ),
            'meta'   => array(
                'links' => array(
                    'collection' => json_url( $base_url ),
                    'self' => json_url( $base_url . '/' . $term->term_id ),
                ),
            ),
        );

        if ( ! empty( $data['parent'] ) && $context === 'view' ) {
            $data['parent'] = $this->getTerm( $type, $term->taxonomy, $data['parent'], 'view-parent' );
        }
        elseif ( empty( $data['parent'] ) ) {
            $data['parent'] = null;
        }

        return apply_filters( 'json_prepare_term', $data, $term );
    }
}
