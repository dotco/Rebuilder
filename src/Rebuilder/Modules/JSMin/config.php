<?php
return array(
    // the module name
    'name' => 'jsmin',

    // the module class
    'class' => 'JSMin',

    // if the module is enabled
    'enabled' => FALSE,

    /**
     * The basepath to your document root. Please ensure this is an absolute
     * path. This is generally your web application's base public folder.
     */
    'basepath' => NULL,

    /**
     * The relative path to the CSS base directory from the application's document
     * root. It's used in combination with the basepath for generating absolute
     * paths to your assets.
     */
    'relpath' => NULL,

    /**
     * Whether to ensure that your JS lines are maintained during the minification
     * process. Helps with debugging at minimal cost.
     *
     * NOT YET IMPLEMENTED.
     */
    'preserve_lines' => FALSE,

    /**
     * Whether to minify the input JS. Filename generated will be
     * outputfile.min.js.
     */
    'minify' => FALSE,

    /**
     * Whether to also generate a combined file of all input CSS. Great for
     * debugging as it's unminified. Filename generated will be
     * outputfile.compressed.js.
     */
    'combine' => FALSE,

    /**
     * The array of files to merge and minify. File paths should be relative
     * from your relpath, i.e. do not include basepath or relpath in your
     * filepath. If your basepath was "/var/www/vhosts/project/html/public"
     * and your relpath was "js/", your files should be something like:
     *
     *   - plugins/jquery.min.js
     *   - pages/home.js
     *   - global.js
     *
     * Note that you can also include remote web accessible files, i.e.:
     *
     *   - http://www.domain.com/path/to/jquery.1.8.js
     *   - https://www.domain.com/path/to/minified.min.js
     *
     * Lastly, all input files that contain the string ".min" are assumed to
     * be already minified. They will not be double minified if you follow
     * this convention.
     */
    'files' => array(),

    /**
     * The output path where you wish to save the output file. This should
     * be an absolute path to the directory you wish to store the output file
     * in.
     */
    'output_path' => NULL,

    /**
     * The output filename of the merged and minified files. You'll want
     * this to end in JS. It cannot be a remote path, i.e. http(s)?. You may
     * optionally choose to use an absolute path matching the fullpath or
     * just specify the filename.
     */
    'output_file' => NULL,

    /**
     * A key/val pair array of strings you wish to find and replace in the files.
     * It will only affect yoru output file and not the originals. This is
     * particularly useful if you need to do things like environment specific
     * swapping of asset paths in your files.
     */
    'find_replace' => array()
);
