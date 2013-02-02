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
        $this->_defaultModulePath = __DIR__ . '/Modules/';
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
     * @return  void
     */
    public function loadCustom()
    {
        if (!empty($this->_customModulePaths)) {
            foreach ((array) $this->_customModulePaths as $path) {
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
        $this->log('Attempting to load module config from directory: ' . $dir);

        if (!is_dir($dir)) {
            $this->log('Module directory doesnt exist: ' . $dir);
            return;
        }

        $it = new \RecursiveIteratorIterator(
            new \Rebuilder\Loader\Filter(
                new \RecursiveDirectoryIterator($dir)
            )
        );

        foreach ($it as $file) {
            $config = include_once($file->getPathname());
            if (!isset($this->_modules[$config['name']])) {
                $this->log('Initial module config import: ' . $config['name']);
                $this->_modules[$config['name']] = $config;
            } else {
                $this->log('Overriding module config: ' . $config['name']);
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
     * @param   array   $default    The currently existing module config
     * @param   array   $override    The new module config (override)
     * @return  array
     */
    protected function _mergeModuleConfig($default, $override)
    {
        foreach ($override as $k => $v) {
            if (isset($default[$k]) && is_array($v)) {
                $default[$k] = $this->_mergeModuleConfig($default[$k], $override[$k]);
            } else {
                $default[$k] = $v;
            }
        }

        return $default;
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
        $filename .= '.php';

        // check for file existance across all module paths
        $modulePaths = array($this->_defaultModulePath);
        if (!empty($this->_customModulePaths)) {
            $modulePaths += $this->_customModulePaths;
        }

        // check for existance in array
        foreach ($modulePaths as $path) {
            $filepath = $path . $filename;
            $this->log('Attempting to load module from path: ' . $filepath);
            if (file_exists($filepath)) {
                require_once($filepath);
                return true;
            }
        }

        $this->log('The class you have requested, ' . $className . ', could not be found.');
        die;
    }

    /**
     * Logs a message to the error log.
     *
     * @access  public
     * @param   $msg
     * @return  void
     */
    public function log($msg)
    {
        echo '[' . date('Y-m-d H:i:s') . '] ' . print_r($msg, true) . PHP_EOL;
    }

}
