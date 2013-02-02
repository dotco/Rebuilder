<?php
namespace Rebuilder;

/**
 * The core rebuilder class which handles the loading of modules.
 */
class Loader {

    /**
     * The path to the module directory.
     *
     * @var string
     */
    protected $_defaultModulePath;

    /**
     * Custom module paths.
     *
     * @var array
     */
    protected $_customModulePaths = array();

    /**
     * The default config values which get merged. Also provides the full
     * set of allowable config keys.
     *
     * @var array
     */
    private $_modules = array();

    /**
     * The default constructor for loading configuration settings and
     * setting up the autoloader.
     *
     * @access  public
     * @param   array   $customPaths    Full paths to custom module directories
     * @return  void
     */
    public function __construct($customPaths)
    {
        // set the default module path
        $this->_defaultModulePath = __DIR__ . '/modules/';
        $this->_customModulePaths = $customPaths;

        // load default modules
        $this->loadDefault();

        // load custom modules
        $this->loadCustom();

        // merge defaults with modules
        if (!empty($modules)) {
            $this->_modules = $this->_mergeModuleConfig($this->_modules, $modules);
        }

        // register the autoloader for modules
        spl_autoload_register(array($this, 'moduleLoader'));
    }

    /**
     * Loads all default module configuration files recursively.
     *
     * @access  public
     * @return  void
     */
    public function loadDefault()
    {
        $this->_loadModulesFromDirectory($this->_defaultModulePath);
    }

    /**
     * Attempt to load custom modules.
     *
     * @access  public
     * @param   string|array    $customPaths
     * @return  void
     */
    public function loadCustom($customPaths)
    {
        if (!empty($customPaths)) {
            foreach ((array) $customPaths as $path) {
                $this->_loadModulesFromDirectory($path);
            }
        }
    }

    /**
     * Handles adding a module to the queue of modules.
     *
     * @access  public
     * @param   string  $moduleName
     * @param   array   $moduleConfig
     * @return  void
     */
    public function addModule($moduleName, array $moduleConfig)
    {
        // handle overriding existing settings
        if (isset($this->_modules[$moduleName])) {
            $this->_modules[$moduleName] =
                $this->_mergeModuleConfig($this->_modules[$moduleName], $moduleConfig);
        } else {
            // handles loading new module
            $this->_modules[$moduleName] = $moduleConfig;
        }
    }

    /**
     * Handle removing an existing module from the queue of modules.
     *
     * @access  public
     * @param   string      $moduleName
     * @return  array|bool  Returns module config if found and removed
     */
    public function removeModule($moduleName)
    {
        if (isset($this->_modules[$moduleName])) {
            $module = $this->_modules[$moduleName];
            unset($this->_modules[$moduleName]);
            return $module;
        }

        return false;
    }

    /**
     * Returns the full set of loaded modules.
     *
     * @access  public
     * @return  array
     */
    public function getModules()
    {
        return $this->_modules;
    }

    /**
     * Updates the default module config with user overrides.
     *
     * @access  public
     * @param   array   $modules
     * @return  void
     */
    public function updateConfig($modules)
    {
        $this->_modules = $this->_mergeModuleConfig($this->_modules, $modules);
    }

    /**
     * Handles loading of modules from a given directory. If the config already
     * exists, we merge with existing to handle the scenario of overriding
     * default config values.
     *
     * @access  protected
     * @return  void
     */
    protected function _loadModulesFromDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $it = new RecursiveIteratorIterator(
            new Rebuilder\Loader\Filter(
                new RecursiveDirectoryIterator($dir)
            )
        );

        foreach ($it as $file) {
            $config = include_once($file->getPathname());
            if (!isset($this->_modules[$config['name']])) {
                $this->_modules[$config['name']] = $config;
            } else {
                $this->_modules[$config['name']] =
                    $this->_mergeModuleConfig(
                        $this->_modules[$config['name']],
                        $config
                    );
            }
        }
    }

    /**
     * Handles merging the default module params with the user supplied
     * module params.
     *
     * @access  public
     * @param   array   $default
     * @param   array   $modules
     * @return  array
     */
    protected function _mergeModuleConfig($default, $modules)
    {
        // create a new array because we need to keep the order of modules
        $return = array();

        foreach ($modules as $k => $v) {
            if (array_key_exists($k, $default) && is_array($v)) {
                $return[$k] = $this->_mergeModuleConfig($default[$k], $modules[$k]);
            } else {
                $return[$k] = $v;
            }
        }

        return $return;
    }

    /**
     * Handles autoloading of modules.
     *
     * @access  public
     * @param   string  $className
     * @return  void
     */
    public function moduleLoader($className)
    {
        $filename = explode('_', $className);
        $filename = array_map(function($arr) {
            return ucfirst($arr);
        }, $filename);

        if (count($filename) > 1) {
            $filename = implode('/', $filename);
        } else {
            // if no dir structure, assume file name has same name as module dir
            $filename = $filename[0] . '/' . $filename[0];
        }

        $filepath = $this->_modulePath . $filename . '.php';
        if (!file_exists($filepath)) {
            throw new Exception(
                'The class you have requested, ' . $className .
                ', could not be found at: ' . $filepath
            );
        } else if (!is_readable($filepath)) {
            throw new Exception(
                'The class you have requested, ' . $className .
                ', could not be read at: ' . $filepath
            );
        }

        require_once($filepath);
    }

}