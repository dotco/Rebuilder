<?php
class Rebuilder_Core {

    /**
     * The path to the module directory.
     *
     * @var string
     */
    private $_modulePath;

    /**
     * The default config values which get merged. Also provides the full
     * set of allowable config keys.
     *
     * @var array
     */
    private $_modules = array(
        'csstidy' => array(
            'class' => 'CSSTidy',
            'enabled' => FALSE,
            'config' => array(
                'basepath' => NULL,
                'multi_line' => TRUE,
                'files' => array(),
                'output_file' => NULL
            )
        ),
        'jsmin' => array(
            'class' => 'JSMin',
            'enabled' => FALSE,
            'config' => array(
                'basepath' => NULL,
                'files' => array(),
                'output_file' => NULL
            )
        )
    );

    /**
     * The default constructor for loading configuration settings and
     * setting up the autoloader.
     *
     * @access  public
     * @param   array   $modules
     * @return  void
     */
    public function __construct($modules = array())
    {
        // merge defaults with modules
        if (!empty($modules)) {
            $this->_modules = $this->_mergeModuleConfig($this->_modules, $modules);
        }

        // set the default module path
        $this->_modulePath = __DIR__ . '/modules/';

        // register the autoloader for modules
        spl_autoload_register(array($this, 'moduleLoader'));
    }

    /**
     * Handles running all of the loaded modules. If an exception is encountered,
     * it affects all subsequent modules. We do so because there's no current
     * way to determine if the queue is full of dependencies based on previous
     * modules.
     *
     * @access  public
     * @return  void
     */
    public function run()
    {
        // reusable helper
        $module = null;

        // iterate over each queued module, load, and run
        foreach ($this->_modules as $moduleName => $config) {
            // skip if not enabled
            if (!$config['enabled']) {
                continue;
            }

            try {

                // use the class param for autoloading
                $module = new $config['class']($config['config']);

                // trigger running of the module
                $module->run();

            } catch (Exception $e) {
                $this->_logException($e);
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
            $this->_modules = $this->_mergeModuleConfig($this->_modules, $moduleConfig);
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
        foreach ($modules as $k => $v) {
            if (array_key_exists($k, $default) && is_array($v)) {
                $default[$k] = $this->_mergeModuleConfig($default[$k], $modules[$k]);
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
        $filename = array_map(function($arr) { return ucfirst($arr); }, $filename);
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

    /**
     * Logs a message to the error log.
     *
     * @access  public
     * @param   $msg
     * @return  void
     */
    public function log($msg)
    {
        error_log($msg);
    }

    /**
     * Handles the logging of an exception to be handled by user.
     *
     * @access  public
     * @param   Exception $e
     * @return  void
     */
    protected function _logException($e)
    {
        $this->log('[Exception] ' . $e->getMessage());
        $this->log('[Exception] Line ' . $e->getLine() . ' in ' . $e->getFile());
        $this->log('[Exception] ' . print_r($e->getTrace(), true));
    }
}
