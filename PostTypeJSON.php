<?php

namespace HackingWP\CustomObjects;

use \WP_JSON_ResponseHandler;
use \WP_JSON_CustomPostType;
use \WP_JSON_Server;

class PostTypeJSON extends WP_JSON_CustomPostType
{
    use Common;

    protected $base;
    protected $type;

    public function __construct(WP_JSON_ResponseHandler $server, $type, $base = '')
    {
        // Use predefined if possible
        if (empty($base) && !empty($this->base)) {
            $base = $this->base;
        }

        if (empty($type) && !empty($this->type)) {
            $type = $this->type;
        }

        // Check values for errors
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

        $this->type = $type;
        $this->base = $base;

        parent::__construct($server);

        add_filter( 'json_dispatch_args', array($this, 'fix_missing_data_parameter'), 10, 2);

        return $this;
    }

	/**
	 * Register the routes for the post type
	 *
	 * @param array $routes Routes for the post type
	 * @return array Modified routes
	 */
	public function registerRoutes( $routes ) {
		$routes[ $this->base ] = array(
			array( array( $this, 'getPosts' ), WP_JSON_Server::READABLE ),
			array( array( $this, 'newPost' ),  WP_JSON_Server::CREATABLE | WP_JSON_Server::ACCEPT_JSON ),
		);

		$routes[ $this->base . '/(?P<id>\d+)' ] = array(
			array( array( $this, 'getPost' ),    WP_JSON_Server::READABLE ),
			array( array( $this, 'editPost' ),   WP_JSON_Server::EDITABLE | WP_JSON_Server::ACCEPT_JSON ),
			array( array( $this, 'deletePost' ), WP_JSON_Server::DELETABLE ),
		);
		return $routes;
	}

    public function fix_missing_data_parameter($args, $callback )
    {
        // Using hacky strstr (should be faster than preg_match)
        if (strstr('^startswith@'.$args['_route'].'/', '^startswith@'.$this->base.'/')) {
            $args['data']['type'] = $this->type;
        }

        return $args;
    }
}
