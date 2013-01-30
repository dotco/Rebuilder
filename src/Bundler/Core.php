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
     * The instantiated Rebuilder module loader.
     * @var Rebuilder\Loader
     */
    public $loader;

    /**
     * Stores the incoming bundles.
     * @var array
     */
    public $bundles = array();

    /**
     * Stores the incoming CSS configuration options.
     * @var array
     */
    public $css = array();

    /**
     * Stores the incoming JS configuration options.
     * @var array
     */
    public $js = array();

    /**
     * Stores the incoming Gzip configuration options.
     * @var array
     */
    public $gzip = array();

    /**
     * Stores the incoming Amazon S3 configuration options.
     * @var array
     */
    public $s3 = array();

	/**
	 * Default constructor for setting up configuration options.
	 *
	 * @access	public
	 * @param	array   $config
	 * @return	void
	 */
	public function __construct($config = array())
	{
        // set the loader
        $this->loader = $loader;

        if (!empty($config['bundles'])) {
            $this->bundles = $config['bundles'];
        }

        if (!empty($config['css'])) {
            $this->css = $config['css'];
        }

        if (!empty($config['js'])) {
            $this->js = $config['js'];
        }

        if (!empty($config['gzip'])) {
            $this->gzip = $config['gzip'];
        }

        if (!empty($config['s3'])) {
            $this->s3 = $config['s3'];
        }
	}

	/**
	 * Output a CSS bundle.
	 *
	 * @access	public
	 * @param	string	$bundle
	 * @return	void
	 */
	public function css($bundle)
	{
		if (empty($this->bundles[$bundle]['css'])) {
			return false;
		}

		// merge bundle pre-requisites
		$bundles = $this->bundles[$bundle]['css']['files'];
		if (!empty($this->bundles[$bundle]['css']['requires'])) {
			$bundles = $this->mergeBundles(
				$bundle,
				$bundles,
				$this->bundles[$bundle]['css']['requires']
			);
		}

		// for every file, set the proper path
		foreach ($files as $k => $path) {
			$dir = dirname($path) . '/';
			$filename = basename($path, '.css');

			if (isset($this->css['minify']) && $this->css['minify'] === TRUE) {
				if (strpos($path, '.min.css') === FALSE) {
					$filename .= '.min';
				}
			} else if (isset($this->css['combine']) && $this->css['combine'] === TRUE) {
				if (strpos($path, '.compressed.css') === FALSE) {
					$filename .= '.compressed';
				}
			}

			if (isset($this->s3['enabled']) && $this->s3['enabled'] === TRUE) {
				if (isset($this->gzip['enabled']) && $this->gzip['enabled'] === TRUE) {
					if (strpos($path, '.gz.css') === FALSE) {
						$filename .= '.gz';
					}
				}

				// grab the bucket url
				$s3BaseUrl = $this->s3['config']['bucketUrl'];
				if (!empty($this->s3['config']['uriPrefix'])) {
					$s3BaseUrl .= $this->s3['config']['uriPrefix'];
				}

				$files[$k] = $s3BaseUrl . $dir . $filename . '.css';
			} else {
				$files[$k] = $dir . $filename . '.css';
			}
		}

		// TODO: output file(s)
		return $files;
	}

	/**
	 * Output a JS bundle.
	 *
	 * @access	public
	 * @param	string	$bundle
	 * @return	void
	 */
	public function js($bundle)
	{
		if (empty($this->bundles[$bundle]['js'])) {
			return false;
		}

		// merge bundle pre-requisites
		$bundles = $this->bundles[$bundle]['js']['files'];
		if (!empty($this->bundles[$bundle]['js']['requires'])) {
			$bundles = $this->mergeBundles(
				$bundle,
				$bundles,
				$this->bundles[$bundle]['js']['requires']
			);
		}

		// for every file, set the proper path
		foreach ($files as $k => $path) {
			$dir = dirname($path) . '/';
			$filename = basename($path, '.js');

			if (isset($this->css['minify']) && $this->css['minify'] === TRUE) {
				if (strpos($path, '.min.js') === FALSE) {
					$filename .= '.min';
				}
			} else if (isset($this->css['combine']) && $this->css['combine'] === TRUE) {
				if (strpos($path, '.compressed.js') === FALSE) {
					$filename .= '.compressed';
				}
			}

			if (isset($this->s3['enabled']) && $this->s3['enabled'] === TRUE) {
				if (isset($this->gzip['enabled']) && $this->gzip['enabled'] === TRUE) {
					if (strpos($path, '.gz.js') === FALSE) {
						$filename .= '.gz';
					}
				}

				// grab the bucket url
				$s3BaseUrl = $this->s3['config']['bucketUrl'];
				if (!empty($this->s3['config']['uriPrefix'])) {
					$s3BaseUrl .= $this->s3['config']['uriPrefix'];
				}

				$files[$k] = $s3BaseUrl . $dir . $filename . '.js';
			} else {
				$files[$k] = $dir . $filename . '.js';
			}
		}

		// TODO: output file(s)
		return $files;
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
    public function mergeBundles(
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
            if (empty($this->bundles[$parent_bundle][$type]['files'])) {
                continue;
            }

            // merge parent bundle files with the bundle
            $files = array_unique(
                array_merge($files, $this->bundles[$parent_bundle][$type]['files'])
            );

            // check if parent bundle has any pre-reqs as well
			if (!empty($this->bundles[$parent_bundle][$type]['requires'])) {
				$files = $this->mergeBundles(
					$parent_bundle,
					$files,
					$this->bundles[$parent_bundle][$type]['requires'],
					$type
				);
			}
        }

		return $files;
    }

}
