<div class="sq-grid">
	<div class="sq-grid-sizer"></div>

<?=$this->context('grid-content') ?>

<? if (sq::request()->get('path') && $model->options['path'] != sq::request()->get('path')): ?>
	<div class="sq-grid-item">
		<a class="sq-picker-folder" href="<?=sq::route()->to([
			'module' => 'admin',
			'controller' => 'files',
			'model' => 'files',
			'path' => dirname(sq::request()->get('path')),
		]) ?>">
			<svg viewBox="0 0 256 256" height="100" width="100">
				<path d="m4.5 34.74c-2.4852 0.000248-4.4998 2.0148-4.5 4.5v177.53c0.00024848 2.4852 2.0148 4.4998 4.5 4.5l247-0.002c2.4852-0.00025 4.4998-2.0148 4.5-4.5v-160.27c-0.00025-2.4852-2.0148-4.4998-4.5-4.5h-150.23l-9.8164-15.197c-0.82878-1.2831-2.2518-2.0582-3.7793-2.0586h-83.172z" fill-rule="evenodd" fill="#6989ad"/>
			</svg>
			
			<span class="sq-grid-label" title="<?=listing::text($item->name) ?>"><?=listing::text('Parent') ?></span>
		</a>
	</div>
<? endif ?>

<? foreach ($model as $item): ?>
	<div class="sq-grid-item">
		<? if ($item->type == 'file'): ?>
			<a href="<?=$item->url ?>">
				<?=listing::image($item->url) ?>
				<span class="sq-grid-label" title="<?=listing::text($item->name) ?>"><?=listing::text($item->name) ?></span>
			</a>
		<? else: ?>
			<a class="sq-picker-folder" href="<?=sq::route()->to([
				'module' => 'admin',
				'controller' => 'files',
				'model' => 'files',
				'path' => urlencode($item->path.'/'.$item->file)
			]) ?>">
				<svg viewBox="0 0 256 256" height="100" width="100">
					<path d="m4.5 34.74c-2.4852 0.000248-4.4998 2.0148-4.5 4.5v177.53c0.00024848 2.4852 2.0148 4.4998 4.5 4.5l247-0.002c2.4852-0.00025 4.4998-2.0148 4.5-4.5v-160.27c-0.00025-2.4852-2.0148-4.4998-4.5-4.5h-150.23l-9.8164-15.197c-0.82878-1.2831-2.2518-2.0582-3.7793-2.0586h-83.172z" fill-rule="evenodd" fill="#6989ad"/>
				</svg>

				<span class="sq-grid-label" title="<?=listing::text($item->name) ?>"><?=listing::text($item->name) ?></span>
			</a>
		<? endif ?>
	</div>
<? endforeach ?>

<?=$this->end() ?>

</div>