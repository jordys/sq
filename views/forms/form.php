<section class="sq-form">
	<h2><?=$title ?></h2>
	<?=form::open($model, array('enctype' => 'multipart/form-data')) ?>

<? foreach ($fields as $name => $type):
	$type = explode('|', $type);
	
	$arg = false;
	if (isset($type[1])):
		$arg = $type[1];
	endif;
	$type = $type[0];
?>	
		<div class="sq-form-row sq-<?=$type ?>-form-row">
			<?=form::label($baseName.'['.$name.']', ucwords(str_replace('_', ' ', $name)), $type) ?>
			<? if ($arg):
				echo form::$type($name, $arg);
			else:
				echo form::$type($name);
			endif ?>
		</div>
<? endforeach ?>
		<div class="sq-actions sq-form-actions">
			<input class="sq-action sq-save-action" type="submit" name="button" value="Save"/>
			<a class="sq-cancel" href="<?=$_SERVER['HTTP_REFERER'] ?>">Cancel</a>
		</div>
	<?=form::close() ?>
</section>