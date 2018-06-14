<?php

/**
 * Pagination widget
 *
 * Generates page numbers for a model.
 */

abstract class sqPagination extends widget {

	// Model attribute is passed into the widget
	public $model;

	// Init is always called after component setup. Here it's overridden to set
	// the widget data to the model.
	public function init() {

		// Show nothing if there aren't enough items to paginate. This can be
		// disabled by enabling the always-show option.
		if (!$this->options['show-always'] && $this->model->options['pages'] <= 1) {
			$this->layout = null;
			return;
		}

		$currentPage = sq::request()->get('page', 1);

		// Generate SEO links in document head
		if ($this->options['seo-links']) {
			if ($currentPage < $this->model->options['pages']) {
				view::$head .= '<link rel="next" href="'.sq::route()->current()->append(['page' => $currentPage + 1]).'"/>';
			}

			if ($currentPage > 1) {
				view::$head .= '<link rel="prev" href="'.sq::route()->current()->append(['page' => $currentPage - 1]).'"/>';
			}
		}

		$this->options['first'] = str_replace('{number}', 1, $this->options['first']);
		$this->options['last'] = str_replace('{number}', $this->model->options['pages'], $this->options['last']);

		$this->layout->set([
			'currentPage' => $currentPage,
			'options' => $this->options,
			'pageCount' => $this->model->options['pages']
		]);
	}
}
