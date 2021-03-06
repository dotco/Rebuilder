<?php
namespace Rebuilder\Modules;
use \Exception as Exception;

/**
 * jsmin.php - PHP implementation of Douglas Crockford's JSMin.
 *
 * This is pretty much a direct port of jsmin.c to PHP with just a few
 * PHP-specific performance tweaks. Also, whereas jsmin.c reads from stdin and
 * outputs to stdout, this library accepts a string as input and returns another
 * string as output.
 *
 * PHP 5 or higher is required.
 *
 * Permission is hereby granted to use this version of the library under the
 * same terms as jsmin.c, which has the following license:
 *
 * --
 * Copyright (c) 2002 Douglas Crockford  (www.crockford.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * The Software shall be used for Good, not Evil.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * --
 *
 * @package JSMin
 * @author Ryan Grove <ryan@wonko.com>
 * @copyright 2002 Douglas Crockford <douglas@crockford.com> (jsmin.c)
 * @copyright 2008 Ryan Grove <ryan@wonko.com> (PHP port)
 * @copyright 2012 Adam Goforth <aag@adamgoforth.com> (Updates)
 * @copyright 2012 Corey Ballou <corey@go.co> (Updates for Rebuilder)
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @version 1.1.2 (2012-05-01)
 * @link https://github.com/rgrove/jsmin-php
 */

class JSMin extends ModulesAbstract {

	const MINIFY_SUFFIX		= 'min';
	const COMBINE_SUFFIX	= 'compressed';

    const ORD_LF            = 10;
    const ORD_SPACE         = 32;
    const ACTION_KEEP_A     = 1;
    const ACTION_DELETE_A   = 2;
    const ACTION_DELETE_A_B = 3;

    protected $a           = '';
    protected $b           = '';
    protected $input       = '';
    protected $inputIndex  = 0;
    protected $inputLength = 0;
    protected $lookAhead   = null;
    protected $y           = null;
    protected $x           = null;
    protected $output      = '';

    protected $atMaxDebugLength = false;
    protected $debugStr = '';

	/**
	 * Whether to combine JS files into a single file without any form
	 * of minification. The combined files will take the output_file name
	 * and end with ".compressed.js" as opposed to ".min.js"
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
	 * The path to the output file(s). We generate both minified and compressed
	 * paths.
	 * @var	string
	 */
	private $output_file;
	private $output_file_min;
	private $output_file_comb;

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
	 * The compressed JS.
	 * @var string
	 */
	private $compressed_js;

	/**
	 * An array of files to compress.
	 * @var	array
	 */
	private $files = array();

  /**
   * Constructor
   *
   * @access    public
   * @package   array   $config
   * //@param string $input Javascript to be minified
   */
    public function __construct($config = array()) //$input)
    {
		if (empty($config['output_path'])) {
			$this->log('[JSMin] You must specify an output_path.');
			$this->log('[JSMin] Skipping minification');
			return false;
		} else if (!is_dir($config['output_path'])) {
			$this->log('[JSMin] The output_path does not exist: ' . $config['output_path']);
			$this->log('[JSMin] Skipping minification');
			return false;
		} else if (!is_writable($config['output_path'])) {
			$this->log('[JSMin] Output path not writable: ' . $this->output_path);
			$this->log('[JSMin] Skipping minification');
			return false;
		} else if (empty($config['basepath']) || !is_dir($config['basepath'])) {
			$this->log('[JSMin] Basepath doesnt exist: ' . $this->output_path);
			$this->log('[JSMin] Skipping minification');
			return false;
		}

		$this->output_path = $config['output_path'];
		$this->basepath = $config['basepath'];

		if (!empty($config['find_replace']) && is_array($config['find_replace'])) {
			$this->find_replace = $config['find_replace'];
		}

		if (!empty($config['combine'])) {
			$this->combine_files = TRUE;
		}

		if (!empty($config['minify'])) {
			$this->minify_files = TRUE;
		}

		if (isset($config['force_rebuild'])) {
			$this->force_rebuild = (bool) $config['force_rebuild'];
		}

		// set the output file
		if (!empty($config['output_file'])) {
			if (!$this->setOutputFile($config['output_file'])) {
				$this->log('[JSMin] Skipping minification');
				return false;
			}
		}

		// add files with validation
		if (!empty($config['files']) && is_array($config['files'])) {
			foreach ($config['files'] as $file) {
				$this->addFile($file);
			}
		}
    }

	/**
	 * Special method for the Rebuilder module which executes and runs
	 * the js minification process.
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
			$this->log('[JSMin] Cannot set output file to a remote path.');
			return false;
		}

		// handle adding the output_path to the file
		if (strpos($file, $this->output_path) === false) {
			$file = rtrim($this->output_path, DIRECTORY_SEPARATOR)
				. DIRECTORY_SEPARATOR . ltrim($file, DIRECTORY_SEPARATOR);
		}

		if (!empty($file)
			&& substr(strrchr($file, '.'), 1) == "js"
			&& ((is_file($file) && is_writable($file)) || is_writable(dirname($file)))) {
				// set the output file
				$this->output_file = $file;
				$this->last_modified = (int) @filemtime($this->output_file);
				$this->log('[JSMin] Set output file to ' . $file . '.');

				// now see if we need to create compressed and minified file paths
				if ($this->minify_files) {
					$this->output_file_min =
						dirname($this->output_file) . DIRECTORY_SEPARATOR
						. basename($this->output_file, '.js')
						. '.' . self::MINIFY_SUFFIX . '.js';

					$this->last_modified =
						min($this->last_modified, (int) @filemtime($this->output_file_min));

					$this->log('[JSMin] Set output minification file to ' . $this->output_file_min);
				}

				if ($this->combine_files) {
					$this->output_file_comb =
						dirname($this->output_file) . DIRECTORY_SEPARATOR
						. basename($this->output_file, '.js')
						. '.' . self::COMBINE_SUFFIX . '.js';

					$this->last_modified =
						min($this->last_modified, (int) @filemtime($this->output_file_min));

					$this->log('[JSMin] Set output combination file to ' . $this->output_file_comb);
				}

				return true;
		}

		$this->log('[JSMin] Could not set output file to ' . $file);
		if (substr(strrchr($file, '.'), 1) != "js") {
			$this->log('[JSMin] Reason: File does not end in .js');
		} else if (is_file($file) && !is_writable($file)) {
			$this->log('[JSMin] Reason: File exists but isnt writable');
		} else if (!is_writable(dirname($file))) {
			$this->log('[JSMin] Reason: File does not exist and parent file directory is not writable');
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
		if (empty($file)) {
			$this->log('[JSMin] Filename cant be empty.');
			return false;
		}

		if (strpos($file, '//') === 0) {
			$file = 'http:' . $file;
		}

		/*
		// handle adding the output_path to the file
		if (!empty($this->output_path)
			&& strpos($file, 'http') === false
			&& strpos($file, '//') === false
		) {
			$file = rtrim($this->output_path, DIRECTORY_SEPARATOR)
				. DIRECTORY_SEPARATOR . ltrim($file, DIRECTORY_SEPARATOR);
		} else if (strpos($file, '//') === 0) {
            $file = 'http:' . $file;
        }
		*/

		if (substr(strrchr($file, '.'), 1) == "js") {
			if (is_file($file) && is_readable($file)) {
				$this->files[] = $file;
				$this->log('[JSMin] Added file ' . $file);
				return true;
			} else if (strpos($file, 'http') === 0) {
				// sucks to duplicate, but we need to check
				if (file_get_contents($file)) {
					$this->files[] = $file;
					$this->log('[JSMin] Added file ' . $file);
					return true;
				}
			}
		}

		$this->log('[JSMin] Could not add file ' . $file);
		if (substr(strrchr($file, '.'), 1) != "js") {
			$this->log('[JSMin] Reason: File does not end in .js');
		} else if (!is_file($file)) {
			$this->log('[JSMin] Reason: File does not exist');
		} else if (!is_readable($file)) {
			$this->log('[JSMin] Reason: File is not readable');
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
			$this->log('[JSMin] Forced rebuild.');
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
			$this->log('[JSMin] Rebuild required.');
			return true;
		}

		$this->log('[JSMin] No rebuild required.');
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
					$this->log('[JSMin] Error, contents not found in ' . $file . '.');
					continue;
				}

				// find and replace any necessary strings
				$contents = $this->findAndReplace($contents);

				// add contents to output
				$output .= $contents;
			}

			// if no errors, write to the output file
			if (!empty($output)) {
				$this->log('[JSMin] Files combined into single string.');
				if (file_put_contents($this->output_file_comb, $output)) {
					$this->log('[JSMin] Files saved to combined output file ' . $this->output_file_comb);
					return true;
				}
			}

		} catch (Exception $e) {
			$this->log('[JSMin] Exception: ' . $e->getMessage());
		}

		return false;
	}

	/**
	 * Wrapper to minify files.
	 *
	 * @access	public
	 * @return	void
	 */
	public function minifyFiles()
	{
		try {

			// get the compressed string
			$compressed = $this->minifyAndMergeFiles()->getCompressedJS(true);

			// if no errors, write to the output file
			if (!empty($compressed)) {
				$this->log('[JSMin] Files have been locally compressed.');
				if (file_put_contents($this->output_file_min, $compressed)) {
					$this->log('[JSMin] Files saved to minified output file ' . $this->output_file_min);
				}
			}

		} catch (Exception $e) {
			$this->log('[JSMin] Exception: ' . $e->getMessage());
		}

		return false;
	}

	/**
	 * Handles find and replace in JS files.
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
	 * Used to generate the concatenated and compressed version of the original
	 * JS files. Given that some JS files are already minified, we need to
	 * perform compression prior to concatenation if the file doesn't match
	 * "min."
	 *
	 * @access	public
	 * @return	void
	 */
	public function minifyAndMergeFiles()
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

            // if we don't have contents, continue
			if (empty($contents)) {
                $this->log('[JSMin] Error, contents not found in ' . $file . '.');
                continue;
			}

			// handle find and replace in contens
			$contents = $this->findAndReplace($contents);

            // check if we need to minify
            if (strpos($file, 'min.') !== FALSE) {
				$this->log('[JSMin] File already minified.');

                // merge with no minification
                $this->compressed_js .= $contents;
            } else {
                $this->log('[JSMin] Minifying file ' . $file . '.');

                // reset JSMin
                $this->reset();

                // set as input and minify
                $this->input = str_replace("\r\n", "\n", $contents);
                $this->inputLength = strlen($this->input);
                $this->compressed_js .= $this->min();
            }

            // indicate merge
            $this->log('[JSMin] Appended file ' . $file);
		}

		// log the merge
		$this->log('[JSMin] All JS files concatted.');

		// allow chaining
		return $this;
	}

	/**
	 * Prints the compressed JS files concatenated. Optionally allows for
	 * adding the appropriate text/javascript content type header.
	 *
	 * @access	public
	 * @param	bool	$return
	 * @param	bool	$headers
	 * @return	void
	 */
	public function getCompressedJS($return = false, $headers = false)
	{
		if ($return === true) {
			return $this->compressed_js;
		}

		if ($headers === true) {
			header('Content-type: text/javascript');
		}

		echo $this->compressed_js;
	}

    /**
     * Resets JSMin back to it's default state once initialized. Used so that
     * we can parse multiple JS files in succession.
     *
     * @access  public
     * @return  void
     */
    public function reset()
    {
        $this->a = '';
        $this->b = '';
        $this->input = '';
        $this->inputIndex = 0;
        $this->inputLength = 0;
        $this->lookAhead = null;
        $this->y = null;
        $this->x = null;
        $this->output = null;
    }

	// -------------------------------------------------------------------------
	// -- Protected Instance Methods -------------------------------------------
	// -------------------------------------------------------------------------

	/**
	 * Action -- do something! What to do is determined by the $command argument.
	 *
	 * action treats a string as a single character. Wow!
	 * action recognizes a regular expression if it is preceded by ( or , or =.
	 *
	 * @uses 	next()
	 * @uses 	get()
	 * @throws 	Exception	If parser errors are found:
	 *         - Unterminated string literal
	 *         - Unterminated regular expression set in regex literal
	 *         - Unterminated regular expression literal
	 * @param 	int 	$command 	One of class constants:
	 *      ACTION_KEEP_A      Output A. Copy B to A. Get the next B.
	 *      ACTION_DELETE_A    Copy B to A. Get the next B. (Delete A).
	 *      ACTION_DELETE_A_B  Get the next B. (Delete B).
	*/
	protected function action($command)
	{
		switch ($command) {
			case self::ACTION_KEEP_A:
				$this->output .= $this->a;
				if ($this->a == $this->b
					&& ($this->a == '+' || $this->a == '-')
					&& $this->y != $this->a) {
					$this->output .= ' ';
				}

			case self::ACTION_DELETE_A:
				$this->a = $this->b;

				if ($this->a === "'" || $this->a === '"') {
					for (;;) {
						$this->output .= $this->a;
						$this->a = $this->get();

						if ($this->a === $this->b) {
							break;
						}

						//if (ord($this->a) <= self::ORD_LF) {
						if ($this->a === null) {
							throw new Exception("Unterminated string literal.\n\n" . $this->debugStr);
						}

						if ($this->a === '\\') {
							$this->output .= $this->a;
							$this->a = $this->get();
						}
					}
				}

			case self::ACTION_DELETE_A_B:
				$this->b = $this->next();

				if ($this->b === '/' && (
					$this->a === '(' || $this->a === ',' || $this->a === '=' ||
					$this->a === ':' || $this->a === '[' || $this->a === '!' ||
					$this->a === '&' || $this->a === '|' || $this->a === '?' ||
					$this->a === '{' || $this->a === '}' || $this->a === ';' ||
					$this->a === "\n" )
				) {

					$this->output .= $this->a . $this->b;

					for (;;) {
						$this->a = $this->get();

						if ($this->a === '[') {
							/*
							inside a regex [...] set, which MAY contain a '/' itself.
							Example: mootools Form.Validator near line 460:
							return Form.Validator.getValidator('IsEmpty').test(element) || ...
							*/
							for (;;) {
								$this->output .= $this->a;
								$this->a = $this->get();

								if ($this->a === ']') {
									break;
								} elseif ($this->a === '\\') {
									$this->output .= $this->a;
									$this->a       = $this->get();
								//} elseif (ord($this->a) <= self::ORD_LF) {
								} elseif ($this->a === null) {
									throw new Exception("Unterminated set in Regular Expression literal.\n\n" . $this->debugStr);
								}
							}
						} elseif ($this->a === '/') {
							break;
						} elseif ($this->a === '\\') {
							$this->output .= $this->a;
							$this->a       = $this->get();
						//} elseif (ord($this->a) <= self::ORD_LF) {
						} elseif ($this->a === null) {
							throw new Exception("Unterminated Regular Expression literal.\n\n" . $this->debugStr);
						}

						$this->output .= $this->a;
					}

					$this->b = $this->next();
				}

			default:
				break;
		}
	}

	/**
	 * Get next char. Convert ctrl char to space.
	 *
	 * @return string|null
	 */
	protected function get()
	{
		$c = $this->lookAhead;
		$this->lookAhead = null;

		if ($c === null) {
			if ($this->inputIndex < $this->inputLength) {
				$c = substr($this->input, $this->inputIndex, 1);
				$this->inputIndex += 1;
				$this->y = $this->x;
				$this->x = $c;
			} else {
				$c = null;
			}
		}

			// rolling debug helper
			$this->debugStr .= $c;
			if ($this->atMaxDebugLength) {
				$this->debugStr = substr($this->debugStr, 1);
			} else if (strlen($this->debugStr) >= 120) {
				$this->atMaxDebugLength = true;
			}

		if ($c === "\r") {
			return "\n";
		}

		if ($c === null || $c === "\n" || ord($c) >= self::ORD_SPACE) {
			return $c;
		}

		return ' ';
	}

	/**
	 * Is $c a letter, digit, underscore, dollar sign, or non-ASCII character.
	 *
	 * @return bool
	 */
	protected function isAlphaNum($c)
	{
		return ord($c) > 126 || $c === '\\' || preg_match('/^[\w\$]$/', $c) === 1;
	}

  /**
   * Perform minification, return result.
   *
   * @uses 		action()
   * @uses 		isAlphaNum()
   * @uses 		get()
   * @uses 		peek()
   * @return 	string
   */
  protected function min()
  {
		if (0 == strncmp($this->peek(), "\xef", 1)) {
			$this->get();
			$this->get();
			$this->get();
		}

		$this->a = "\n";
		$this->action(self::ACTION_DELETE_A_B);

		while ($this->a !== null) {
			switch ($this->a) {
				case ' ':
					if ($this->isAlphaNum($this->b)) {
						$this->action(self::ACTION_KEEP_A);
					} else {
						$this->action(self::ACTION_DELETE_A);
					}
					break;

				case "\n":
					switch ($this->b) {
						case '{':
						case '[':
						case '(':
						case '+':
						case '-':
						case '!':
						case '~':
							$this->action(self::ACTION_KEEP_A);
							break;

						case ' ':
							$this->action(self::ACTION_DELETE_A_B);
							break;

						default:
							if ($this->isAlphaNum($this->b)) {
								$this->action(self::ACTION_KEEP_A);
							} else {
								$this->action(self::ACTION_DELETE_A);
							}
					}
					break;

				default:
					switch ($this->b) {
						case ' ':
							if ($this->isAlphaNum($this->a)) {
								$this->action(self::ACTION_KEEP_A);
								break;
							}

						  $this->action(self::ACTION_DELETE_A_B);
						  break;

						case "\n":
							switch ($this->a) {
								case '}':
								case ']':
								case ')':
								case '+':
								case '-':
								case '"':
								case "'":
									$this->action(self::ACTION_KEEP_A);
									break;

								default:
									if ($this->isAlphaNum($this->a)) {
										$this->action(self::ACTION_KEEP_A);
									} else {
										$this->action(self::ACTION_DELETE_A_B);
									}
							}
							break;

						default:
							$this->action(self::ACTION_KEEP_A);
							break;
				}
			}
		}

		return $this->output;
	}

	/**
	 * Get the next character, skipping over comments. peek() is used to see
	 *  if a '/' is followed by a '/' or '*'.
	 *
	 * @uses get()
	 * @uses peek()
	 * @throws JSMin_Exception On unterminated comment.
	 * @return string
	 */
	protected function next() {
		$c = $this->get();

		if ($c === '/') {
			switch($this->peek()) {
				case '/':
					for (;;) {
						$c = $this->get();

						if (ord($c) <= self::ORD_LF) {
							return $c;
						}
					}

				case '*':
					$this->get();

					for (;;) {
						switch($this->get()) {
							case '*':
								if ($this->peek() === '/') {
									$this->get();
									return ' ';
								}
								break;

							case null:
								throw new Exception("Unterminated comment:\n\n" . $this->debugStr);
						}
					}

					default:
						return $c;
			}
		}

		return $c;
	}

	/**
	 * Get next char. If is ctrl character, translate to a space or newline.
	 *
	 * @uses get()
	 * @return string|null
	 */
    protected function peek()
	{
        $this->lookAhead = $this->get();
        return $this->lookAhead;
    }

}
