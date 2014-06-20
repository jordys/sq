<?php

$inlineActions = $model->options['inline-actions'];
$actions = $model->options['actions'];
$modelName = $model->options['name'];

if (url::request('controller')):
	$modelName = url::request('controller');
endif;

?>
<section class="form list-form">
	<h2><?php echo ucwords($modelName)?></h2>
	<span class="count"><?php echo count($model)?> Results</span>
	<form>
		<div class="actions global-actions listing-actions">
<?php
if ($actions): 
	foreach ($actions as $action):
		echo '<a href="'.$base.url::get('module').'/'.$modelName.'/'.url::make($action).'" class="action global-action list-action">'.ucwords($action).'</a>';
	endforeach;
endif;
?>
		</div>
		<div class="table-wrapper">
			<table>
				<thead>
					<tr>
<?php foreach ($fields as $name => $type):
	echo '<th>'.ucwords($name).'</th>';
endforeach ?>
						<th class="list-actions"></th>
					</tr>
				</thead>
				<tbody>
<?php foreach ($model as $item): ?>
					<tr>
	<?php foreach ($fields as $name => $type):
		if (isset($item->$name)):
			echo '<td class="'.url::make($type).'-list-item">'.listing::$type($item->$name).'</td>';
		endif;
	endforeach;
	if (isset($item->$name)): ?>
						<td class="actions inline-actions list-actions">
		<?php foreach ($inlineActions as $action):
			$id = '?id='.$item->id;
			
			if (is_int($item->id)):
				$id = '/'.$item->id;
			endif;
			echo '<a href="'.$base.url::get('module').'/'.$modelName.'/'.url::make($action).$id.'" class="action inline-action list-action '.url::make($action).'-action">'.ucwords($action).'</a>';
		endforeach ?>
						</td>
	<?php endif ?>
					</tr>
<?php endforeach ?>
				</tbody>
			</table>
		</div>
	</form>
</section>