<? if (!$model->options['inline-view']): ?>
	<?=form::open($model, ['enctype' => 'multipart/form-data']) ?>
<? endif ?>

<div class="sq-form <?=$model->options['inline-view'] ? 'sq-inline' : '' ?>">

<? foreach ($fields as $id => $field):
	if (is_int($id)) {
		$field = [
			'format' => 'heading',
			'label' => $field
		];
	}
	
	if (is_string($field)) {
		$field = ['format' => $field];
	}
	
	if (!isset($field['label'])) {
		$field['label'] = ucwords(str_replace('_', ' ', $id));
	}
	
	if (isset($field['type']) && is_string($field['type'])) {
		$field['type'] = [$field['type']];
	}
	
	if (!empty($field['type']) && !empty($model->type) && !in_array($model->type, $field['type'])) {
		continue;
	}
	
	$format = $field['format'];
	if ($format == 'heading'):
		echo '<h3 class="sq-form-heading">'.$field['label'].'</h3>';
		continue;
	endif;

	echo '<div class="sq-form-row sq-'.$format.'-form-row">';
	if (!isset($field['label']) || $field['label']) {
		echo form::label($id, $field['label'], $field['format']);
	}
	
	if (!empty($field['options'])) {
		echo form::$format($id, $field['options']);
	} else {
		echo form::$format($id);
	}
	
	if (!empty($field['help'])) {
		echo '<span class="sq-help-text">'.$field['help'].'</span>';
	}
	echo '</div>';
endforeach ?>

<div class="sq-actions sq-form-actions">
	<input class="sq-action sq-save-action" type="submit" name="button" value="Save"/>
	<a class="sq-cancel" href="<?=sq::route()->to(['module' => 'admin', 'model' => $model->options['name']]) ?>">Cancel</a>
</div>

<? if (!$model->options['inline-view']): ?>
	<?=form::close() ?>
<? endif ?>

</div>