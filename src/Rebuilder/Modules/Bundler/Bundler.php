<?php
namespace Rebuilder\Modules;

/**
 * Bundler is an advanced implementation of the CSSTidy, Gzip, JSMin, and S3 modules.
 * It combines all four modules into a singular tool which can create media/asset
 * "bundles" for your website. For our purposes, a "bundle" is a named set of
 * combined assets. For each bundle, the following things are created:
 *
 *   - The unminified and uncompressed version of the combined files
 *   - The unminified and compressed version of the combined files
 *   - The minified and uncompressed version of the combined files
 *   - The minified and compressed version of the combined files
 *
 * These four different variations can then be used by the client-side version
 * of Bundler in your application to serve up all sorts of variations which may
 * be required by your different application environments.
 *
 * @package		Bundler
 * @copyright	2013 .CO Internet <http://go.co>
 * @author		Corey Ballou <corey@go.co>
 * @link		http://github.com/dotco/Rebuilder
 */
class Bundler {

	/**
	 * Default constructor for setting up configuration options.
	 *
	 * @access	public
	 * @param	array   $config
	 * @return	void
	 */
	public function __construct($config = array())
	{

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

    }

}
