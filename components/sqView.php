<?php

/**
 * Abstract base view class
 *
 * Takes the path of a template file, puts the single passed content array into 
 * it and returns a fully formed html page for saving, displaying, etc...  Also
 * contains a few static helper methods to streamline formatting. Views are 
 * rendered inside to outside. Specficly called views first then layouts working
 * outwards.
 */

abstract class sqView extends component {
	
	// If full is true the framework will generate header and footer sections.
	// View is the path of the view going to be generated with the view data.
	// Both of these values can be changed at any time.
	public $full, $view, $parent;
	
	// FIFO stack of the list of current view clips
	private $clips = array();
	
	// Cache of the slots model
	protected static $slots = null;
	
	// Variable to store data to be turned into a javascript JSON object in the
	// footer
	protected static $jsData = array();
	
	// Static counter. Every time a new view object is created it goes up by 
	// one. This counter allows the object to know the current position of the
	// view in the overall render flow.
	protected static $current;
	
	// Scripts and styles arrays use the current property to reverse the render
	// flow. So scripts and styles included in the inner most view appear last;
	// the reverse of the normal flow.
	protected static $styles = array(),
		$scripts = array('foot' => array(), 'head' => array());
	
	// In template variables
	public static $description, $doctype, $title, $language, $favicon, $id,
		$head, $foot, $top, $charset, $keywords = array();
	
	public function __construct($options, $view, $data = array()) {
		
		// Add static data from the passed in array
		$this->data = $data + $this->data;
		
		// Set view
		$this->view = $view;
		
		parent::__construct($options);
		
		// Set defult options for description title doctype and such
		foreach (array('description', 'keywords', 'charset', 'title', 'doctype', 'language', 'favicon', 'id') as $prop) {
			if (!self::$$prop) {
				self::$$prop = $this->options[$prop];
			}
		}
	}
	
	// Special overloaded setter that adds data from the layout view into views
	// included
	public function __set($name, $value) {
		if (is_object($value) && !is_a($value, 'model')) {
			$value->data += $this->data;
			
			if (is_a($value, 'view')) {
				$value->parent = &$this;
			}
			
			$value = $value->render();
		}
		
		$this->data[$name] = $value;
	}
	
	// Renders the template view file. If full is specified the template will
	// include the auto generated header and footer sections. HTML templates 
	// should only include content inside the body tag and omit the body tag and
	// everything outside of it.
	public function render($view = null, $data = array()) {
		
		// Clear the labels session variable
		if (!isset($_SESSION)) {
			session_start();
		}
		
		unset($_SESSION['sq-form-labels']);
		
		if ($view) {
			return sq::view($view, $data)
				->set($data + $this->data)
				->render();
		} else {
			return $this->renderTemplate($data);
		}
	}
	
	// Utility function to render a template
	private function renderTemplate($data) {
		$this->set($data);
		
		// Set variables
		extract($this->data);
		
		// Variable for root path of website
		$base = sq::base();
		
		ob_start();
		include $this->getViewPath($this->view);
		
		if (isset($this->parent)) {
			self::$current++;
			
			// Set local data to the parent view
			$this->parent->set($this->data);
		}
		
		$rendered = ob_get_clean();
		
		if ($this->layout) {
			self::$current++;
			
			if (is_string($this->layout)) {
				$this->layout = sq::view($this->layout);
			}
			
			$this->layout->set($this->data);
			$this->layout->content = $rendered;
			
			if ($this->full) {
				$this->layout->full = true;
			}
			
			$rendered = $this->layout;
		} elseif ($this->full) {
			if (self::$head !== false) {
				$rendered = $this->formatHead().$rendered;
			}
			
			if (self::$foot !== false) {
				$rendered = $rendered.$this->formatFoot();
			}
		}
		
		return $rendered;
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
	
	// Renders the auto generated head section from the static view parameters
	protected function formatHead() {
		$head = self::$doctype;                       // Doctype
		$head .= '<html lang="'.self::$language.'">'; // Open html tag
		$head .= '<head>';                            // Open head tag
		
		// Charset if set
		if (self::$charset) {
			$head .= '<meta charset="'.self::$charset.'">';
		}
		
		// Print head scripts
		foreach (array_reverse(self::$scripts['head']) as $group) {
			foreach ($group as $script) {
				$head .= '<script src="'.$script.'"></script>';
			}
		}
		
		// Generic dump from top variable
		$head .= self::$top;
		
		// External stylesheets
		foreach (array_reverse(self::$styles) as $group) {
			foreach ($group as $style) {
				$head .= '<link rel="stylesheet" href="'.$style.'"/>';
			}
		}
		
		// HTML title
		$head .= '<title>'.self::$title.'</title>';
		
		// Favicon
		$head .= '<link rel="icon" href="'.self::$favicon.'"/>';
		
		self::$description = strip_tags(self::$description);
		self::$description = str_replace("\n", " ", self::$description);
		self::$description = str_replace("  ", " ", self::$description);
		
		// Named meta tags
		$head .= '<meta name="description" content="'.self::$description.'">';
		$head .= '<meta name="keywords" content="'.implode(',', self::$keywords).'">';
		
		// Dump foom head variable
		$head .= self::$head;
		
		// Close head tag
		$head .= '</head>';
		
		// Open the body tag optionally with an id
		if (self::$id) {
			$head .= '<body id="'.self::$id.'">';
		} else {
			$head .= '<body>';
		}
		
		return $head;
	}
	
	// Renders the footer
	protected function formatFoot() {
		$foot = null;
		
		// Print a JSON object in the footer
		self::$jsData += $this->options['js-data'];
		if (self::$jsData) {
			
			// Pretty print JSON if debug is enabled. Formatting is funky so the
			// source code looks good.
			if (sq::config('debug')) {
				$foot .= '
<script>

// Init sq object
var sq = {

	// Data passed from the sq framework
	data: '.str_replace('    ', "\t", json_encode(self::$jsData)).'
}

</script>
';
			} else {
				$foot .= '<script>(var sq = {data: '.json_encode(self::$jsData).'}</script>';
			}
		}
		
		// Print foot scripts
		foreach (array_reverse(self::$scripts['foot']) as $group) {
			foreach ($group as $script) {
				$foot .= '<script src="'.$script.'"></script>';
			}
		}
		
		$foot .= self::$foot;      // Generic dump in foot variable
		$foot .= '</body></html>'; // Close tags
		
		return $foot;
	}
	
	// Creates / uses a content slot. Content slots are bits of content stored
	// in a model that may be defined directly in code. Slots are editable in
	// the Admin module or via a custom setup in your app.
	public static function slot($id, $name, $type = 'markdown', $content = null) {
		
		// Create model object if one doesn't already exist and read slots and
		// cache them to the view
		if (!self::$slots) {
			self::$slots = sq::model('sq_slots')
				->make(sq::config('sq_slots/schema'))
				->read();
		}
		
		// Find the requested slot and create if it doesn't exist
		$slot = self::$slots->find($id);
		if (!$slot) {
			$slot = sq::model('sq_slots')->create(array(
				'id' => $id,
				'name' => $name,
				'type' => $type,
				'content' => $content
			));
		}
		
		// Special rendering for slot types
		if ($slot->content) {
			switch ($slot->type) {
				case 'markdown':
					sq::load('phpMarkdown');
					$output = markdown($slot->content);
					break;
				case 'image':
					$output = '<img src="'.sq::base().$slot->content.'" alt="'.$slot->alt_text.'"/>';
					break;
				default:
					$output = $slot->content;
					break;
			}
			
			return "<div class=\"sq-slot $id\">$output</div>";
		}
	}
	
	// Print out a php object or array to screen with readable formatting
	public static function debug($content) {
		if (sq::config('debug')) {
			echo '<pre>';
			print_r($content);
			echo '</pre>';
		}
	}
	
	// Start a clip optionally saving the clip to a layout variable
	public function clip($name = null) {
		ob_start();
		
		if ($name) {
			array_push($this->clips, $name);
		}
	}
	
	// Ends clip and returns the content captured
	public function end() {
		if (empty($this->clips)) {
			return ob_get_clean();
		}
		
		$clip = array_pop($this->clips);
		
		if (strpos($clip, 'sqcontext:') === 0) {
			$clip = str_replace('sqcontext:', '', $clip);
			
			if (url::request('sqContext') == $clip) {
				echo ob_get_clean();
				die();
			} else {
				return '</div>';
			}
		} else {
			$content = ob_get_clean();
			$this->$clip = $content;
			return $content;
		}
	}
	
	public function context($name) {
		array_push($this->clips, 'sqcontext:'.$name);
		
		if (url::request('sqContext') == $name) {
			ob_end_clean();
			ob_start();
		} else {
			return '<div id="sq-context-'.$name.'">';
		}
	}
	
	// Adds a script to template
	public static function script($path, $location = 'foot') {
		$order = self::$current;
		
		if (!isset(self::$scripts[$location][$order])) {
			self::$scripts[$location][$order] = array();
		}
		
		self::$scripts[$location][$order][] = $path;
	}
	
	// Adds a style to head
	public static function style($path) {
		$order = self::$current;
		
		if (!isset(self::$styles[$order])) {
			self::$styles[$order] = array();
		}
		
		self::$styles[$order][] = $path;
	}
	
	// Add data to the javascript sq object
	public static function jsData($data, $val = null) {
		if (is_array($data)) {
			view::$jsData += $data;
		} elseif (is_string($data) && $val) {
			view::$jsData[$data] = $val;
		}
	}
	
	// Returns a formatted date. If no date is passed to the function now will
	// be assumed. Optionally a third argument can be passed in to specify the
	// format of the date string given to the function.
	public static function date($formatOut, $date = 'now', $formatIn = null) {
		if ($formatIn) {
			return DateTime::createFromFormat($formatIn, $date)->format($formatOut);
		}
		
		$date = new DateTime($date);
		return $date->format($formatOut);
	}
	
	// Shortens text and appends a passed in ending or elipsis as default
	public static function blurb($string, $length = 100, $closing = '&hellip;') {
		$string = strip_tags($string);
		$string = substr($string, 0, $length);
		
		if (strlen($string) > $length - 1) {
			$string .= $closing;
		}
		
		return $string;
	}
}

?>