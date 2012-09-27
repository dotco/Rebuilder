## About ##
Rebuilder digresses from the usual asset management and pipelining patterns you
may be familiar with. The intention of Rebuilder is to not be run
in your application pipeline, but rather run via a cronjob, hook,
or other means in which you trigger it yourself.

The core concept of Rebuilder is the ability to queue up modules to be run.
There is no restriction on what actions these modules may perform or what files
they may modify. The only thing Rebuilder cares about is that your modules
follow a specific naming convention and directory structure for autoloading
purposes.

## Module Conventions ##

The conventions you must follow when creating or porting modules for usage with
Rebuilder are as follows:

* Modules must be placed within their own namespaced directory in `/modules/`,
i.e. `/modules/CSSTidy/` and `/modules/JSMin/`
* Modules may contain sub-directories and classes.
* If you have a class contained within a sub-directory, the class name must
include the sub-directory path included where you replace any
directory separator, `/`, with an underscore `_`.
* The class naming and directory structure mirrors that of PSR-0 with the exception
of being able to have the following: `/modules/JSMin/JSMin.php`
* Modules must have a default constructor, `__construct()`, which takes in a `$config`
array parameter
* Modules must have a `public function run()` method which is called by Rebuilder

## Usage ##
Rebuilder is intended to be very easy to use. At a very high level, you only
need to run a few commands, passing in a multi-dimensional array of the modules
you wish to run and their configuration options.

Rebuilder requires a `$modules` array to be passed in for queueing of modules.
For each module, Rebuilder has a base set of three configuration settings that
need to be passed in for it to determine how to load the module, if the module
should in fact run, and what configuration values to pass to the module:

* **class** - The name of the module's primary class [CSSTidy]
* **enabled** - Whether the module is enabled [boolean true|false]
* **config** - A configuration array specific to the module in question

Below are a few quick examples of using Rebuilder with CSSTidy and JSMin.
Specific configuration options for CSSTidy and JSMin will be covered later.

#### Run CSSTidy ####
```php
<?php
// compress CSS $files into $output_file
$modules = array(
    'csstidy' => array(
        'class' => 'CSSTidy',
        'enabled' => TRUE,
        'config' => array(
            'basepath' => '/path/to/public/directory/',
            'multi_line' => TRUE,
            'files' => array(
                'css/reset.css',
                'css/global.css'
            ),
            'output_file' => 'css/combined.css'
        )
    )
);

require('Rebuilder/Core.php');
$rebuilder = new Rebuilder_Core($modules);
$rebuilder->run();
<?php
```

#### Run JSMin ####
```php
<?php
$modules = array(
    'jsmin' => array(
        'class' => 'JSMin',
        'enabled' => TRUE,
        'config' => array(
            'basepath' => '/path/to/public/directory/',
            'files' => array(
                'js/jquery.min.js',
                'js/global.js'
            )
            'output_file' => 'js/combined.js'
        )
    )
);

require('Rebuilder/Core.php');
$rebuilder = new Rebuilder_Core($modules);
$rebuilder->run();
```

#### Run CSSTidy and JSMin ####
```php
<?php
$modules = array(
    'csstidy' => array(
        'class' => 'CSSTidy',
        'enabled' => TRUE,
        'config' => array(
            'basepath' => '/path/to/public/directory/',
            'multi_line' => TRUE,
            'files' => array(
                'css/reset.css',
                'css/global.css'
            ),
            'output_file' => 'css/combined.css'
        )
    ),
    'jsmin' => array(
        'class' => 'JSMin',
        'enabled' => TRUE,
        'config' => array(
            'basepath' => '/path/to/public/directory/',
            'files' => array(
                'js/jquery.min.js',
                'js/global.js'
            )
            'output_file' => 'js/combined.js'
        )
    )
);

require('Rebuilder/Core.php');
$rebuilder = new Rebuilder_Core($modules);
$rebuilder->run();
```

## Existing Modules ##
Two modules currently exist and are shipped with Rebuilder:

* JSMin - A modified PHP port of php-jsmin to work with Rebuilder
* CSSTidy - A modified version of php-css-tidy to work with Rebuilder

Below is a rundown of the modules and their configuration parameters.

### JSMin ###
JSMin is a module for combining (merging) a set of JS files into a singular file.
It has handling build in for attempted retrieval of remote HTTP files (i.e. Google
hosted jQuery). JSMin also handles minification of Javascript files.
It also has handling built in which will skip minification of files
that have `min.` in their filename. This is necessary to avoid double minification.
JSMin has the following configuration settings:

* **basepath** - The full base path to the public directory of the files on the server. Gets concatted with relative filepaths below.
* **files** - An array of the relative paths to the files to be merged. Must be in order.
* **output_file** - The relative path to the output file where the CSS files get merged.

### CSSTidy ###
CSSTidy is a module for combining (merging) a set of CSS files into a singular
file. CSSTidy also handles compression of the CSS into one line or one rule per line.

CSSTidy has the following configuration settings:

* **basepath** - The full base path to the public directory of the files on the server. Gets concatted with relative filepaths below.
* **multi_line** - Whether to combine the files on a single line (max compression), or one rule per line.
* **files** - An array of the relative paths to the files to be merged. Must be in order.
* **output_file** - The relative path to the output file where the CSS files get merged.
