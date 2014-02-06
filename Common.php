<?php

namespace HackingWP\CustomObjects;

/**
 * Common Trait
 *
 * Includes CodeIgniter Inflector Helpers
 *
 * @package     CodeIgniter
 * @subpackage  Helpers
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2008 - 2013, EllisLab, Inc. (http://ellislab.com/)
 * @license     http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @since       Version 1.0
 * @link        http://codeigniter.com/user_guide/helpers/inflector_helper.html
 *
 */

trait Common
{
    private function valid_string_value($v)
    {
        return (!is_string($v) || strlen(trim($v))===0) ? false : true;
    }

    /**
     * Checks if the given word has a plural version.
     *
     * @param   string  $word   Word to check
     * @return  bool
     */
    function is_countable($word)
    {
        return ! in_array(strtolower($word),
                    array(
                        'equipment', 'information', 'rice', 'money',
                        'species', 'series', 'fish', 'meta'
                    )
            );
    }

    /**
     * Singular
     *
     * Takes a plural word and makes it singular
     *
     * @param   string  $str    Input string
     * @return  string
     */
    function singular($str)
    {
        $result = strval($str);

        if ( ! $this->is_countable($result))
        {
            return $result;
        }

        $singular_rules = array(
            '/(matr)ices$/'     => '\1ix',
            '/(vert|ind)ices$/' => '\1ex',
            '/^(ox)en/'         => '\1',
            '/(alias)es$/'      => '\1',
            '/([octop|vir])i$/' => '\1us',
            '/(cris|ax|test)es$/' => '\1is',
            '/(shoe)s$/'        => '\1',
            '/(o)es$/'          => '\1',
            '/(bus|campus)es$/' => '\1',
            '/([m|l])ice$/'     => '\1ouse',
            '/(x|ch|ss|sh)es$/' => '\1',
            '/(m)ovies$/'       => '\1\2ovie',
            '/(s)eries$/'       => '\1\2eries',
            '/([^aeiouy]|qu)ies$/'  => '\1y',
            '/([lr])ves$/'      => '\1f',
            '/(tive)s$/'        => '\1',
            '/(hive)s$/'        => '\1',
            '/([^f])ves$/'      => '\1fe',
            '/(^analy)ses$/'    => '\1sis',
            '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/' => '\1\2sis',
            '/([ti])a$/'        => '\1um',
            '/(p)eople$/'       => '\1\2erson',
            '/(m)en$/'          => '\1an',
            '/(s)tatuses$/'     => '\1\2tatus',
            '/(c)hildren$/'     => '\1\2hild',
            '/(n)ews$/'         => '\1\2ews',
            '/([^us])s$/'       => '\1'
        );

        foreach ($singular_rules as $rule => $replacement)
        {
            if (preg_match($rule, $result))
            {
                $result = preg_replace($rule, $replacement, $result);
                break;
            }
        }

        return $result;
    }

    /**
     * Plural
     *
     * Takes a singular word and makes it plural
     *
     * @param   string  $str    Input string
     * @return  string
     */
    function plural($str)
    {
        $result = strval($str);

        if ( ! $this->is_countable($result))
        {
            return $result;
        }

        $plural_rules = array(
            '/^(ox)$/'                 => '\1\2en',     // ox
            '/([m|l])ouse$/'           => '\1ice',      // mouse, louse
            '/(matr|vert|ind)ix|ex$/'  => '\1ices',     // matrix, vertex, index
            '/(x|ch|ss|sh)$/'          => '\1es',       // search, switch, fix, box, process, address
            '/([^aeiouy]|qu)y$/'       => '\1ies',      // query, ability, agency
            '/(hive)$/'                => '\1s',        // archive, hive
            '/(?:([^f])fe|([lr])f)$/'  => '\1\2ves',    // half, safe, wife
            '/sis$/'                   => 'ses',        // basis, diagnosis
            '/([ti])um$/'              => '\1a',        // datum, medium
            '/(p)erson$/'              => '\1eople',    // person, salesperson
            '/(m)an$/'                 => '\1en',       // man, woman, spokesman
            '/(c)hild$/'               => '\1hildren',  // child
            '/(buffal|tomat)o$/'       => '\1\2oes',    // buffalo, tomato
            '/(bu|campu)s$/'           => '\1\2ses',    // bus, campus
            '/(alias|status|virus)$/'  => '\1es',       // alias
            '/(octop)us$/'             => '\1i',        // octopus
            '/(ax|cris|test)is$/'      => '\1es',       // axis, crisis
            '/s$/'                     => 's',          // no change (compatibility)
            '/$/'                      => 's',
        );

        foreach ($plural_rules as $rule => $replacement)
        {
            if (preg_match($rule, $result))
            {
                $result = preg_replace($rule, $replacement, $result);
                break;
            }
        }

        return $result;
    }
}
