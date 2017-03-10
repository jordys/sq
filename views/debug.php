<? self::$title = $error['code'].' | '.self::$title ?>
<section class="sq-debug">
	<h2>Error <?=$error['code'] ?></h2>
	<? if (isset($error['debug'])): ?>
		<p class="sq-debug-message"><?=$error['debug'] ?></p>
	<? endif ?>
	<? if (isset($error['line'])): ?>
		<p class="sq-debug-error"><strong>Line #<?=$error['line'] ?></strong> <?=$error['string'] ?> in <?=$error['file'] ?></p>
		
		<div class="sq-debug-trace">
			<h2>Trace</h2>
			<? foreach ($error['trace'] as $line):
				if (!isset($line['class'])):
					$line['class'] = null;
				endif;
				
				if (!isset($line['type'])):
					$line['type'] = null;
				endif;
				
				if (isset($line['args'])):
					$args = [];
					foreach ($line['args'] as $arg):
						if (is_object($arg) && get_class($arg) == 'view'):
							$args[] = "[$arg->view]";
						elseif (is_object($arg)):
							$args[] = ucwords(get_class($arg));
						elseif (is_array($arg)):
							$args[] = 'Array';
						else:
							$args[] = $arg;
						endif;
					endforeach;
					
					$line['args'] = '('.implode(', ', $args).')';
				endif;
				
				?>
				<p>
					<? if (isset($line['line']) && isset($line['file'])): ?>
						<strong>Line #<?=$line['line'] ?></strong>
						<?=$line['file'] ?>
					<? else: ?>
						<strong>No Line #</strong>
					<? endif ?>
					<?=$line['class'].$line['type'].$line['function'].$line['args'] ?>
				</p>
			<? endforeach ?>
		</div>
	<? endif ?>
</section>