<?

$inlineActions = $model->options['inline-actions'];
$actions = $model->options['actions'];
$modelName = $model->options['name'];

if (sq::request()->any('controller')):
	$modelName = sq::request()->any('controller');
endif;

?>
<section class="form list-form">
	<h2><? echo ucwords($modelName) ?></h2>
	<span class="count"><? echo count($model) ?> Results</span>
	
	<?=view::pagination($model) ?>
	
	<form method="post" action="">
		<div class="actions global-actions listing-actions">
<?
if ($actions):
	foreach ($actions as $action):
		echo '<a href="'.$base.sq::request()->get('module').'/'.$modelName.'/'.sq::route()->format($action).'" class="action global-action list-action">'.ucwords($action).'</a>';
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
		<? foreach ($inlineActions as $action):
			$id = '?id='.$item->id;
			
			if (is_int($item->id)):
				$id = '/'.$item->id;
			endif;
			echo '<a href="'.$base.sq::request()->get('module').'/'.$modelName.'/'.sq::route()->format($action).$id.'" class="action inline-action list-action '.sq::route()->format($action).'-action">'.ucwords($action).'</a>';
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