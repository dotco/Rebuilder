<?php
namespace Rebuilder\Modules;
use \Exception as Exception;

/**
 * Bundler is an advanced implementation of the CSSTidy, and JSMin modules.
 * It combines the two modules into a singular tool which can create media/asset
 * "bundles" for your website. For our purposes, a "bundle" is a named set of
 * combined assets. For each bundle, the following things are created via Bundler:
 *
 *   - The unminified and uncompressed version of the combined files
 *   - The minified and uncompressed version of the combined files
 *
 * If you queue up gzip following bundler, you can then obtain the following
 * additions:
 *
 *   - The unminified and compressed version of the combined files
 *   - The minified and compressed version of the combined files
 *
 * Lastly, you can queue up the Amazon S3 bundle to ensure that all of your
 * media assets get appropriately uploaded.
 *
 * All of these asset variations can then be used by the client-side version
 * of Bundler in your application to serve up different files depending on
 * your environment and requirements.
 *
 * @package		Bundler
 * @copyright	2013 .CO Internet <http://go.co>
 * @author		Corey Ballou <corey@go.co>
 * @link		http://github.com/dotco/Rebuilder
 */
class Bundler extends ModulesAbstract {

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
    public $csstidy = array();

    /**
     * Stores the incoming JS configuration options.
     * @var array
     */
    public $jsmin = array();

    /**
     * The public directory to the media files.
     * @var string
     */
    private $public_dir;

	/**
	 * Default constructor for setting up configuration options.
	 *
	 * @access	public
	 * @param	array   $config
	 * @return	void
	 */
	public function __construct($config = array(), \Rebuilder\Loader $loader)
	{
        // set the loader
        $this->loader = $loader;

        // handle configuration setup and merging
        $this->mergeConfigs($config);
	}

	/**
	 * Special method for the Rebuilder module which executes and runs
	 * the bundler.
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function run()
	{
        // iterate over bundles to perform creation
        foreach ($this->bundles as $bundle => $options) {
            if (!empty($options['js'])) {
                $this->createJsBundle($bundle, $options['js']);
            }

            if (!empty($options['css'])) {
                $this->createCssBundle($bundle, $options['css']);
            }
        }

        $this->combineMinifyCSS();
        $this->combineMinifyJS();
    }

    /**
     * The core functionality for creation/update of a JS bundle of a given name.
     * We need to specifically override a number of parameters, including:
     *
     *   - basepath
     *   - files
     *   - output_file
     *
     * @access  public
     * @param   string  $bundle
     * @param   array   $files
     */
    public function createJsBundle($bundle, $files)
    {
        // merge the config with files and override some core options
        $config = $this->jsmin;

        // add full path to relative filepath
        foreach ($files as $k => $v) {
            $files[$k] = rtrim($config['basepath'], '/') . '/'
                . rtrim($config['relpath'], '/') . '/'
                . ltrim($v, '/');
        }

        $config['files'] = $files;
        $config['combine'] = true;
        $config['minify'] = true;
        $config['output_path'] = rtrim($config['basepath'], '/') . rtrim($config['relpath'], '/') . '/bundles/';
        $config['output_file'] = $bundle . '.js';

        $class = new \Rebuilder\Modules\JSMin($config);
        $class->run();
    }

    /**
     * The core functionality for creation/update of a CSS bundle of a given name.
     *
     * @access  public
     * @param   string  $bundle
     * @param   array   $files
     */
    public function createCssBundle($bundle, $files)
    {
        // merge the config with files
        $config = $this->csstidy;

        // add full path to relative filepath
        foreach ($files as $k => $v) {
            $files[$k] = rtrim($config['basepath'], '/') . '/'
                . rtrim($config['relpath'], '/') . '/'
                . ltrim($v, '/');
        }

        $config['files'] = $files;
        $config['combine'] = true;
        $config['minify'] = true;
        $config['output_path'] = rtrim($config['basepath'], '/') . rtrim($config['relpath'], '/') . '/bundles/';
        $config['output_file'] = $bundle . '.css';

        $class = new \Rebuilder\Modules\CSSTidy($config);
        $class->run();
    }

    /**
     * Handles combining and minifying the CSS files.
     *
     * @access  public
     * @return  void
     */
    public function combineMinifyCSS()
    {
        // minify and combine all other CSS files
        $basepath = rtrim($this->csstidy['basepath'], '/') . rtrim($this->csstidy['relpath'], '/');
        $files = $this->_findFilesRecursive($basepath, array('.css'));
        foreach ($files as $file) {
            // don't deal with already minified or compressed files
            if (strpos($file['filename'], '.min') !== FALSE) {
                continue;
            } else if (strpos($file['filename'], '.compressed') !== FALSE) {
                continue;
            } else if (strpos($file['filename'], '.gz') !== FALSE) {
                continue;
            }

            // send through CSSTidy
            $class = new \Rebuilder\Modules\CSSTidy(
                array(
                    'force_rebuild' => TRUE,
                    'minify' => TRUE,
                    'combine' => TRUE,
                    'basepath' => $this->csstidy['basepath'],
                    'output_path' => dirname($file['filepath']),
                    'output_file' => $file['filename'],
                    'find_replace' => $this->csstidy['find_replace'],
                    'files' => array($file['filepath'])
                )
            );

            $class->run();
        }
    }

    /**
     * Handles combining and minifying the JS files.
     *
     * @access  public
     * @return  void
     */
    public function combineMinifyJS()
    {
        // minify and combine all other JS files
        $basepath = rtrim($this->jsmin['basepath'], '/') . rtrim($this->jsmin['relpath'], '/');
        $files = $this->_findFilesRecursive($basepath, array('.js'));
        foreach ($files as $file) {
            // don't deal with already minified or compressed files
            if (strpos($file['filename'], '.min') !== FALSE) {
                continue;
            } else if (strpos($file['filename'], '.compressed') !== FALSE) {
                continue;
            } else if (strpos($file['filename'], '.gz') !== FALSE) {
                continue;
            }

            // send through JSMin
            $class = new \Rebuilder\Modules\JSMin(
                array(
                    'force_rebuild' => TRUE,
                    'combine' => TRUE,
                    'minify' => TRUE,
                    'basepath' => $this->jsmin['basepath'],
                    'output_path' => dirname($file['filepath']),
                    'output_file' => $file['filename'],
                    'find_replace' => $this->jsmin['find_replace'],
                    'files' => array($file['filepath'])
                )
            );

            $class->run();
        }
    }

    /**
     * Merges bundle specific config options for all of the other modules with
     * the module defaults. Handles overrides.
     *
     * @access  public
     * @param   array   $config
     * @return  void
     */
    public function mergeConfigs($config)
    {
        // set bundles
        if (!empty($config['bundles'])) {
            $this->bundles = $config['bundles'];
        }

        // retrieve additional csstidy and jsmin since we need their paths
        $modules = array('csstidy', 'jsmin');
        foreach($modules as $module) {
            if (!empty($config[$module])) {
                $this->_mergeConfigs($module, $config[$module]);
            } else {
                // just load the default
                $this->{$module} = $this->loader->getModule($module);
            }
        }
    }

    /**
     * The gruntwork for merging of a user defined config file and the default
     * values.
     *
     * @access  protected
     * @param   string      $type
     * @return  void
     */
    protected function _mergeConfigs($type, $configOverride)
    {
        $this->{$type} = $this->_array_merge_recursive(
            $this->loader->getModule($type),
            $configOverride
        );
    }

	/**
	 * Custom implementation of array merge recursive for config files. Ensures
	 * that matching array keys do not create nested arrays but rather override.
	 *
	 * @access	protected
	 *
	 */
	protected function _array_merge_recursive(array $parent, array $override)
	{
		foreach ($override as $key => $value) {
			if (is_array($value) && isset($parent[$key]) && is_array($parent[$key])) {
				$parent[$key] = $this->_array_merge_recursive($parent[$key], $value);
			} else {
				$parent[$key] = $value;
			}
		}
		return $parent;
	}

	/**
	 * Handles recursively finding files in a directory matching a given extension
	 * or extensions.
	 *
	 * @access	protected
	 * @param	string		$dir
	 * @param	array		$ext
	 * @return	array
	 */
	protected function _findFilesRecursive($dir, $ext = array())
	{
		// return files
		$files = array();

		$it = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($dir, \FilesystemIterator::KEY_AS_PATHNAME)
		);

		foreach ($it as $dir => $info) {
			if ($info->isDir()) {
				continue;
			}

			$fileExt = '.' . strtolower($info->getExtension());
			if (!in_array($fileExt, (array) $ext)) {
				continue;
			}

			// get the full path
			$filepath = $info->getPathname();
			$bucketPath = $filepath;
			if (!empty(self::$baseDir)) {
				$bucketPath = str_replace(self::$baseDir, '', $bucketPath);
				$bucketPath = ltrim($bucketPath, '/');
				if (!empty(self::$uriPrefix)) {
					$bucketPath = self::$uriPrefix . $bucketPath;
				}
			}

			$files[] = array(
				'extension' => strtolower($info->getExtension()),
				'filename' => $info->getFilename(),
				'filesize' => $info->getSize(),
				'filepath' => $filepath,
				'bucketPath' => $bucketPath,
				'lastModified' => $info->getMTime()
			);
		}

		return $files;
	}

}
