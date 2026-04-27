<?php

declare(strict_types=1);

$currentPage = $currentPage ?? 'home';
?>
<nav>
    <a class="<?= $currentPage === 'home' ? 'active' : '' ?>" href="/php-demo/index.php">Home</a>
    <a class="<?= $currentPage === 'inheritance' ? 'active' : '' ?>" href="/php-demo/inheritance.php">Inheritance</a>
    <a class="<?= $currentPage === 'sorting' ? 'active' : '' ?>" href="/php-demo/sorting.php">Sorting</a>
    <a class="<?= $currentPage === 'arrays' ? 'active' : '' ?>" href="/php-demo/arrays.php">Arrays/Loops</a>
</nav>
