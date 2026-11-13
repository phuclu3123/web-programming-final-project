<?php

/**
 * Reusable server-rendered pagination; pass $pageNav from the parent view.
 *
 * @var array{page:int,pages:int,makeUrl:callable,ariaLabel:string,navClass?:string,linkClass?:string} $pageNav
 *
 * Use require (not require_once): a template may be rendered more than once.
 */
$_paginationCurrent = max(1, (int) $pageNav['page']);
$_paginationPages = max(1, (int) $pageNav['pages']);
$_paginationMakeUrl = $pageNav['makeUrl'];
$_paginationNavClass = (string) ($pageNav['navClass'] ?? 'd-flex flex-wrap gap-2 mt-3');
$_paginationLinkClass = (string) ($pageNav['linkClass'] ?? 'btn btn-outline-secondary');
?>
<?php if ($_paginationPages > 1): ?>
<nav class="<?= e($_paginationNavClass) ?>" aria-label="<?= e((string) $pageNav['ariaLabel']) ?>">
    <?php for ($_paginationTarget = max(1, $_paginationCurrent - 2); $_paginationTarget <= min($_paginationPages, $_paginationCurrent + 2); ++$_paginationTarget): ?>
    <a class="<?= e($_paginationLinkClass) ?>" href="<?= e($_paginationMakeUrl($_paginationTarget)) ?>"
        <?= $_paginationTarget === $_paginationCurrent ? 'aria-current="page"' : '' ?>><?= $_paginationTarget ?></a>
    <?php endfor; ?>
</nav>
<?php endif; ?>