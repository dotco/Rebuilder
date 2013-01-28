<?php
namespace Rebuilder\Modules;

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
class Gzip implements ModulesAbstract {

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
        // base extensions
        $baseExts = $ext;

		// turn file extensions into regex
		$ext = str_replace('.', '\.', $ext);
		$ext = implode('|', $ext);
		$regex = '#(?<!/)(' . $ext . ')$|^[^\.]*$#i';

		// return files
		$files = array();

		// create the iterator
		$it =
		new RecursiveIteratorIterator(
			new RecursiveRegexIterator(
				new RecursiveDirectoryIterator(
					$dir,
					RecursiveDirectoryIterator::KEY_AS_PATHNAME
				),
				$regex,
				RegexIterator::MATCH
			),
			RecursiveIteratorIterator::SELF_FIRST
		);

		// iterate over dir
		foreach ($it as $dir => $info) {
			// skip directories
			if ($info->isDir()) {
				continue;
			}

            // skip files that have already been gzipped
            foreach ($baseExts as $baseExt) {
                if (strpos($info->getFilename(), '.gz' . $baseExt)) {
                    // we don't want to deal with gzipped files
                    continue 2;
                }
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
