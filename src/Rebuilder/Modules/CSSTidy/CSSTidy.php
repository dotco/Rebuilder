<?php
namespace Rebuilder\Modules;
use \Exception as Exception;

/**
 * A PHP class implementing regular expressions for combining and compressing
 * CSS files. Intended to be used as a module within the Rebuilder PHP class
 * for performing CLI based asset management.
 *
 * Credit goes to the original author, JR. His blog post and additional
 * documentation on this class can be found at:
 *
 * http://www.if-not-true-then-false.com/2009/css-compression-with-own-php-class-vs-csstidy/
 *
 * @package		CSSMin
 * @copyright	2012 Corey Ballou <http://go.co>
 * @author		Corey Ballou <corey@go.co>
 * @link		http://github.com/cballou
 */
class CSSTidy extends ModulesAbstract {

	/**
	 * Whether to combine JS files into a single file without any form
	 * of minification. The combined files will take the output_file name
	 * and end with ".compressed.js" as opposed to ".min.js"
	 * @var	bool
	 */
	private $_combine_files = FALSE;

	/**
	 * Whether to minify the files
	 */
	private $_minify_files = FALSE;

	/**
	 * Triggers forcing a rebuild.
	 * @var	bool
	 */
	private $force_rebuild = FALSE;

	/**
	 * Whether you want to output CSS on a single line or multiple lines. This
	 * must be manually changed by you depending on your preference.
	 * @var	bool
	 */
	private $multi_line = TRUE;

	/**
	 * The base directory to the application public directory. Should be a full
	 * path. Used for concatting with the individual file assets.
	 * @var	string
	 */
	private $basepath;

	/**
	 * The path to the output file.
	 * @var	string
	 */
	private $output_path;

	/**
	 * The path to the output file.
	 * @var	string
	 */
	private $output_file;

	/**
	 * Key/val pairs to find and replace in file(s).
	 * @var	array
	 */
	private $find_replace = array();

	/**
	 * The last modified time of the output file.
	 * @var	int
	 */
	private $last_modified;

	/**
	 * An array of files to compress.
	 * @var	array
	 */
	private $files = array();

	/**
	 * The original CSS.
	 * @var string
	 */
	private $original_css;

	/**
	 * The compressed CSS.
	 * @var string
	 */
	private $compressed_css;

	/**
	 * Default constructor for setting up configuration options.
	 *
	 * @access	public
	 * @param	array   $config
	 * @return	void
	 */
	public function __construct($config = array())
	{
		if (isset($config['multi_line'])) {
			$this->multi_line = (bool) $config['multi_line'];
		}

		// check for the base full path to the files
		if (!empty($config['basepath']) && is_dir($config['basepath'])) {
			$this->basepath = $config['basepath'];
		}

		// check for the base output path
		if (!empty($config['output_path']) && is_dir($config['output_path'])) {
			$this->output_path = $config['output_path'];
		} else if ($this->basepath) {
			$this->output_path = $this->basepath;
		}

		// add files with validation
		if (!empty($config['files']) && is_array($config['files'])) {
			foreach ($config['files'] as $file) {
				$this->addFile($file);
			}
		}

		// set the output file
		if (!empty($config['output_file'])) {
			$this->setOutputFile($config['output_file']);
		}

		if (!empty($config['find_replace']) && is_array($config['find_replace'])) {
			$this->find_replace = $config['find_replace'];
		}

		if (!empty($config['combine_files'])) {
			$this->_combine_files = TRUE;
		}

		if (isset($config['force_rebuild'])) {
			$this->force_rebuild = (bool) $config['force_rebuild'];
		}
	}

	/**
	 * Special method for the Rebuilder module which executes and runs
	 * the css minification process.
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function run()
	{
		// determine if we need to run
		if ($this->requiresRebuild()) {
			// simple handler for combining the files
			if ($this->_combine_files) {
				$this->combineFiles();
			}

			// get the compressed string
			$compressed = $this->mergeFiles()->compressCSS()->getCompressedCSS(true);

			// if no errors, write to the output file
			if (!empty($compressed)) {
				$this->log('[CSSTidy] Files compressed.');
				if (isset($this->output_file)) {
					// generate minified filename
					$filename =
						dirname($this->output_file) . DIRECTORY_SEPARATOR
						. basename($this->output_file, '.css')
						. '.min.css';

					if (file_put_contents($this->output_file, $compressed)) {
						$this->log('[CSSTidy] Files saved to output file ' . $this->output_file . '.');
					}
				} else {
					echo $compressed;
				}
			}
		}
	}

	/**
	 * Sets the output file with some error checking.
	 *
	 * @access	public
	 * @param	string	$file
	 * @return	bool
	 */
	public function setOutputFile($file)
	{
		// handle adding the basepath to the file
		if (!empty($this->output_path)
			&& strpos($file, 'http') === false
			&& strpos($file, '//') === false
		) {
			$file = rtrim($this->output_path, DIRECTORY_SEPARATOR)
				. DIRECTORY_SEPARATOR . ltrim($file, DIRECTORY_SEPARATOR);
		} else if (strpos($file, '//') === 0) {
            $file = 'http:' . $file;
        }

		if ($file != null && $file != "" && substr(strrchr($file, '.'), 1) == "css") {
			if ((is_file($file) && is_writable($file)) || is_writable(dirname($file))) {
				$this->output_file = $file;
				$this->last_modified = @filemtime($this->output_file);
				$this->log('[CSSTidy] Set output file to ' . $file . '.');
				return true;
			}
		}

        // try one more thing
        $contents = file_get_contents($file);
        if ($contents) {
            $this->files[] = $contents;
			$this->log('[CSSTidy] Added file ' . $file . '.');
            return true;
        }

		$this->log('[CSSTidy] Could not set output file to ' . $file . '.');
		return false;
	}

	/**
	 * Add a file to be compressed. Only accepts full paths to files.
	 *
	 * @access	public
	 * @param	string	$file
	 * @return	bool
	 */
	public function addFile($file)
	{
		// handle adding the basepath to the file
		if (!empty($this->basepath)
			&& strpos($file, 'http') === false
			&& strpos($file, '//') === false
		) {
			$file = rtrim($this->basepath, DIRECTORY_SEPARATOR)
				. DIRECTORY_SEPARATOR . ltrim($file, DIRECTORY_SEPARATOR);
		} else if (strpos($file, '//') === 0) {
            $file = 'http:' . $file;
        }

		if ($file != null && $file != ""
			&& substr(strrchr($file, '.'), 1) == "css"
			&& is_file($file)
			&& is_readable($file)
		) {
			$this->files[] = $file;
			$this->log('[CSSTidy] Added file ' . $file . '.');
			return true;
		}

		$this->log('[CSSTidy] Could not add file ' . $file . '.');
		return false;
	}

	/**
	 * Handles adding multiples files for compression. Each string in the array
	 * must be a full path to the file.
	 *
	 * @access	public
	 * @param	array|null	$files
	 * @return	bool
	 */
	public function addFiles($files = null)
	{
		$ok = true;
		$files = (array) $files;
		foreach ($files as $file) {
			if (!$this->addFile($file)) {
				$ok = false;
			}
		}

		return $ok;
	}

	/**
	 * Determines if we need to require a rebuild of the output file based
	 * on whether any of the uncompressed files have a newer filemtime
	 * than the compressed file.
	 *
	 * @access	public
	 * @return	bool
	 */
	public function requiresRebuild()
	{
		if ($this->force_rebuild) {
			return true;
		}

		$max_modified = null;

		if (!empty($this->files)) {
			foreach ($this->files as $file) {
				$modified = @filemtime($file);
				if ($modified && $modified > $max_modified) {
					$max_modified = $modified;
				}
			}
		}

		if ($max_modified > $this->last_modified) {
			$this->log('[CSSTidy] Rebuild required.');
			return true;
		}

		$this->log('[CSSTidy] No rebuild required.');
		return false;
	}

	/**
	 * Handles cominging files without any form of minification. This is a simple
	 * method for combining of JS files but not touching them.
	 *
	 * @access	public
	 * @return	void
	 */
	public function combineFiles()
	{
		$output = '';

		foreach ($this->files as $file) {
			$contents = null;

            // check if we need retrieval by URL
            if (strpos($file, 'http') === 0) {
                $contents = file_get_contents($file);
            } else {
                $fh = fopen($file, 'r');
                $contents = fread($fh, filesize($file));
                fclose($fh);
            }

            // if we don't have contents, continue
			if (!$contents) {
                $this->log('[CSSTidy] Error, contents not found in ' . $file . '.');
                continue;
			}

			// find and replace any necessary strings
			$contents = $this->findAndReplace($contents);

			// add contents to output
			$output .= $contents;
		}

		// if no errors, write to the output file
		if (!empty($output)) {
			$this->log('[CSSTidy] Files combined into single string.');
			if (isset($this->output_file)) {
				// determine the combined filename based on the output filename
				$filename =
					dirname($this->output_file) . DIRECTORY_SEPARATOR
					. basename($this->output_file, '.css')
					. '.compressed.css';

				if (file_put_contents($filename, $output)) {
					$this->log('[CSSTidy] Files saved to output file ' . $filename . '.');
				}
			}
		}
	}

	/**
	 * Handles find and replace in CSS files.
	 *
	 * @access	public
	 * @param	string	$contents
	 * @return	string
	 */
	public function findAndReplace($contents)
	{
		// if find and replace enabled
		if (empty($this->find_replace)) {
			return $contents;
		}

		return str_replace(
			array_keys($this->find_replace),
			array_values($this->find_replace),
			$contents
		);
	}

	/**
	 * Used to generate the concatenated version of the original CSS files.
	 *
	 * @access	public
	 * @return	void
	 */
	public function mergeFiles()
	{
		foreach ($this->files as $file) {
			$contents = null;

            // check if we need retrieval by URL
            if (strpos($file, 'http') === 0) {
                $contents = file_get_contents($file);
            } else {
                $fh = fopen($file, 'r');
                $contents = fread($fh, filesize($file));
                fclose($fh);
            }

			if (empty($contents)) {
				$this->log('[CSSTidy] Error, contents not found in ' . $file . '.');
				continue;
			}

			// handle find and replace in contens
			$contents = $this->findAndReplace($contents);

			// append to unminified css
			$this->original_css .= $contents;
		}

		// log the merge
		$this->log('[CSSTidy] Original css files merged.');

		// allow chaining
		return $this;
	}

	/**
	 * Prints the original CSS files concatenated. Optionally allows for
	 * adding the appropriate text/css content type header.
	 *
	 * @access	public
	 * @param	bool	$return
	 * @param	bool	$headers
	 * @return	void
	 */
	public function getOriginalCSS($return = false, $headers = false)
	{
		if ($return === true) {
			return $this->original_css;
		}

		if ($headers === true) {
			header('Content-type: text/css');
		}

		echo $this->original_css;
	}

	/**
	 * Prints the compressed CSS files concatenated. Optionally allows for
	 * adding the appropriate text/css content type header.
	 *
	 * @access	public
	 * @param	bool	$return
	 * @param	bool	$headers
	 * @return	void
	 */
	public function getCompressedCSS($return = false, $headers = false)
	{
		if ($return === true) {
			return $this->compressed_css;
		}

		if ($headers === true) {
			header('Content-type: text/css');
		}

		echo $this->compressed_css;
	}

	/**
	 * Handles compressing all included CSS files that have already been
	 * concatenated.
	 *
	 * @access	public
	 * @return	void
	 */
	public function compressCSS()
	{
		$patterns = array();
		$replacements = array();

		// remove multi-line comments
		$patterns[] = '/\/\*.*?\*\//s';
		$replacements[] = '';

		// remove multiple newlines
		$patterns[] = '/\n+|\r+/';
		$replacements[] = "\n";

		// remove tabs
		$patterns[] = '/\t/';
		$replacements[] = ' ';

		// remove tabs, spaces, newlines, etc
		$patterns[] = '/\r\n|\r|\n|\t|\s\s+/';
		$replacements[] = '';

		// remove whitespace on both sides of colons
		$patterns[] = '/\s?\:\s?/';
		$replacements[] = ':';

		// remove whitespace on both sides of curly brackets
		$patterns[] = '/\s?\{\s?/';
		$replacements[] = '{';
		$patterns[] = '/\s?\}\s?/';
		$replacements[] = '}';

		// remove whitespace on both sides of commas
		$patterns[] = '/\s?\,\s?/';
		$replacements[] = ',';

		// add newlines back in
		if ($this->multi_line === TRUE) {
			$patterns[] = '/\}/';
			$replacements[] = "}\n";
		}

		// given all regex rules, perform the compression
		$this->compressed_css = preg_replace($patterns, $replacements, $this->original_css);

		// allow chaining
		return $this;
	}
}
