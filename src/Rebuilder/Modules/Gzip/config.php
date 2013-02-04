<?php
return array(
    // the module name
    'name' => 'gzip',

    // the module class
    'class' => 'Gzip',

    // if the module is enabled
    'enabled' => FALSE,

    /**
     * The absolute path to your application's publicly accessible CSS directory.
     * This is necessary to recursively find all CSS files.
     */
    'cssDir' => NULL,

    /**
     * The absolute path to your application's publicly accessible JS directory.
     * This is necessary to recursively find all JS files.
     */
    'jsDir' => NULL,

    /**
     * This module can take in string or array from the "action" parameter which
     * specifies which asset types to gzip. Available options are:
     *
     *  array('css')            - gzip CSS only
     *  array('js')             - gzip JS only
     *  array('css', 'js')      - gzip both CSS and JS
     *  array()                 - gzip both CSS and JS
     */
    'action' => array()
);
