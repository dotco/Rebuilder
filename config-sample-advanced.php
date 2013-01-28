<?php
/**
 * The advanced configuration example assumes you have read and understood the
 * basic configuration sample file. In the advanced example, we utilize third
 * party includes to generate our configuration settings and return the dynamic
 * array. This is likely to be a common scenario if you wish to tie Bundler
 * directly into your application and share the configuration settings.
 *
 * In many cases, MVC applications have a specific configuration file structure.
 * You'll likely want to stick to that convention and simply generate the
 * Rebuilder configuration settings piecemeal from your application's configuration
 * settings.
 */

// set the application's overall base directory
$basedir = __DIR__ . '../../';

// load the application specific configuration file (or files)
$config = include_once($basedir . 'application/config/application.php');

// utilize the application's config settings to generate your Rebuilder config
return array(
    /**
     * The production environment Rebuilder settings.
     */
    'production' => array(
        // the bundler module settings
        'bundler' => array(
            'enabled' => TRUE,
            'config' => array(
                // container for all of the bundles
                'bundles' => isset($config['production']['bundles'])
                    ? $config['production']['bundles'] : array(),

                // csstidy specific config options for bundler
                'csstidy' => array(
                    'enabled' => TRUE,
                    'config' => array(
                        'basepath' => isset($config['production']['css']['basepath']) ?
                            $config['production']['css']['basepath'] : NULL,
                        'multi_line' => isset($config['production']['css']['multi_line']) ?
                            $config['production']['css']['multi_line'] : TRUE,
                        'files' => array(),
                        'output_file' => NULL
                    )
                ),

                // gzip specific config options for bundler
                'gzip' => array(
                    'enabled' => TRUE,
                    'config' => array(
                        'cssDir' => NULL,
                        'jsDir' => NULL,
                        'type' => array()
                    )
                ),

                // jsmin specific config options for bundler
                'jsmin' => array(
                    'enabled' => TRUE,
                    'config' => array(
                        'basepath' => NULL,
                        'files' => array(),
                        'output_file' => NULL
                    )
                ),

                // s3 specific config options for bundler
                's3' => array(
                    'enabled' => TRUE,
                    'config' => array(
                        'cloudFrontDistributionId' => NULL,
                        'privateKey' => NULL,
                        'accessKey' => NULL,
                        'useSSL' => true,
                        'bucket' => NULL,
                        'bucketUrl' => NULL,
                        'uriPrefix' => NULL,
                        'baseDir' => NULL,
                        'cssDir' => NULL,
                        'jsDir' => NULL,
                        'imgDir' => NULL,
                        'fontDir' => NULL,
                        'type' => array()
                    )
                )
            )
        ),

        // the jsmin module settings
        'jsmin' => array(
            'enabled' => TRUE,
            'config' => array(
                'basepath' => NULL,
                'files' => array(),
                'output_file' => NULL
            )
        ),

        // the csstidy module settings
        'csstidy' => array(
            'enabled' => TRUE,
            'config' => array(
                'basepath' => NULL,
                'multi_line' => TRUE,
                'files' => array(),
                'output_file' => NULL
            )
        ),

        // the gzip module settings
        'gzip' => array(
            'enabled' => TRUE,
            'config' => array(
                'cssDir' => NULL,
                'jsDir' => NULL,
                'type' => array()
            )
        ),

        // the amazon S# module settings
        's3' => array(
            'enabled' => TRUE,
            'config' => array(
                'cloudFrontDistributionId' => NULL,
                'privateKey' => NULL,
                'accessKey' => NULL,
                'useSSL' => true,
                'bucket' => NULL,
                'bucketUrl' => NULL,
                'uriPrefix' => NULL,
                'baseDir' => NULL,
                'cssDir' => NULL,
                'jsDir' => NULL,
                'imgDir' => NULL,
                'fontDir' => NULL,
                'type' => array()
            )
        )
    ),

    /**
     * The staging environment Rebuilder settings.
     */
    'staging' => array(

    ),

    /**
     * The development environment Rebuilder settings.
     */
    'development' => array(

    ),

    /**
     * The local environment Rebuilder settings.
     */
    'local' => array(

    )
);
