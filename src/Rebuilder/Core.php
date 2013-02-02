<?php
namespace Rebuilder;

/**
 * The core rebuilder class which handles the loading of modules.
 */
class Core {

    /**
     * The default constructor for loading configuration settings and
     * setting up the autoloader.
     *
     * @access  public
     * @param   array   $modules
     * @param   array   $customPaths    Full paths to custom module directories
     * @return  void
     */
    public function __construct($modules = array(), $customPaths = array())
    {
        // pull in all module default config values
        $this->loader = new \Rebuilder\Loader($customPaths);

        // merge defaults with modules
        if (!empty($modules)) {
            $this->loader->updateConfig($modules);
        }
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
        // reusable
        $module = null;

        // iterate over each queued module, load, and run
        $modules = $this->loader->getModules();
        foreach ($modules as $moduleName => $config) {
            /*
            // skip if module not enabled
            if (!isset($config['enabled']) || $config['enabled'] === FALSE) {
                $this->log('Module ' . $moduleName . ' not enabled. Skipping.');
                continue;
            }
            */

            try {

                $this->log('Running module: ' . $moduleName);
                //$this->log('Module config settings:');
                //$this->log($config);

                // use the class param for autoloading
                $module = new $config['class']($config['config'], $this->loader);

                // trigger running of the module
                $module->run();

            } catch (Exception $e) {
                $this->_logException($e);
            }
        }
    }

    /**
     * Return an instance of the module loader.
     *
     * @access  public
     * @return  Rebuilder\Loader
     */
    public function getLoader()
    {
        return $this->loader;
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
