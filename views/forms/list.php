<?

$sort = false;

if (!$model->options['inline-view']):
	if (!$model->options['picker']):
		echo form::open();
	endif;

?>
<div class="sq-list <?=$model->options['inline-view'] ? 'sq-inline' : '' ?>">
	<table>
		<thead>
			<tr>
				<? foreach ($fields as $name => $type):
					if (isset($type['label'])) {
						$name = $type['label'];
					} else {
						$name = ucwords(str_replace('_', ' ', $name));
					}
					echo '<th>'.$name.'</th>';
				endforeach ?>
				<th></th>
			</tr>
		</thead>
		<tbody>
<? endif ?>

<? foreach ($model as $item):
	echo '<tr>';
	foreach ($fields as $id => $field):
		if (is_string($field)) {
			$field = [
				'format' => $field
			];
		}

		if (isset($field['type']) && is_string($field['type'])) {
			$field['type'] = [$field['type']];
		}

		if (!empty($field['type']) && !empty($item->type) && !in_array($item->type, $field['type'])) {
			echo '<td></td>';
			continue;
		}

		$format = $field['format'];

		// Special behavior for sort option
		if ($format == 'sort') {
			$sort = true;
			$field['options'] = [
				'field-id' => $id,
				'item-id' => $item->id
			];
		}

		echo '<td class="sq-'.$format.'-item">';
		if (!empty($field['options'])) {
			echo listing::$format($item->$id, $field['options']);
		} else {
			echo listing::$format($item->$id);
		}
		echo '</td>';
	endforeach;

	if (isset($item->id)): ?>
		<td class="sq-actions sq-inline-actions">
			<?=$this->render('admin/forms/actions', ['item' => $item]) ?>
		</td>
	<? endif ?>

	</tr>

	<? if ($model->options['hierarchy'] && !empty($item->{$model->options['hierarchy']})): ?>
		</tbody>
		<tbody class="sq-indent <?=$model->options['inline-view'] ? 'sq-indent-2' : '' ?>">
			<?=listing::inline($item->{$model->options['hierarchy']}) ?>
		</tbody>
		<tbody class="<?=$model->options['inline-view'] ? 'sq-indent' : '' ?>">
	<? endif ?>
<? endforeach ?>

<? if (!$model->options['inline-view']): ?>
		</tbody>
	</table>

	<? if ($sort): ?>
		<button class="sq-action sq-sort-action" name="action" value="sort">Update</button>
	<? endif ?>
</div>

	<? if (!$model->options['picker']):
		echo form::close();
	endif ?>
<? endif ?>