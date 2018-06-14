<?=form::hidden('path') ?>
<? // @TODO: Make form fields able to ignore the array syntax of their parent
   // form and fix this ?>
<input type="file" name="upload" />
<button>Upload</button>
<?=$this->render('forms/grid') ?>