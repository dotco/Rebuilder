<?php
namespace Rebuilder\Modules;
use \Exception as Exception;

/**
 * A PHP class implementing gzipping of file assets. For storage, the file is
 * simply renamed to .gz.ext for the gzipped copy. Currently only supports
 * CSS and JS.
 *
 * The idea for gzipping for S3 purposes came from:
 * http://clickontyler.com/blog/2008/05/using-amazon-s3-as-a-content-delivery-network/
 *
 * @package		Gzip
 * @copyright	2012 Corey Ballou <http://go.co>
 * @author		Corey Ballou <corey@go.co>
 * @link		http://github.com/cballou
 */
class Gzip extends ModulesAbstract {

	/**
	 * The CSS directory we use for outputting gzipped files.
	 * @var	string
	 */
	public $cssDir = NULL;

	/**
	 * The JS directory we use for outputting gzipped files.
	 * @var	string
	 */
	public $jsDir = NULL;

	/**
	 * Default constructor for setting up configuration options.
	 *
	 * @access	public
	 * @param	null|string		$original_css
	 * @return	void
	 */
	public function __construct($config = array())
	{
		// check for a type limiter
		$type = array('css', 'js', 'img', 'font');
		if (!empty($config['type']) && is_array($config['type'])) {
			$type = $config['type'];
		}

		if (!empty($config['cssDir'])
			&& is_readable($config['cssDir'])
			&& in_array('css', $type)
		) {
			$this->cssDir = $config['cssDir'];
		}

		if (!empty($config['jsDir'])
			&& is_readable($config['jsDir'])
			&& in_array('js', $type)
		) {
			$this->jsDir = $config['jsDir'];
		}
	}

	/**
	 * Required function by Rebuilder for executing any requirements.
	 *
	 * @access	public
	 * @param
	 */
	public function run()
	{
		$files = array();

		if (!empty($this->cssDir)) {
			$exts = array('.css');
			$files = array_merge($files, $this->_findFilesRecursive($this->cssDir, $exts));
		}

		if (!empty($this->jsDir)) {
			$exts = array('.js');
			$files = array_merge($files, $this->_findFilesRecursive($this->jsDir, $exts));
		}

		// handle uploads all at once
		$this->_gzipFiles($files);
	}

    /**
     * Handles gzipping of files.
     *
     * @access  public
     * @param   array   $files
     */
    protected function _gzipFiles($files)
    {
		if (!empty($files)) {
			foreach ($files as $f) {
				// skip if already has .gz in extension
				if (strpos($f['filepath'], '.gz.css') !== FALSE) {
					continue;
				} else if (strpos($f['filepath'], '.gz.js') !== FALSE) {
					continue;
				}

                // generate the new name
                $ext = substr($f['filepath'], strrpos($f['filepath'], '.') + 1);
                $newPath = str_replace('.' . $ext, '.gz.' . $ext, $f['filepath']);

                try {

                    // handle gzipping
                    error_log('Gzipping file: ' . $newPath);
                    shell_exec('gzip -9 -c ' . $f['filepath'] . ' > ' . $newPath);

                } catch (Exception $e) {
                    error_log($e->getMessage());
                }
            }
        }
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
