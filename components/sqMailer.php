<?php

/**
 * Mailer component
 *
 * Sends email with the options passed to the component. Email can be sent as
 * text or html or as both in which case it is sent as multipart. Views may be
 * passed in as the text and/or html email body.
 */

abstract class sqMailer extends component {

	// List of mail headers
	private $headers = null;

	// Adds an email header
	public function addHeader($header) {
		$this->headers .= $header."\n";

		return $this;
	}

	// Send email. Optionally to a specific recipient.
	public function send($to = null) {
		if ($to) {
			$this->options['to'] = $to;
		}

		// Set the content header as long as the message isn't multipart
		if (!empty($this->options['text']) && empty($this->options['html'])) {
			$this->addHeader('Content-Type: text/plain; charset=utf-8');
		} elseif (!empty($this->options['html']) && empty($this->options['text'])) {
			$this->addHeader('Content-Type: text/html; charset=utf-8');
		}

		// Set required headers
		$this->addHeader('MIME-Version: 1.0');
		$this->addheader('From: '.$this->options['from']);

		mail($this->options['to'], $this->options['subject'], $this->getBody(), trim($this->headers));

		return $this;
	}

	// Generate the body of the mail message. If both a text and html view are
	// provided then multipart/alternative will be used.
	public function getBody() {
		$body = null;

		if (!empty($this->options['text']) && !empty($this->options['html'])) {
			$this->addHeader('Content-Type: multipart/alternative; boundary='.$this->options['boundary']);

			$body .= "Content-Type: text/plain; charset=utf-8\n\n";
			$body .= $this->makeBoundary();
		}

		if (!empty($this->options['text'])) {
			$body .= $this->options['text'];
		}

		if (!empty($this->options['text']) && !empty($this->options['html'])) {
			$body .= $this->makeBoundary();
			$body .= "Content-Type: text/html; charset=utf-8\n\n";
		}

		if (!empty($this->options['html'])) {
			$body .= $this->options['html'];
		}

		if (!empty($this->options['text']) && !empty($this->options['html'])) {
			$body .= $this->makeBoundary(true);
		}

		return $body;
	}

	// Creates the boundary between multipart html and text in emails
	private function makeBoundary($last = false) {
		$boundary = "\n\n--".$this->options['boundary'];
		if ($last) {
			$boundary .= '--';
		} else {
			$boundary .= "\n";
		}

		return $boundary;
	}
}
