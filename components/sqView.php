<?php

/**
 * View component
 *
 * Takes the path of a template file and an array of data values for the view
 * and renders a html view. Views may be wrapped in layers of layouts and sub
 * views may be included. Views are rendered inside to outside starting with the
 * first called view and then rendering layouts. Sections of a view can be
 * clipped into a variable for use in a layout.
 *
 * Contains static helper methods to streamline formatting dates, truncating
 * text, generating pagination and printing out data for debuging.
 *
 * A view represents the body content of an html document. The head foot 
 * sections can have scripts, styles and other content injected into them using
 * various methods and properties.
 */

abstract class sqView extends component {
	
	// If full is true the framework will generate header and footer sections.
	// View is the path of the view going to be generated with the view data.
	// Both of these values can be changed at any time.
	public $full, $view, $parent;
	
	// FIFO stack of the list of current view clips
	private $clips = array();
	
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
	
	// In template variables. Setting these changes the value included in the
	// html head tag.
	public static $description, $doctype, $title, $language, $favicon, $id,
		$head, $foot, $top, $charset, $keywords = array();
	
	// Setup the view
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
	
	// Special overloaded setter that adds data from the layout view into
	// included views
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
	
	// Reset global view properties
	public static function reset() {
		foreach (array('description', 'keywords', 'charset', 'title', 'doctype', 'language', 'favicon', 'id') as $prop) {
			self::$$prop = sq::config('view/'.$prop);
		}
		
		self::$styles = array();
		self::$scripts = array('foot' => array(), 'head' => array());
		self::$jsData = array();
	}
	
	// Returns true if the view file exists, false if not.
	public static function exists($view) {
		return (bool)self::getViewPath($view);
	}
	
	/**
	 * Renders the specified view
	 *
	 * If full is specified the template will include the auto generated head
	 * and foot sections. HTML templates should only include content inside the
	 * body tag and omit the body tag and everything outside of it.
	 */
	public function render($view = null, $data = array()) {
		if ($view) {
			return sq::view($view, $data)
				->set($data + $this->data)
				->render();
		} else {
			return $this->renderTemplate($data);
		}
	}
	
	// Utility method to render a template dealing with moving data between
	// layouts and nested views
	private function renderTemplate($data) {
		$this->set($data);
		
		// Set variables
		extract($this->data);
		
		// Variable for root path of website
		$base = sq::base();
		
		ob_start();
		include self::getViewPath($this->view);
		
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
	
	// Utility method to find the file path from a passed in view
	private static function getViewPath($file) {
		$path = null;
		
		if (file_exists(sq::root().'/views/'.$file.'.php')) {
			$path = sq::root().'/views/'.$file.'.php';
		} elseif (file_exists(sq::path().'views/'.$file.'.php')) {
			$path = sq::path().'views/'.$file.'.php';
		} else {
			$module = explode('/', $file);
			$module = $module[0];
			$file = str_replace($module.'/', '', $file);
			
			if (file_exists(sq::root().'/modules/'.$module.'/views/'.$file.'.php')) {
				$path = sq::root().'/modules/'.$module.'/views/'.$file.'.php';
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
				$foot .= '<script>var sq = {data: '.json_encode(self::$jsData).'}</script>';
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
	
	// Print out a object or array to screen with readable formatting
	public static function debug($content) {
		if (sq::config('debug')) {
			echo '<pre class="sq-printout">';
			echo htmlentities(print_r($content, true));
			echo '</pre>';
		}
	}
	
	// Start a clip, optionally saving the clip to a layout variable
	public function clip($name = null) {
		ob_start();
		
		if ($name) {
			array_push($this->clips, $name);
		}
	}
	
	// Start a rendering context. Rendering contexts are a special kind of clip
	// that, when requested by name with the sqContext url parameter, return
	// only the content contained in that clip. When contexts aren't being
	// requested they print out a wrapping div with the name as and id for use
	// in sq.js or other javascript tools.
	public function context($name) {
		array_push($this->clips, 'sqcontext:'.$name);
		
		if (sq::request()->context == $name) {
			ob_end_clean();
			ob_start();
		} else {
			return '<div id="sq-context-'.$name.'">';
		}
	}
	
	// Ends clip and returns the content captured. For context clips end will
	// immediately end view rendering and return only current clip content. If
	// the context isn't currently active end prints out a closing div to close
	// the div opened by view::context above.
	public function end() {
		if (empty($this->clips)) {
			return ob_get_clean();
		}
		
		$clip = array_pop($this->clips);
		
		if (strpos($clip, 'sqcontext:') === 0) {
			$clip = str_replace('sqcontext:', '', $clip);
			
			if (sq::request()->context == $clip) {
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
	
	// Adds a script include to the page
	public static function script($path, $location = 'foot') {
		$order = self::$current;
		
		if (!isset(self::$scripts[$location][$order])) {
			self::$scripts[$location][$order] = array();
		}
		
		self::$scripts[$location][$order][] = $path;
	}
	
	// Adds a stylesheet to the view
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
	public static function truncate($string, $length = 100, $closing = '&hellip;') {
		$string = strip_tags($string);
		$string = substr($string, 0, $length);
		
		if (strlen($string) > $length - 1) {
			$string .= $closing;
		}
		
		return $string;
	}
	
	// Generates page numbers for a model
	public static function pagination($model, $options = array()) {
		$options = sq::merge(sq::config('view/pagination'), $options);
		
		// Show nothing if there aren't enough items to paginate. This can be
		// disabled by enabling the always-show option.
		if (!$options['show-always'] && $model->options['pages'] <= 1) {
			return;
		}
		
		$currentPage = sq::request()->get('page', 1);
		
		// Generate SEO links in document head
		if ($options['seo-links']) {
			if ($currentPage < $model->options['pages']) {
				self::$head .= '<link rel="next" href="'.sq::route()->current()->append(array('page' => $currentPage + 1)).'"/>';
			}
			
			if ($currentPage > 1) {
				self::$head .= '<link rel="prev" href="'.sq::route()->current()->append(array('page' => $currentPage - 1)).'"/>';
			}
		}
		
		$options['first'] = str_replace('{number}', 1, $options['first']);
		$options['last'] = str_replace('{number}', $model->options['pages'], $options['last']);
		
		return sq::view('widgets/pagination', array(
			'currentPage' => $currentPage,
			'options' => $options,
			'url' => sq::route()->current(),
			'pageCount' => $model->options['pages']
		));
	}
}

?>