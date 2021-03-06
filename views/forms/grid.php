<div class="sq-grid">

<?=$this->context('grid-content') ?>

<input type="hidden" id="sort-keys" name="sort-keys"/>

<div class="sq-picker-path" data-path="<?=sq::request()->get('path') ?>"></div>

<? if (sq::request()->get('path') && $model->options['path'] != sq::request()->get('path')): ?>
	<div class="sq-grid-item">
		<a class="sq-picker-folder sq-picker-parent" href="<?=sq::route()->to([
			'controller' => 'files',
			'model' => 'files',
			'path' => dirname(sq::request()->get('path')),
		]) ?>">
			<svg viewBox="0 0 256 256" height="100" width="100">
				<path d="m4.5 34.74c-2.4852 0.000248-4.4998 2.0148-4.5 4.5v177.53c0.00024848 2.4852 2.0148 4.4998 4.5 4.5l247-0.002c2.4852-0.00025 4.4998-2.0148 4.5-4.5v-160.27c-0.00025-2.4852-2.0148-4.4998-4.5-4.5h-150.23l-9.8164-15.197c-0.82878-1.2831-2.2518-2.0582-3.7793-2.0586h-83.172z" fill-rule="evenodd" fill="#6989ad"/>
				<g transform="scale(.5) translate(100,120)">
					<polygon points="35.7,247.35 153,130.05 270.3,247.35 306,211.65 153,58.65 0,211.65" fill="#ddd"/>
				</g>
			</svg>

			<span class="sq-grid-label" title="Parent"><?=listing::text('Parent') ?></span>
		</a>
	</div>
<? endif ?>

<? foreach ($model as $item): ?>
	<div class="sq-grid-item" data-id="<?=$item->id ?>">
		<? if ($item->type == 'file'): ?>
			<a href="<?=$item->url ?>">
				<img data-src="<?=$item->url ?>" src="<?=$item->variant('small') ?>"/>
				<span class="sq-grid-label" title="<?=listing::text($item->name) ?>"><?=listing::text($item->name) ?></span>
			</a>
		<? else: ?>
			<a class="sq-picker-folder" href="<?=sq::route()->to([
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
		<? // @TODO Fix this hack ?>
		<? if (!sq::request()->isAjax): ?>
			<?=$this->render('admin/forms/actions', ['item' => $item]) ?>
		<? endif ?>
	</div>
<? endforeach ?>

<?=$this->end() ?>

</div>