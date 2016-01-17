<section class="sq-list">
	<h2><?=$title ?></h2>
	<span class="sq-list-count"><?=count($model) ?> Results</span>
	
	<?=view::pagination($model) ?>
	
	<?=form::open() ?>
		<div class="sq-actions sq-list-actions">
<? foreach ($model->options['actions'] as $key => $val):
	$action = $val;
	$display = ucwords($action);
	if (!is_numeric($key)):
		$action = $key;
		$display = $val;
	endif;	
	
	$url = sq::route()->current()->append(array(
		'action' => $action
	))->remove('page');
	
	echo '<a class="sq-action sq-'.$action.'-action" href="'.$url.'">'.$display.'</a>';
endforeach ?>
		</div>
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
<? foreach ($model as $item): ?>
					<tr>
	<? foreach ($fields as $name => $type):
		if (isset($item->$name)):
			if ($type == 'sort'):
				if (!$item->$name):
					$item->$name = null;
				endif;
				echo '
					<td class="sq-sort-item">
						<input name="save['.$type.']['.$item->id.']" type="text" autocomplete="off" inputmode="numeric" maxlength="3" value="'.$item->$name.'"/>
					</td>';
			else:
				echo '<td class="sq-'.$type.'-item">'.listing::$type($item->$name).'</td>';
			endif;
		endif;
	endforeach;
	if (isset($item->id)): ?>
						<td class="sq-actions sq-inline-actions">
		<? foreach ($model->options['inline-actions'] as $key => $val):
			$action = $val;
			$display = ucwords($action);
			if (!is_numeric($key)):
				$action = $key;
				$display = $val;
			endif;
			
			$url = sq::route()->current()->append(array(
				'action' => $action,
				'id' => $item->id
			))->remove('page');
			
			echo '<a href="'.$url.'" class="sq-action sq-'.$action.'-action">'.$display.'</a>';
		endforeach ?>
						</td>
	<? endif ?>
					</tr>
<? endforeach ?>
				</tbody>
			</table>
		</div>
		<? if (in_array('sort', $fields)): ?>
			<button class="sq-action sq-sort-action" name="action" value="sort">Update</button>
		<? endif ?>
	<?=form::close() ?>
	
	<?=view::pagination($model) ?>
</section>