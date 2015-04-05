<?php

$modelName = $model->options['name'];

if (!isset($action) || !$action) {
	$action = url::get('module').'/'.$modelName.'/'.url::get('action');
}

if (isset($model->id)) {
	if (is_int($model->id)) {
		$action .= '/'.$model->id;
	} else {
		$action .= '?id='.$model->id;
	}
}

$baseName = 'save';

if ($model->options['inline-view']) {
	$baseName = $modelName;
}

?>
<?php if (!$model->options['inline-view']): ?>
<section class="form edit-form">
	<h2><?php echo ucwords(url::get('action'))?></h2>
	<form method="post" enctype="multipart/form-data" action="<?php echo $base, $action?>">
<?php else: ?>
	<div class="inline-form">
		<h3><?php echo ucwords($modelName) ?></h3>
		<input type="hidden" name="model[]" value="<?php echo $baseName ?>"/>
<?php endif ?>
<?php foreach ($fields as $name => $type):
	$type = explode('|', $type);
	
	$arg = false;
	if (isset($type[1])) {
		$arg = $type[1];
	}
	
	$fieldValue = null;
	$type = $type[0];
	if (isset($model->$name)) {
		$fieldValue = $model->$name;
	}
	
	if ($type != 'inline') {
		echo '<div class="form-block">';
		echo form::label($baseName.'['.$name.']', ucwords(str_replace('_', ' ', $name)), $type);
	} else {
		echo '<input type="hidden" value="'.$name.'" name="'.$baseName.'[id-field]"/>';
		echo '<input type="hidden" name="'.$baseName.'['.$name.']" value="'.$fieldValue.'"/>';
	}
	
	if ($arg) {
		echo form::$type($baseName.'['.$name.']', $arg, $fieldValue);
	} else {
		echo form::$type($baseName.'['.$name.']', $fieldValue);
	}
	
	if ($type != 'inline') {
		echo '</div>';
	}
endforeach ?>
<?php if (!$model->options['inline-view']): ?>
		<div class="actions global-actions form-actions">
			<input type="submit" name="button" value="Save"/>
			<a class="cancel form-cancel" href="<?php echo $base.url::request('controller').'/'.$modelName?>">Cancel</a>
		</div>
	</form>
</section>
<?php else: ?>
</div>
<?php endif ?>