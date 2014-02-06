Hacking Wordpress Custom Objects
================================

Abstract classes (prototypes) for creating custom post types and taxonomies
and registering [WP API][] programmatically

Share, enhance and enjoy!

[Martin][martin_adamko]

[WP API]:        https://github.com/WP-API/WP-API/
[martin_adamko]: http://twitter.com/martin_adamko

Usage
-----

Create a reusable object class you can drop in to any project and register any
post type (or taxonomy). Enhance object and speed up site creation just by
including file.

Build upon overriding parent methods, most hooking hooking can be avoided.

```php
<?php

// LinkPostType.php

use \HackingWP\CustomObjects\PostTypePrototype;

class LinkPostType extends PostTypePrototype
{
    protected $post_type = 'link';

    function post_type_args_supports()
    {
        return array('title', 'author', 'excerpt');
    }

    function post_type_args_public()
    {
        return true;
    }

    function post_type_args_has_archive()
    {
        return true;
    }

    protected function post_type_args_menu_icon()
    {
        return 'dashicons-format-links';
    }

    protected function post_type_args_taxonomies()
    {
        return array('post_tag', 'category');
    }
}
```

Than somewhere in your plugin include and create object:

```php

if (class_exists('\HackingWP\CustomObjects\CustomObjects_PostTypePrototype')) {
    new LinkPostType;
}
```

And you are done. As a bonus, WP API shortcut endpoints have been registered too.
Go ahead and fire:

* `http://example.com/wp-json.php/links` instead of `http://example.com/wp-json.php/posts/types/link`
* `http://example.com/wp-json.php/links/taxonomies`
* `http://example.com/wp-json.php/links/taxonomies/category/terms`
* `http://example.com/wp-json.php/links/taxonomies/category/terms`
* `http://example.com/wp-json.php/links/taxonomies/category/terms/1`

