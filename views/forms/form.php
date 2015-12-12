<section class="form edit-form">
	<h2><?=ucwords(sq::request()->get('action')) ?></h2>
	<?=form::open($model, array('enctype' => 'multipart/form-data')) ?>

<? foreach ($fields as $name => $type):
	$type = explode('|', $type);
	
	$arg = false;
	if (isset($type[1])):
		$arg = $type[1];
	endif;
	$type = $type[0];
?>	
		<div class="form-block">
			<?=form::label($baseName.'['.$name.']', ucwords(str_replace('_', ' ', $name)), $type) ?>
			<? if ($arg):
				echo form::$type($name, $arg);
			else:
				echo form::$type($name);
			endif ?>
		</div>
<? endforeach ?>
		<div class="actions global-actions form-actions">
			<input type="submit" name="button" value="Save"/>
			<a class="cancel form-cancel" href="<?=$_SERVER['HTTP_REFERER'] ?>">Cancel</a>
		</div>
	<?=form::close() ?>
</section>