<?php
namespace Rebuilder\Modules;

/**
 * An abstract class which simply imposes the necessity to include a run method
 * for each module.
 */
abstract class ModulesAbstract {

    abstract public function run();

    /**
     * A global error logger for modules. Can be enabled or disabled on a
     * per module basis depending on whether the module's configuration
     * options enabled logging.
     *
     * @access  public
     * @param   mixed   $msg
     * @return  void
     */
    public function log($msg)
    {
        /*
        if ($this->_enableLogging) {
            error_log(print_r($msg, true));
        }
        */

        error_log(print_r($msg, true));
    }

}
