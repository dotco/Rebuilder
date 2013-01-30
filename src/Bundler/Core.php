<?php
namespace Bundler;

/**
 * This file is represents the client-side version of Bundler in your application
 * to serve up all sorts of variations which may be required by your different
 * application environments. This includes combining bundle pre-requisites.
 *
 * @package		Bundler
 * @copyright	2013 .CO Internet <http://go.co>
 * @author		Corey Ballou <corey@go.co>
 * @link		http://github.com/dotco/Rebuilder
 */
class Core {

    /**
     * Stores the incoming bundles.
     * @var array
     */
    public static $bundles = array();

    /**
     * Stores the incoming CSS configuration options.
     * @var array
     */
    public static $csstidy = array();

    /**
     * Stores the incoming JS configuration options.
     * @var array
     */
    public static $jsmin = array();

    /**
     * Stores the incoming Gzip configuration options.
     * @var array
     */
    public static $gzip = array();

    /**
     * Stores the incoming Amazon S3 configuration options.
     * @var array
     */
    public static $s3 = array();

	/**
	 * Default handler for setting up configuration options.
	 *
	 * @access	public
	 * @param	array   $config
	 * @return	void
	 */
	public static function init($config = array())
	{
        if (!empty($config['bundler']['config']['bundles'])) {
            self::$bundles = $config['bundler']['config']['bundles'];
        }

        if (!empty($config['csstidy'])) {
            self::$csstidy = $config['csstidy'];
        }

        if (!empty($config['jsmin'])) {
            self::$jsmin = $config['jsmin'];
        }

        if (!empty($config['gzip'])) {
            self::$gzip = $config['gzip'];
        }

        if (!empty($config['s3'])) {
            self::$s3 = $config['s3'];
        }
	}

	/**
	 * Output a CSS bundle.
	 *
	 * @access	public
	 * @param	string	$bundle
	 * @return	void
	 */
	public static function css($bundle)
	{
		if (empty(self::$bundles[$bundle]['css'])) {
			return false;
		}

		// merge bundle pre-requisites
		$bundles = self::$bundles[$bundle]['css']['files'];
		if (!empty(self::$bundles[$bundle]['css']['requires'])) {
			$bundles = self::mergeBundles(
				$bundle,
				$bundles,
				self::$bundles[$bundle]['css']['requires'],
				'css'
			);
		}

		// for every file, set the proper path
		foreach ($bundles as $k => $path) {
			$dir = dirname($path) . '/';
			$filename = basename($path, '.css');

			if (isset(self::$csstidy['minify']) && self::$csstidy['minify'] === TRUE) {
				if (strpos($path, '.min.css') === FALSE) {
					$filename .= '.min';
				}
			} else if (isset(self::$csstidy['combine']) && self::$csstidy['combine'] === TRUE) {
				if (strpos($path, '.compressed.css') === FALSE) {
					$filename .= '.compressed';
				}
			}

			if (isset(self::$s3['enabled']) && self::$s3['enabled'] === TRUE) {
				if (isset(self::$gzip['enabled']) && self::$gzip['enabled'] === TRUE) {
					if (strpos($path, '.gz.css') === FALSE) {
						$filename .= '.gz';
					}
				}

				// grab the bucket url
				$s3BaseUrl = self::$s3['config']['bucketUrl'];
				if (!empty(self::$s3['config']['uriPrefix'])) {
					$s3BaseUrl .= self::$s3['config']['uriPrefix'];
				}

				$bundles[$k] = $s3BaseUrl . $dir . $filename . '.css';
			} else {
				$bundles[$k] = $dir . $filename . '.css';
			}
		}

		// TODO: output file(s)
		return $bundles;
	}

	/**
	 * Output a JS bundle.
	 *
	 * @access	public
	 * @param	string	$bundle
	 * @return	void
	 */
	public static function js($bundle)
	{
		if (empty(self::$bundles[$bundle]['js'])) {
			return false;
		}

		// merge bundle pre-requisites
		$bundles = self::$bundles[$bundle]['js']['files'];
		if (!empty(self::$bundles[$bundle]['js']['requires'])) {
			$bundles = self::mergeBundles(
				$bundle,
				$bundles,
				self::$bundles[$bundle]['js']['requires']
			);
		}

		// for every file, set the proper path
		foreach ($bundles as $k => $path) {
			$dir = dirname($path) . '/';
			$filename = basename($path, '.js');

			if (isset(self::$csstidy['minify']) && self::$csstidy['minify'] === TRUE) {
				if (strpos($path, '.min.js') === FALSE) {
					$filename .= '.min';
				}
			} else if (isset(self::$csstidy['combine']) && self::$csstidy['combine'] === TRUE) {
				if (strpos($path, '.compressed.js') === FALSE) {
					$filename .= '.compressed';
				}
			}

			if (isset(self::$s3['enabled']) && self::$s3['enabled'] === TRUE) {
				if (isset(self::$gzip['enabled']) && self::$gzip['enabled'] === TRUE) {
					if (strpos($path, '.gz.js') === FALSE) {
						$filename .= '.gz';
					}
				}

				// grab the bucket url
				$s3BaseUrl = self::$s3['config']['bucketUrl'];
				if (!empty(self::$s3['config']['uriPrefix'])) {
					$s3BaseUrl .= self::$s3['config']['uriPrefix'];
				}

				$bundles[$k] = $s3BaseUrl . $dir . $filename . '.js';
			} else {
				$bundles[$k] = $dir . $filename . '.js';
			}
		}

		// TODO: output file(s)
		return $bundles;
	}

    /**
     * Handles the merging of bundles based on their pre-requisites. This method
     * is the first step in the merge as it takes the bundle in that has
     * pre-requisites. We also ensure that no duplicates exist in the combined
     * list(s).
     *
     * @access  public
     * @param   string  $bundle         The bundle name
     * @param   array   $files          The array of files in the bundle
     * @param   array   $requirements   The array of pre-req bundles
     * @param   string  $type           'js' or 'css'
     * @return  void
     */
    public static function mergeBundles(
        $bundle,
        $files = array(),
        $requirements = array(),
        $type = 'js'
    )
    {
        // keep a cache of requirements so we don't endless loop
        $cache = array();

        // iterate over requirements
        foreach ($requirements as $parent_bundle) {
            if (isset($cache[$parent_bundle])) {
                continue;
            }

            // cache the parent bundle
            $cache[$parent_bundle] = TRUE;

            // ensure parent bundle exists
            if (empty(self::$bundles[$parent_bundle][$type]['files'])) {
                continue;
            }

            // merge parent bundle files with the bundle
            $files = array_unique(
                array_merge(self::$bundles[$parent_bundle][$type]['files'], $files)
            );

            // check if parent bundle has any pre-reqs as well
			if (!empty(self::$bundles[$parent_bundle][$type]['requires'])) {
				$files = self::mergeBundles(
					$parent_bundle,
					$files,
					self::$bundles[$parent_bundle][$type]['requires'],
					$type
				);
			}
        }

		return $files;
    }

}
