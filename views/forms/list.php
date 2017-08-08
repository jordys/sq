<? if (empty($subList) || !$subList): ?>
		<?=form::open() ?>
		
		<div class="sq-table sq-list-table">
			<table>
				<thead>
					<tr>
<? foreach ($fields as $name => $type):
	echo '<th>'.ucwords($name).'</th>';
endforeach ?>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<? endif ?>
<? foreach ($model as $item): ?>
					<tr <?=!empty($subList) ? 'class="indent"' : '' ?>>
	<? foreach ($fields as $name => $type):
		if (isset($item->$name)):
			if ($type == 'sort'):
				echo '
					<td class="sq-sort-item">
						<input name="save['.$name.']['.$item->id.']" type="text" autocomplete="off" inputmode="numeric" maxlength="3" value="'.$item->$name.'"/>
					</td>';
			else:
				echo '<td class="sq-'.$type.'-item">'.listing::$type($item->$name).'</td>';
			endif;
		endif;
	endforeach;
	if (isset($item->id)): ?>
						<td class="sq-actions sq-inline-actions">
							<?=$this->render('admin/forms/actions', ['item' => $item]) ?>
						</td>
	<? endif ?>
					</tr>
					<? if ($model->options['hierarchy'] && !empty($item->{$model->options['hierarchy']})): ?>
						<?=listing::list($item->{$model->options['hierarchy']}) ?>
					<? endif ?>
<? endforeach ?>
		<? if (empty($subList) || !$subList): ?>

				</tbody>
			</table>
		</div>
		<? if (in_array('sort', $fields)): ?>
			<button class="sq-action sq-sort-action" name="action" value="sort">Update</button>
		<? endif ?>
	<?=form::close() ?>
<? endif ?>