<? self::$title = $error['code'].' | '.self::$title ?>
<h2>Error <?=$error['code']?></h2>
<? if (isset($error['debug'])): ?>
	<p><?=$error['debug'] ?></p>
<? endif ?>
<? if (isset($error['line'])): ?>
	<p><strong>Line #<?=$error['line']?></strong> <?=$error['string']?> in <?=$error['file']?></p>
	<h2>Trace</h2>
	<? foreach ($error['trace'] as $line):
		if (!isset($line['class'])):
			$line['class'] = null;
		endif;
		
		if (!isset($line['type'])):
			$line['type'] = null;
		endif;
		
		if (isset($line['args'])):
			$line['args'] = '('.implode(', ', $line['args']).')';
		endif;
		
		?>
		<p><strong>Line #<?=$line['line']?></strong> <?=$line['file']?> <?=$line['class'],$line['type'],$line['function'].$line['args'] ?></p>
	<? endforeach ?>
<? endif ?>