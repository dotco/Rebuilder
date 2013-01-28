<?php
/**
 * It's common to have different environment specific settings. Below, you will
 * find four common environments with their own distinct settings. When initiating
 * the config file via `./rebuilder --config=config-sample.php`, note that you
 * can optionally pass the environmental variable, env, which will specifically
 * target that environment as opposed to all environments, saving you time.
 */
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
                'bundles' => array(

                ),

                // csstidy specific config options for bundler
                'csstidy' => array(
                    'enabled' => TRUE,
                    'config' => array(
                        'basepath' => NULL,
                        'multi_line' => TRUE,
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
