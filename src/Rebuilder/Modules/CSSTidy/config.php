<?php
return array(
    // the module name
    'name' => 'csstidy',

    // the module class
    'class' => 'CSSTidy',

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
     * Whether to ensure that your CSS is maintained one rule per line. This
     * drastically enhances readability.
     */
    'multi_line' => TRUE,

    /**
     * Whether to minify the input CSS. Filename generated will be
     * outputfile.min.css.
     */
    'minify' => FALSE,

    /**
     * Whether to also generate a combined file of all input CSS. Great for
     * debugging as it's unminified. Filename generated will be
     * outputfile.compressed.css.
     */
    'combine' => FALSE,

    /**
     * The array of files to merge and minify. File paths should be relative
     * from your relpath, i.e. do not include basepath or relpath in your
     * filepath. If your basepath was "/var/www/vhosts/project/html/public"
     * and your relpath was "css/", your files should be something like:
     *
     *   - backgrounds/my-bg.gif
     *   - icons/button.png
     *   - logo.jpg
     *
     * Note that you can also include remote web accessible files, i.e.:
     *
     *   - http://fonts.googleapis.com/css?family=Cantarell
     *   - https://www.domain.com/path/to/external.min.css
     *
     * Lastly, all input files that contain the string ".min" are assumed to
     * be already minified. They will not be double minified if you follow
     * this convention, although minification won't adversely affect css
     * files.
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
     * this to end in CSS. It cannot be a remote path, i.e. http(s)?. You may
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
