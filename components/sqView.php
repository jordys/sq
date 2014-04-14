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
	private static $slots = false;
	
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
		$head, $foot, $keywords = array();
	
	public function __construct($options, $view, $data = array(), $full = null) {
		
		// Add static data from the passed in array
		$this->data = $data + $this->data;
		
		// Set object parameters
		$this->options = $options;
		$this->view    = $view;
		$this->full    = $full;
		
		// Set defult options for description title doctype and such
		foreach (array('description', 'keywords', 'title', 'doctype', 'language', 'favicon', 'id') as $prop) {
			if (!self::$$prop) {
				self::$$prop = $this->options[$prop];
			}
		}
	}
	
	// Special overloaded setter that adds data from the layout view into views
	// loaded included
	public function __set($name, $value) {
		if (is_object($value) && !is_subclass_of($value, 'model')) {
			$value->data += $this->data;
			
			if (get_class($value) == 'view' || is_subclass_of($value, 'view')) {
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
	public function render($view = null, $data = array(), $full = null) {
		if ($view === null) {
			$view = $this->view;
			self::$current++;
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
			
			$this->layout->set($this->data);
			$this->layout->content = $rendered;
			
			if ($full) {
				$this->layout->full = true;
			}
			
			$rendered = $this->layout;
		} elseif ($full) {
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
		
		if (isset($this->parent)) {
			$this->parent->set($this->data);
		}
		
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
		
		// External stylesheets
		foreach (array_reverse(self::$styles) as $group) {
			foreach ($group as $style) {
				$head .= '<link rel="stylesheet" type="text/css" href="'.$style.'"/>';
			}
		}
		
		// Print head scripts
		foreach (array_reverse(self::$scripts['head']) as $group) {
			foreach ($group as $script) {
				$head .= '<script type="text/javascript" src="'.$script.'"></script>';
			}
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
		foreach (array_reverse(self::$scripts['foot']) as $group) {
			foreach ($group as $script) {
				$foot .= '<script type="text/javascript" src="'.$script.'"></script>';
			}
		}
		
		$foot .= self::$foot;      // Generic dump in foot variable
		$foot .= '</body></html>'; // Close tags
		
		return $foot;
	}
	
	// Creates / uses a content slot. Content slots are bits of content stored
	// in a model that may be defined directly in code. Slots are editable in
	// the Admin module or via a custom setup in your app.
	public static function slot($id, $name, $type = 'markdown', $content = '') {
		
		// Create model object if one doesn't already exist and read slots and
		// cache them to the view.
		if (!self::$slots) {
			self::$slots = sq::model('sq_slots')
				->make(sq::config('view/slots-db'))
				->read();
		}
		
		// Find the requested slot and create if it doesn't exist
		$slot = self::$slots->find($id);
		if ($slot) {
			$content = $slot->content;
			$type = $slot->type;
		} else {
			self::$slots->create(array(
				'id' => $id,
				'name' => $name,
				'type' => $type,
				'content' => $content
			));
		}
		
		// If the slot type is markdown parse it
		if ($type == 'markdown') {
			sq::load('phpMarkdown');
			
			$content = markdown($content);	
		}
		
		return $content;
	}
	
	// Start a clip optionally saving the clip to a layout variable
	public function clip($var = false) {
		ob_start();
		
		array_push($this->clips, $var);
	}
	
	// Ends clip and returns the content captured
	public function end() {
		$content = ob_get_clean();
		
		if (!empty($this->clips)) {
			$this->{array_pop($this->clips)} = $content;
		}
		
		return $content;
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