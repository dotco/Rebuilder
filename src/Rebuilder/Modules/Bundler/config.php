<?php
return array(
    // the module name
    'name' => 'bundler',

    // the core class name
    'class' => 'Bundler',

    // whether Bundler is enabled (deprecated)
    'enabled' => FALSE,

    // container for all of the bundles
    'bundles' => array(),

    /**
     * Container for csstidy specific config options. See the CSSTidy module's
     * default configuration files for options. Suggested implementation is to
     * pull in your custom CSSTidy module options into this array dynamically.
     */
    'csstidy' => array(),

    /**
     * Container for gzip specific config options. See the GZip module's
     * default configuration file for options. Suggested implementation is to
     * pull in your custom GZip module options into this array dynamically.
     */
    'gzip' => array(),

    /**
     * Container for jsmin specific config options. See the JSMin module's
     * default configuration file for options. Suggested implementation is to
     * pull in your custom JSMin module options into this array dynamically.
     */
    'jsmin' => array(),

    /**
     * Container for s3 specific config options. See the S3 module's default
     * configuration file for options. Suggested implementation is to pull in your
     * custom S3 module options into this array dynamically.
     */
    's3' => array()
);
