<? self::$title = $error['code'].' | '.self::$title ?>
<div class="sq-error">
	<h2><?=$error['code'] ?></h2>
	<p class="sq-error-message">Something went wrong. This page couldn&rsquo;t be generated.</p>
	<p class="sq-error-link"><a href="<?=$base?>">Go back home</a></p>
</div>