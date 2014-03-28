<?php

/**
 * Email sending component
 *
 * Sends email rendered as a view with the paramerters passed set to the object.
 * Email can be sent as text or html or as both in which case it is sent as 
 * multipart. The current view defaults to email/text or email/html but can be 
 * set with htmlView or textView.
 */

abstract class sqMailer extends component {
	private $headers = '';
	
	// View files used for the email. If one or the other is set then only that
	// view will be used. If both are set the email will be sent as multipart.
	// If no views are specified the default views email/text and email/html
	// will be used.
	public $htmlView = null, $textView = null;
	
	public function init() {
		$this->addHeader('MIME-Version: 1.0');
		
		$this->from = $this->options['from'];
	}
	
	// Sets email headers from an array
	public function headers($headers) {
		foreach ($headers as $header) {
			$this->headers .= $header."\n";
		}
	}
	
	// Adds an email header
	public function addHeader($header) {
		$this->headers .= $header."\n";
	}
	
	// Sends email
	public function send() {
		$this->addheader('From: '.$this->from);
		$this->addheader('Subject: '.$this->subject);
		
		// If no views have been specified use the defaults
		if (!$this->textView && !$this->htmlView) {
			$this->textView = $this->options['text-view'];
			$this->htmlView = $this->options['html-view'];
		}
		
		if ($this->options['format'] == 'both') {
			$this->addHeader('Content-Type: multipart/alternative; boundary='.$this->options['boundary']);
		}
		
		$message = $this->makeBoundary();
		
		if ($this->options['format'] == 'both') {
			$message .= "Content-Type: text/plain; charset=utf-8\n\n";
		}

		if ($this->options['format'] == 'both' || $this->options['format'] == 'text') {
			$message .= sq::view($this->textView, $this->data, false);
		}
		
		$message .= $this->makeBoundary();

		if ($this->options['format'] == 'both') {
			$message .= "Content-Type: text/html; charset=utf-8\n\n";
		}
		
		if ($this->options['format'] == 'both' || $this->options['format'] == 'html') {
			$message .= sq::view($this->htmlView, $this->data, false);
		}
		
		$message .= $this->makeBoundary(true);
		
		// Send email
		mail($this->to, $this->subject, $message, $this->headers);
	}
	
	// Creates the boundary between multipart html and text in emails
	private function makeBoundary($last = false) {
		$boundary = null;
		
		if ($this->options['format'] == 'both') {
			$boundary = "\n\n--".$this->options['boundary'];
			if ($last) {
				$boundary .= '--';
			} else {
				$boundary .= "\n";
			}
		}
		
		return $boundary;
	}
}

?>