<?php
return array(
    'name' => 'jsmin',
    'class' => 'JSMin',
    'enabled' => FALSE,
    'config' => array(
        /**
         * The basepath to your JS files. Please ensure this is an absolute
         * path. This is generally a public folder,
         * i.e. /path/to/public/js/ or /path/to/public/assets/js/.
         */
        'basepath' => NULL,

        /**
         * Whether to ensure that your CSS is maintained one rule per line. This
         * drastically enhances readability.
         *
         * NOT YET IMPLEMENTED.
         */
        'multi_line' => FALSE,

        /**
         * Whether to also generate a combined file of all input JS. Great for
         * debugging as it's unminified. Filename generated will be
         * outputfile.compressed.js.
         */
        'combine_files' => FALSE,

        /**
         * The array of files to merge and minify.
         */
        'files' => array(),

        /**
         * The output path where you wish to save the output file. This should
         * be an absolute path. If left blank, we'll assume you want to output
         * to the basepath.
         */
        'output_path' => NULL,

        /**
         * The output filename of the merged and minified files. You'll want
         * this to end in JS. Do not include a path.
         */
        'output_file' => NULL,

        /**
         * A key/val pair array of strings you wish to find and replace in the files.
         * This is particularly useful if you need to do things like environment
         * specific swapping of asset paths in your files.
         */
        'find_replace' => array()
    )
);
