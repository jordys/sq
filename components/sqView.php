<?php

/**
 * Abstract base view class
 *
 * Takes the path of a template file, puts the single passed content array into 
 * it and returns a fully formed html page for saving, displaying, etc...  Also
 * contains a few static helper methods to streamline formatting.
 */

abstract class sqView extends component {
	
	// If full is true the framework will generate header and footer sections.
	// View is the path of the view going to be generated with the view data.
	// Both of these values can be changed at any time.
	public $full, $view;
	
	// FIFO stack of the list of current view clips
	private $clips = array();
	
	// In template variables
	public static $description, $doctype, $title, $language, $favicon, $base,
		$meta, $id, $head, $foot,
		$keywords = array(),
		$scripts  = array('foot' => array(), 'head' => array()), 
		$styles   = array();
	
	public function __construct($options, $view, $data = array(), $full = null) {
		
		// Add static data from the passed in array
		$this->data = $data + $this->data;
		
		// Set object parameters
		$this->options = $options;
		$this->view    = $view;
		$this->full    = $full;
		
		// Setup base template variables from config
		self::$description = $this->options['meta-description'];
		self::$keywords    = $this->options['meta-keywords'];
		self::$title       = $this->options['title'];
		self::$doctype     = $this->options['doctype'];
		self::$language    = $this->options['language'];
		self::$favicon     = $this->options['favicon'];
		self::$base        = $this->options['base'];
	}
	
	// Special overloaded setter that adds data from the layout view into views
	// loaded included
	public function __set($name, $value) {
		$this->data[$name] = $value;
		
		if (is_object($value) && !is_subclass_of($value, 'model')) {
			$value->data += $this->data;
		}
	}
	
	// Renders the template view file. If full is specified the template will
	// include the auto generated header and footer sections. HTML templates 
	// should only include content inside the body tag and omit the body tag and
	// everything outside of it.
	public function render($view = null, $data = array(), $full = null) {
		if ($view === null) {
			$view = $this->view;
		}
		
		if ($full === null) {
			$full = $this->full;
		}
		
		if ($full) {
			$this->full = false;
		}
		
		$rendered = $this->renderTemplate($view, $data);
		
		if ($this->layout) {
			if (is_string($this->layout)) {
				$this->layout = sq::view($this->layout);
			}
			
			$this->layout->content = $rendered;
			$this->layout->full = true;
			
			$rendered = $this->layout;
		}
		
		if ($full) {
			if (self::$head !== false) {
				$rendered = $this->formatHead().$rendered;
			}
			
			if (self::$foot !== false) {
				$rendered = $rendered.$this->formatFoot();
			}
		}
		
		return $rendered;
	}
	
	// Utility function to render a template
	private function renderTemplate($view, $data = false) {
		
		// Make variables base level items in the content array
		foreach ($this->data as $key => $val) {
			$$key = $val;
		}
		unset($key, $val);
		
		// Config variable is available in template
		$config = sq::config();
		
		// Variable for root path of website
		$base = sq::base();
		
		// Set data explicity passed into the template to variables. Possibly 
		// overwriting existing variables.
		if (is_array($data)) {
			$params = $data;
			
			foreach ($data as $key => $val) {
				$$key = $val;
			}
			unset($key,$val);
		}
		
		ob_start();
		include $this->getViewPath($view);
		return ob_get_clean();
	}
	
	/**
	 * Finds view file
	 *
	 * This method checks the framework files as well as the app view files as
	 * well as those for any modules currently active. The method returns the
	 * path of the file to be included. If a view file with the same name exists
	 * both in the framework and in the app the app one will be chosen.
	 */
	private function getViewPath($file) {
		if (file_exists(dirname($_SERVER['SCRIPT_FILENAME']).'/views/'.$file.'.php')) {
			$path = dirname($_SERVER['SCRIPT_FILENAME']).'/views/'.$file.'.php';
		} elseif (file_exists(sq::path().'views/'.$file.'.php')) {
			$path = sq::path().'views/'.$file.'.php';
		} else {
			$exploded = explode('/', $file);
			$module = $exploded[0];
			$file = str_replace($module.'/', '', $file);
			
			if (file_exists(dirname($_SERVER['SCRIPT_FILENAME']).'/modules/'.$module.'/views/'.$file.'.php')) {
				$path = dirname($_SERVER['SCRIPT_FILENAME']).'/modules/'.$module.'/views/'.$file.'.php';
			} elseif (file_exists(sq::path().'modules/'.$module.'/views/'.$file.'.php')) {
				$path = sq::path().'modules/'.$module.'/views/'.$file.'.php';
			}
		}
		
		return $path;
	}
	
	// Renders the auto generated head section from the static view parameters.
	protected function formatHead() {
		$head = self::$doctype;                       // Doctype
		$head .= '<html lang="'.self::$language.'">'; // Open html tag
		$head .= '<head>';                            // Open head tag
		
		// HTML title
		$head .= '<title>'.self::$title.'</title>';
		
		self::$description = strip_tags(self::$description);
		self::$description = str_replace("\n", " ", self::$description);
		self::$description = str_replace("  ", " ", self::$description);
		
		// Named meta tags
		$head .= '<meta name="description" content="'.self::$description.'">';
		$head .= '<meta name="keywords" content="'.implode(',', self::$keywords).'">';
		
		// Generic meta variable
		$head .= self::$meta;
		
		// Base tag
		if (self::$base !== false) {
			$head .= '<base href="'.self::$base.'"/>';
		}
		
		// External stylesheets
		foreach (self::$styles as $style) {
			$head .= '<link rel="stylesheet" type="text/css" href="'.$style.'"/>';
		}
		
		// Print head scripts
		foreach (self::$scripts['head'] as $script) {
			$head .= '<script type="text/javascript" src="'.$script.'"></script>';
		}
		
		// Generic dump from head variable
		$head .= self::$head;
		
		// Favicon
		$head .= '<link rel="icon" type="image/x-icon" href="'.sq::base().self::$favicon.'"/>';
		
		// Close head tag
		$head .= '</head>';
		
		// Open the body tag with an id of the page type
		$head .= '<body id="'.self::$id.'">';
		
		return $head;
	}
	
	// Renders the footer.
	protected function formatFoot() {
		$foot = null;
		
		// Print foot scripts
		foreach (self::$scripts['foot'] as $script) {
			$foot .= '<script type="text/javascript" src="'.$script.'"></script>';
		}
		
		$foot .= self::$foot;      // Generic dump in foot variable
		$foot .= '</body></html>'; // Close tags
		
		return $foot;
	}
	
	// Start a clip optionally saving the clip to a layout variable
	public function clip($var = false) {
		ob_start();
		
		if (is_object($this->layout) && $var) {
			array_push($this->clips, $var);
		}
	}
	
	// Ends clip and returns the content captured
	public function end() {
		$content = ob_get_clean();
		
		if (!empty($this->clips) && is_object($this->layout)) {
			$this->layout->{array_pop($this->clips)} = $content;
		}
		
		return $content;
	}
	
	// Adds a script to template
	public static function script($path, $location = 'foot') {
		self::$scripts[$location][] = $path;
	}
	
	// Adds a style to head
	public static function style($path) {
		self::$styles[] = $path;
	}
	
	// Returns a formatted date. If no date is passed to the function now will
	// be assumed. Optionally a third argument can be passed in to specify the
	// format of the date string given to the function.
	public static function date($formatOut, $date = false, $formatIn = false) {
		if ($formatIn && $date) {
			$date = DateTime::createFromFormat($formatIn, $date)
				->format($formatOut);
		} else {
			if ($date === false) {
				$date = 'now';
			}
			
			$date = strtotime($date);
			$date = date($formatOut, $date);
		}
		
		return $date;
	}
	
	// Shortens text and appends ...
	public static function blurb($string, $length = 100, $closing = '&hellip;') {
		$string = strip_tags($string);
		$string = substr($string, 0, $length);
		
		if (strlen($string) > $length - 1) {
			$string = $string.$closing;
		}
		
		return $string;
	}
}

?>