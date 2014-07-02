<?php

abstract class sqAdmin extends controller {
	public $layout = 'admin/layouts/main';
	
	public function filter($action) {		
		sq::controller('auth')->action($action);
		
		if (!$this->options['require-login'] || auth::check('admin')) {
			return true;
		} else {
			return sq::view('admin/login');
		}
	}
	
	public function defaultAction($act) {
		if ($act == 'password') {
			$this->layout->content = sq::controller('auth')->action('password');
		}
	}
	
	public function init() {
		$this->layout->modelName = url::get('model');
	}
	
	public function indexAction() {
		if (url::get('model')) {
			$this->layout->content = sq::model(url::get('model'))
				->read();
		}
	}
	
	public function createAction() {
		$model = sq::model(url::get('model'));
		
		if (url::post()) {
			$save = url::post('save', false);
			
			if (isset($save['id-field'])) {
				$idField = $save['id-field'];
				unset($save['id-field']);
			}
			
			$model->set($save);
			
			if (is_array(url::post('model'))) {
				foreach (url::post('model') as $inline) {
					$inlineModel = sq::model($inline);
					$inlineModel->set(url::post($inline));
					$inlineModel->create();
					
					$model->$idField = $inlineModel->id;
				}
			}
			
			$model->create();
			
			sq::redirect(sq::base().'admin/'.url::get('model'));
		} else {
			$model->schema();
			$model->limit();
			
			$this->layout->content = $model;
		}
	}
	
	public function updateAction() {
		$model = sq::model(url::get('model'));
		$model->options['load-relations'] = false;
		$model->where(url::request('id'));
		
		if (url::post()) {
			$save = url::post('save', false);
			
			if (isset($save['id-field'])) {
				$idField = $save['id-field'];
				unset($save['id-field']);
			}
			
			$model->set($save);
			
			if (is_array(url::post('model'))) {
				foreach (url::post('model') as $inline) {
					$inlineModel = sq::model($inline);
					$inlineModel->where(url::request('id'));
					$inlineModel->set(url::post($inline));
					$inlineModel->update();
					
					if (isset($inlineModel->id)) {
						$model->$idField = $inlineModel->id;
					}
				}
			}
			
			$model->update();
			sq::redirect(sq::base().'admin/'.url::get('model'));
		} else {
			$model->read();
			
			$this->layout->content = $model;
		}
	}
	
	public function deleteAction() {
		$model = sq::model(url::get('model'));
		$model->where(url::get('id'));
		
		if (url::post()) {
			$model->delete();
			
			sq::redirect(sq::base().'admin/'.url::get('model'));
		} else {
			$model->read();
			
			$this->layout->content = sq::view('admin/confirm', array('model' => $model));
		}
	}
}

?>