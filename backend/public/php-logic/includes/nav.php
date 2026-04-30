<?php

declare(strict_types=1);

$currentPage = $currentPage ?? 'home';
?>
<nav>
    <a class="<?= $currentPage === 'home' ? 'active' : '' ?>" href="/php-logic/index.php">Home</a>
    <a class="<?= $currentPage === 'inheritance' ? 'active' : '' ?>" href="/php-logic/inheritance.php">Inheritance</a>
    <a class="<?= $currentPage === 'sorting' ? 'active' : '' ?>" href="/php-logic/sorting.php">Sorting</a>
    <a class="<?= $currentPage === 'arrays' ? 'active' : '' ?>" href="/php-logic/arrays.php">Arrays/Loops</a>
</nav>
