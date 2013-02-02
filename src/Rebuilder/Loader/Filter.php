<?php
namespace Rebuilder\Loader;

/**
 * A custom module loader filter which aids in the process of recursively finding
 * all module configuration files of the name "config.php".
 */
class Filter extends \RecursiveFilterIterator
{

    /**
     * The default constructor.
     *
     * @access  public
     * @return  void
     */
    public function __construct($iterator)
    {
    	parent::__construct($iterator);
    }

    /**
     * A function which returns boolean depending on whether the filename matches
     * config.php. Also accepts directories to continue iteration.
     *
     * @access  public
     * @return  bool
     */
    public function accept()
    {
    	return
			$this->hasChildren() ||
			(
				$this->current()->isFile()
				&& ($this->current()->getFilename() == 'config.php')
			);
    }

    /**
     * Retruns the filename in question.
     *
     * @access  public
     * @return  string
     */
    public function __toString()
    {
    	return $this->current()->getFilename();
    }

}
