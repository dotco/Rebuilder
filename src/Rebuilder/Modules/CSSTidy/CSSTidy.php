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
	 * Whether to combine CSS files into a single file without any form
	 * of minification. The combined files will take the output_file name
	 * and end with ".compressed.css" as opposed to ".min.css"
	 * @var	bool
	 */
	private $combine_files = FALSE;

	/**
	 * Whether to minify the files.
	 * @var	bool
	 */
	private $minify_files = FALSE;

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
	 * Default constructor for setting up configuration options. It's not pretty
	 * checking for appropriate options, but it's necessary.
	 *
	 * @access	public
	 * @param	array   $config
	 * @return	void
	 */
	public function __construct($config = array())
	{
		// check for the base full path to the files
		if (!empty($config['basepath']) && is_dir($config['basepath'])) {
			$this->basepath = $config['basepath'];
		}

		// there's the potential for output_path to override basepath
		// when we have single file compression and minification
		if (!empty($config['output_path']) && is_dir($config['output_path'])) {
			$this->output_path = $config['output_path'];
		} else if ($this->basepath) {
			$this->output_path = $this->basepath;
		}

		if (!is_writable($this->output_path)) {
			$this->log('[CSSTidy] Output path not writable: ' . $this->output_path);
			$this->log('[CSSTidy] Skipping minification');
			return false;
		}

		// set the output file
		if (!empty($config['output_file'])) {
			if (!$this->setOutputFile($config['output_file'])) {
				$this->log('[CSSTidy] Skipping compression');
				return false;
			}
		}

		if (isset($config['multi_line'])) {
			$this->multi_line = (bool) $config['multi_line'];
		}

		// add files with validation
		if (!empty($config['files']) && is_array($config['files'])) {
			foreach ($config['files'] as $file) {
				$this->addFile($file);
			}
		}

		if (!empty($config['find_replace']) && is_array($config['find_replace'])) {
			$this->find_replace = $config['find_replace'];
		}

		if (!empty($config['combine_files'])) {
			$this->combine_files = TRUE;
		}

		if (!empty($config['minify_files'])) {
			$this->minify_files = TRUE;
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
			if ($this->combine_files) {
				$this->combineFiles();
			}

			// handle minification of files
			if ($this->minify_files) {
				$this->minifyFiles();
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
		// we can't handle remote output files
		if (empty($this->output_path)
			|| strpos($file, 'http') === 0
			|| strpos($file, '//') === 0) {
			$this->log('[CSSTidy] Cannot set output file to a remote path.');
			return false;
		}

		// handle adding the basepath to the file
		if (strpos($file, $this->output_path) === false) {
			$file = rtrim($this->output_path, DIRECTORY_SEPARATOR)
				. DIRECTORY_SEPARATOR . ltrim($file, DIRECTORY_SEPARATOR);
		}

		if (!empty($file)
			&& substr(strrchr($file, '.'), 1) == "css"
			&& ((is_file($file) && is_writable($file)) || is_writable(dirname($file)))) {
				// set the output file
				$this->output_file = $file;
				$this->last_modified = (int) @filemtime($this->output_file);
				$this->log('[CSSTidy] Set output file to ' . $file . '.');

				// now see if we need to create compressed and minified file paths
				if ($this->minify_files) {
					$this->output_file_min =
						dirname($this->output_file) . DIRECTORY_SEPARATOR
						. basename($this->output_file, '.css')
						. '.' . self::MINIFY_SUFFIX . '.css';

					$this->last_modified =
						min($this->last_modified, (int) @filemtime($this->output_file_min));

					$this->log('[CSSTidy] Set output minification file to ' . $this->output_file_min);
				}

				if ($this->combine_files) {
					$this->output_file_comb =
						dirname($this->output_file) . DIRECTORY_SEPARATOR
						. basename($this->output_file, '.css')
						. '.' . self::COMBINE_SUFFIX . '.css';

					$this->last_modified =
						min($this->last_modified, (int) @filemtime($this->output_file_min));

					$this->log('[CSSTidy] Set output combination file to ' . $this->output_file_comb);
				}

				return true;
		}

		$this->log('[CSSTidy] Could not set output file to ' . $file . '.');
		if (substr(strrchr($file, '.'), 1) == "css") {
			$this->log('[CSSTidy] Reason: File does not end in .css');
		} else if (is_file($file) && !is_writable($file)) {
			$this->log('[CSSTidy] Reason: File exists but isnt writable');
		} else if (!is_writable(dirname($file))) {
			$this->log('[CSSTidy] Reason: File does not exist and parent file directory is not writable');
		}

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
			// check if the file already contains the full output_path
			if (strpos($file, $this->output_path) === FALSE) {
				$file = rtrim($this->output_path, DIRECTORY_SEPARATOR)
					. DIRECTORY_SEPARATOR . ltrim($file, DIRECTORY_SEPARATOR);
			}
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
		if (substr(strrchr($file, '.'), 1) == "css") {
			$this->log('[CSSTidy] Reason: File does not end in .css');
		} else if (!is_file($file)) {
			$this->log('[CSSTidy] Reason: File does not exist');
		} else if (!is_readable($file)) {
			$this->log('[CSSTidy] Reason: File is not readable');
		}

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
				$modified = (int) @filemtime($file);
				if ($modified && $modified > $max_modified) {
					$max_modified = $modified;
				}
			}
		}

		if ($this->last_modified === 0
			|| $max_modified > $this->last_modified) {
			$this->log('[CSSTidy] Rebuild required.');
			return true;
		}

		$this->log('[CSSTidy] No rebuild required.');
		return false;
	}

	/**
	 * Handles cominging files without any form of minification. This is a simple
	 * method for combining of CSS files but not touching them.
	 *
	 * @access	public
	 * @return	void
	 */
	public function combineFiles()
	{
		$output = '';

		try {

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
				if (file_put_contents($this->output_file_comb, $output)) {
					$this->log('[CSSTidy] Files saved to combined output file ' . $this->output_file_comb . '.');
					return true;
				}
			}

		} catch (Exception $e) {
			$this->log('[CSSTidy] Exception: ' . $e->getMessage());
		}

		return false;
	}

	/**
	 * Wrapper around file minification process.
	 *
	 * @access	public
	 * @return	void
	 */
	public function minifyFiles()
	{
		try {

			// get the compressed string and write to file
			$compressed = $this->mergeFiles()->compressCSS()->getCompressedCSS(true);
			if (!empty($compressed)) {
				$this->log('[CSSTidy] Files have been locally compressed.');
				if (file_put_contents($this->output_file_min, $compressed)) {
					$this->log('[CSSTidy] Files saved to minified output file ' . $this->output_file_min);
				}
			}

		} catch (Exception $e) {
			$this->log('[CSSTidy] Exception: ' . $e->getMessage());
		}

		return false;
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
