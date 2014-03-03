<?php

class markdown extends file {
	public function init() {
		sq::load('phpMarkdown.php');
	}
	
	public function parse($content) {
		return array(
			'raw' => $content,
			'content' => markdown($content)
		);
	}
}

?>