<?php
return array(
    // the module name
	'name' => 's3',

    // the module class
    'class' => 'S3',

    // if the module is enabled
	'enabled' => FALSE,

    /**
     * If you are using CloudFront in combination with S3, you need to supply
     * your cloudfront distribution id.
     *
     * NOT YET IMPLEMENTED
     */
    'cloudFrontDistributionId' => NULL,

    /**
     * Your Amazon AWS access key. To find your access key, navigate to:
     *
     * http://aws-portal.amazon.com/gp/aws/developer/account/index.html?action=access-key
     *
     * From that page, click on the Access Keys tab and copy the appropriate
     * access key.
     */
    'accessKey' => NULL,

    /**
     * Your Amazon AWS private key. To find your private key, navigate to:
     *
     * http://aws-portal.amazon.com/gp/aws/developer/account/index.html?action=access-key
     *
     * From that page, on the Access Keys tab, you'll find to the right of your
     * active Access Key a link labeled "Show". Click this label to reveal your
     * secret key.
     */
    'privateKey' => NULL,

    /**
     * Whether to support usage of SSL or not. This is required if you intend on
     * using CloudFront.
     */
    'useSSL' => true,

    /**
     * Whether to enable SSL validation, which under the hood enables
     * CURLOPT_SSL_VERIFYHOST and CURLOPT_SSL_VERIFYPEER.
     *
     * NOT YET IMPLEMENTED
     */
    'useSSLValidation' => false,

    /**
     * If you opt for useSSLValidation, you'll want to provide an SSL key. This
     * value gets applied to CURLOPT_SSLKEY.
     *
     * TODO: NOT YET IMPLEMENTED
     */
    'sslKey' => NULL,

    /**
     * If you opt for useSSLValidation, you'll want to provide an SSL cert to go
     * with your SSL key. This value gets applied to CURLOPT_SSLCERT.
     *
     * TODO: NOT YET IMPLEMENTED
     */
    'sslCert' => NULL,

    /**
     * If you opt for useSSLValidation, you may optionally want to provide an
     * SSL CA Cert as opposed to an SSL cert to go with your SSL key. This
     * value gets applied to CURLOPT_CAINFO.
     *
     * TODO: NOT YET IMPLEMENTED
     */
    'sslCACert' => NULL,

    /**
     * The name of the bucket you wish to upload media assets to.
     */
    'bucket' => NULL,

    /**
     * The full URL to the bucket supplied above. You'll likely want to stick
     * to a protocol independent link that works for encryped and unencrypted
     * connections, i.e.:
     *
     * //s3.amazonaws.com/[YOUR-BUCKET-NAME]/
     */
    'bucketUrl' => NULL,

    /**
     * The uriPrefix param is extremely useful if you're planning on mimicking
     * or mirroring your site's existing asset directory structure. This value
     * will be prefixed on each and every file that gets uploaded to Amazon S3,
     * giving files the appearance of containing a directory structure. Many S3
     * viewer applications use this folder naming convention to mimic folders.
     * By using a uriPrefix, you can essentially create a mirror image of your
     * local public assets directories, meaning you can toggle using S3 or local
     * assets via the Bundler module with the toggle of a parameter.
     */
    'uriPrefix' => NULL,

    /**
     * The absolute path to your media/assets public directory. This is likely to
     * be your document root or a sub-directory of your document root such as
     * /media/ or /assets/. It's used to strip out the path from your files to
     * determine their true filename.
     */
    'baseDir' => NULL,

    /**
     * Similar to your baseDir, but applies to the base directory of your CSS
     * files only. Used for recursively finding all of your CSS files for
     * upload.
     */
    'cssDir' => NULL,

    /**
     * Similar to your baseDir, but applies to the base directory of your JS
     * files only. Used for recursively finding all of your JS files for
     * upload.
     */
    'jsDir' => NULL,

    /**
     * Similar to your baseDir, but applies to the base directory of your image
     * files only. Used for recursively finding all of your image files for
     * upload. Images are those that end in .gif, .jpg, .jpeg, .png, and .ico.
     */
    'imgDir' => NULL,

    /**
     * Similar to your baseDir, but applies to the base directory of your font
     * files only. Used for recursively finding all of your font files for
     * upload. Fonts are those that end in .eot, .svg, .ttf, .woff, and .otf.
     */
    'fontDir' => NULL,


    /**
     * This module can take in string or array from the "action" parameter which
     * specifies which asset types to upload to S3. Available options are:
     *
     *  array('css')            - upload CSS only
     *  array('js')             - upload JS only
     *  array('img')            - upload IMAGES only
     *  array ('font')          - upload FONTS only
     *  array('css', 'js')      - upload both CSS and JS
     *  array()                 - upload CSS, JS, IMAGES, and FONTS
     */
    'action' => array()
);
