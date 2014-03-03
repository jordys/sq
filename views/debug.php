<? self::$title = $error['code'].' | '.self::$title ?>
<h2>Error <?=$error['code']?></h2>
<? if (isset($error['line'])): ?>
	<p><strong>Line #<?=$error['line']?></strong> <?=$error['string']?> in <?=$error['file']?></p>
	<h2>Trace</h2>
	<? foreach ($error['trace'] as $line): ?>
		<p><strong>Line #<?=$line['line']?></strong> <?=$line['file']?> <?=$line['class'],$line['type'],$line['function']?></p>
	<? endforeach ?>
<? endif ?>