<?php
namespace Rebuilder\Modules;

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
class Bundler implements ModulesAbstract {

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
     * @param   array   $options
     */
    public function createJsBundle($bundle, $options)
    {
        // merge the config with files and override some core options
        $config = $this->js;

        $config['basepath'] = rtrim($config['basepath'], '/') . '/bundles/';
        $config['files'] = $options['files'];
        $config['output_file'] = $bundle . '.js';
        $config['combine_files'] = TRUE;

        $class = new \Rebuilder\Modules\JSMin($config);
        $class->run();
    }

    /**
     * The core functionality for creation/update of a CSS bundle of a given name.
     *
     * @access  public
     * @param   string  $bundle
     * @param   array   $options
     */
    public function createCssBundle($bundle, $options)
    {
        // merge the config with files
        $config = $this->js;

        $config['basepath'] = rtrim($config['basepath'], '/') . '/bundles/';
        $config['files'] = $options['files'];
        $config['output_file'] = $bundle . '.css';
        $config['combine_files'] = TRUE;

        $class = new \Rebuilder\Modules\CSSTidy($config);
        $class->run();
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
        if (!empty($config['bundles'])) {
            $this->bundles = $config['bundles'];
        }

        $modules = array('css', 'js', 'gzip', 's3');
        foreach($modules as $module) {
            if (!empty($config[$module])) {
                $this->_mergeConfigs($module, $config[$module]);
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
        $this->{$type} = array_merge_recursive(
            $this->loader->getConfig($type),
            $configOverride
        );
    }

}
