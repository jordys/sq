<ul class="sq-breadcrumbs">
    <? $i = 0; foreach ($breadcrumbs as $name => $url): ?>
        <li>
            <? $i++ ?>
            <? if (!$url || $i === count($breadcrumbs)): ?>
                <span><?=$name ?></span>
            <? else: ?>
                <a href="<?=$url ?>"><?=$name ?></a>
            <? endif ?>
        </li>
    <? endforeach ?>
</ul>