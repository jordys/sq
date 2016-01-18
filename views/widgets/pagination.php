<ul class="sq-pagination">
	<?
	
	// Setup prev, next and page number ranges
	$options['visible-numbers']--;
	
	$prev = $currentPage - 1;
	$next = $currentPage + 1;
	$first = $currentPage - floor($options['visible-numbers'] / 2);
	$last = $currentPage + ceil($options['visible-numbers'] / 2);
	
	if ($first < 1):
		$last += ($first - 1) * -1;
		$first = 1;
	endif;
	
	if ($last > $pageCount):
		$first += $pageCount - $last;
		$last = $pageCount;
	endif;
	
	if ($first < 1):
		$first = 1;
	endif;
	
	if ($prev < 1):
		$prev = 1;
	endif;
	
	if ($next > $pageCount):
		$next = $pageCount;
	endif;
	
	// Setup URL objects
	$url = sq::route()->current();
	
	$firstUrl = clone($url);
	$lastUrl = clone($url);
	$prevUrl = clone($url);
	$nextUrl = clone($url);
	
	$firstUrl->append(array('page' => 1));
	$lastUrl->append(array('page' => $pageCount));
	$prevUrl->append(array('page' => $prev));
	$nextUrl->append(array('page' => $next));
	
	// First and prev links
	$class = null;
	if ($currentPage == 1):
		$class = 'is-active';
	endif;
	
	if ($options['last']): ?>
		<li class="first <?=$class ?>">
			<a href="<?=$firstUrl ?>"><?=$options['first'] ?></a>
		</li>
	<? endif;
	
	if ($options['prev']): ?>
		<li class="prev <?=$class ?>">
			<a href="<?=$prevUrl ?>"><?=$options['prev'] ?></a>
		</li>
	<? endif;
	
	// Page numbers
	for ($i = $first; $i <= $last; $i++):
		$url->append(array('page' => $i));
		
		$class = null;
		if ($i == $currentPage):
			$class = 'is-active';
		endif;
		
		echo '<li class="number '.$class.'">';
		echo '<a href="'.$url.'">'.$i.'</a></li>';
	endfor;
	
	// Next and last links
	$class = null;
	if ($currentPage == $pageCount):
		$class = 'is-active';
	endif;
	
	if ($options['next']): ?>
		<li class="next <?=$class ?>">
			<a href="<?=$nextUrl ?>"><?=$options['next'] ?></a>
		</li>
	<? endif;
	
	if ($options['last']): ?>
		<li class="last <?=$class ?>">
			<a href="<?=$lastUrl ?>"><?=$options['last'] ?></a>
		</li>
	<? endif ?>
</ul>