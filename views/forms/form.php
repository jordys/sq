<?

$modelName = $model->options['name'];

if (!isset($action) || !$action) {
	$action = sq::request()->get('module').'/'.$modelName.'/'.sq::request()->get('action');
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
<? if (!$model->options['inline-view']): ?>
<section class="form edit-form">
	<h2><? echo ucwords(sq::request()->get('action')) ?></h2>
	<form method="post" enctype="multipart/form-data" action="<? echo $base, $action?>">
<? else: ?>
	<div class="inline-form">
		<h3><? echo ucwords($modelName) ?></h3>
		<input type="hidden" name="model[]" value="<? echo $baseName ?>"/>
<? endif ?>
<? foreach ($fields as $name => $type):
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
<? if (!$model->options['inline-view']): ?>
		<div class="actions global-actions form-actions">
			<input type="submit" name="button" value="Save"/>
			<a class="cancel form-cancel" href="<? echo $base.sq::request()->any('controller').'/'.$modelName?>">Cancel</a>
		</div>
	</form>
</section>
<? else: ?>
</div>
<? endif ?>