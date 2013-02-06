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
        if (!empty($config['bundler']['bundles'])) {
            self::$bundles = $config['bundler']['bundles'];
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
	 * Output a CSS bundle. The array params for the bundle are as follows:
	 *
	 *   'name' => Bundle name, path to local file, or path to remote file
	 *   'external' => Whether the bundle is a file and needs to be loaded as such
	 *
	 * @access	public
	 * @param	mixed	$bundle		Params for the bundle to output
	 * @return	void
	 */
	public static function css($bundle)
	{
		return self::asset($bundle, 'css');
	}

	/**
	 * Output a JS bundle. The array params for the bundle are as follows:
	 *
	 *   'name' => Bundle name, path to local file, or path to remote file
	 *   'external' => Whether the bundle is a file and needs to be loaded as such
	 *
	 * @access	public
	 * @param	mixed	$bundle
	 * @return	void
	 */
	public static function js($bundle)
	{
		return self::asset($bundle, 'js');
	}

	/**
	 * Load either CSS or JS.
	 *
	 * @access	public static
	 */
	public static function asset($bundle, $type)
	{
		$isBundle = true;

		if ($type == 'css') {
			$format = '<link rel="stylesheet" type="text/css" href="%s">' . PHP_EOL;
			$config = self::$csstidy;
			$ext = '.css';
		} else {
			$format = '<script type="text/javascript" src="%s"></script>' . PHP_EOL;
			$config = self::$jsmin;
			$ext = '.js';
		}

		// special handler for remote files
		if (strpos($bundle, 'http') === 0 || strpos($bundle, '//') === 0) {
			echo sprintf($format, $bundle);
			return;
		}

		// special handling for non-bundle assets
		if (strpos($bundle, '*') === 0) {
			$isBundle = false;
			$bundle = substr($bundle, 1);
			$path = $bundle;
			$dir = rtrim(dirname($bundle), '/') . '/';
			$bundle = strpos($bundle, $ext) !== FALSE ? basename($bundle, $ext) : $bundle;
		} else if (empty(self::$bundles[$bundle][$type])) {
			return false;
		} else {
			// set the path to be the same as the bundle
			$path = $bundle;
		}

		// if loading a bundle and not a normal asset, get the relative bundle path
		if (!isset($dir)) {
			$dir = isset($config['relpath']) ? rtrim($config['relpath'], '/') . '/' : '/';
			$dir .= 'bundles/';
		}

		// assumed filename of bundle/include (without extension)
		$filename = $bundle;

		// check for minify settings
		if (isset($config['minify']) && $config['minify'] === TRUE) {
			if (strpos($path, '.min' . $ext) === FALSE) {
				$filename .= '.min';
			}
		} else if (isset($config['combine']) && $config['combine'] === TRUE) {
			if (strpos($path, '.compressed' . $ext) === FALSE) {
				$filename .= '.compressed';
			}
		} else {
			// we are loading the uncompressed bundle files individually
			return self::loadUncompressed($bundle, $filename, $dir, $type, $ext, $format, $config, $isBundle);
		}

		// we are loading some form of minified/compressed bundle
		return self::loadCompressed($bundle, $filename, $dir, $ext, $format, $config, $isBundle);
	}

	/**
	 * Loads a bundle compressed (either combined or minified).
	 *
	 * @access	public static
	 * @param	string	$bundle
	 * @param	string	$filename
	 * @param	string	$dir
	 * @param	string	$ext
	 * @param	string	$format
	 * @param	array	$config
	 * @param	bool	$isBundle
	 * @return	void
	 */
	public static function loadCompressed($bundle, $filename, $dir, $ext, $format, $config, $isBundle)
	{
		if (isset(self::$s3['enabled']) && self::$s3['enabled'] === TRUE) {
			if (isset(self::$gzip['enabled']) && self::$gzip['enabled'] === TRUE) {
				// ensure the user can handle gzipped files
				if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
					if (strpos($filename, '.gz' . $ext) === FALSE) {
						$filename .= '.gz';
					}
				}
			}

			// grab the bucket url
			$s3BaseUrl = self::$s3['bucketUrl'];

			/*
			if (!empty(self::$s3['uriPrefix'])) {
				$s3BaseUrl .= self::$s3['uriPrefix'];
			}
			*/

			$filepath = $s3BaseUrl . $dir . $filename . $ext;

		} else {
			$filepath = $dir . $filename . $ext;
		}

		// lasty perform any necessary renaming
		if (!empty($config['find_replace'])) {
			$filepath = str_replace(
				array_keys($config['find_replace']),
				array_values($config['find_replace']),
				$filepath
			);
		}

		// ensure no double forward slash
		$filepath = str_replace('//', '/', $filepath);

		echo sprintf($format, $filepath);
	}

	/**
	 * Load the bundle in uncompressed, unminified format. This means we break
	 * it apart and serve each individual file.
	 *
	 * @access	public static
	 * @param	string	$bundle
	 * @param	string	$filename
	 * @param	string	$dir
	 * @param	string	$type
	 * @param	string	$ext
	 * @param	string	$format
	 * @param	array	$config
	 * @param	bool	$isBundle
	 */
	public static function loadUncompressed($bundle, $filename, $dir, $type, $ext, $format, $config, $isBundle)
	{
		$dir = str_replace('bundles/', '', $dir);

		// if we need to load an uncompressed bundle, we actual load each file
		if ($isBundle && !empty(self::$bundles[$bundle][$type])) {
			// iterate over each file
			foreach (self::$bundles[$bundle][$type] as $file) {
				$filepath = $dir . ltrim($file, '/');
				$filepath = str_replace('//', '/', $filepath);
				if (!empty($config['find_replace'])) {
					$filepath = str_replace(
						array_keys($config['find_replace']),
						array_values($config['find_replace']),
						$filepath
					);
				}

				echo sprintf($format, $filepath);
			}
		} else if (!$isBundle) {
			// perform any necessary renaming
			$filepath = $dir . $filename . $ext;
			if (!empty($config['find_replace'])) {
				$filepath = str_replace(
					array_keys($config['find_replace']),
					array_values($config['find_replace']),
					$filepath
				);
			}

			// just load the single uncompressed file
			echo sprintf($format, $filepath);
		}
	}

}
