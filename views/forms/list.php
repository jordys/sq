<?

$inlineActions = $model->options['inline-actions'];
$actions = $model->options['actions'];
$modelName = $model->options['name'];

if (sq::request()->any('controller')):
	$modelName = sq::request()->any('controller');
endif;

?>
<section class="form list-form">
	<h2><?=ucwords($modelName) ?></h2>
	<span class="count"><?=count($model) ?> Results</span>
	
	<?=view::pagination($model) ?>
	
	<form method="post" action="">
		<div class="actions global-actions listing-actions">
<?
if ($actions):
	foreach ($actions as $key => $val):
		$action = $val;
		$display = ucwords($action);
		if (!is_numeric($key)):
			$action = $key;
			$display = $val;
		endif;	
		
		$url = sq::route()->to(array(
			'controller?',
			'module?',
			'model' => $modelName,
			'action' => $action
		));
		
		echo '<a href="'.$url.'" class="action global-action list-action">'.$display.'</a>';
	endforeach;
endif;
?>
		</div>
		<div class="table-wrapper">
			<table>
				<thead>
					<tr>
<? foreach ($fields as $name => $type):
	echo '<th>'.ucwords($name).'</th>';
endforeach ?>
						<th class="list-actions"></th>
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
					<td class="'.sq::route()->format($type).'-list-item">
						<input class="sort" name="save['.$type.']['.$item->id.']" type="text" autocomplete="off" inputmode="numeric" maxlength="3" value="'.$item->$name.'"/>
					</td>';
			else:
				echo '<td class="'.sq::route()->format($type).'-list-item">'.listing::$type($item->$name).'</td>';
			endif;
		endif;
	endforeach;
	if (isset($item->$name)): ?>
						<td class="actions inline-actions list-actions">
		<? foreach ($inlineActions as $key => $val):
			$action = $val;
			$display = ucwords($action);
			if (!is_numeric($key)):
				$action = $key;
				$display = $val;
			endif;
			
			$url = sq::route()->to(array(
				'controller?',
				'module?',
				'model' => $modelName,
				'action' => $action,
				'id' => $item->id
			));
			
			echo '<a href="'.$url.'" class="action inline-action list-action '.sq::route()->format($action).'-action">'.$display.'</a>';
		endforeach ?>
						</td>
	<? endif ?>
					</tr>
<? endforeach ?>
				</tbody>
			</table>
		</div>
		<? if (in_array('sort', $fields)): ?>
			<button name="action" value="sort">Update</button>
		<? endif ?>
	</form>
	
	<?=view::pagination($model) ?>
</section>