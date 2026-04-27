<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$currentPage = 'sorting';
$pageTitle = 'Sorting + PHP Arrays';

$sortedDescending = sortStudentsByGpa($students, 'desc');
$sortedAscending = sortStudentsByGpa($students, 'asc');

require __DIR__ . '/includes/header.php';
?>
<h2>Real Sorting Demo</h2>
<p>Sorting is implemented with <code>usort()</code> and GPA comparator logic.</p>

<h3>Top by GPA (DESC)</h3>
<table>
    <thead>
    <tr>
        <th>Rank</th>
        <th>Name</th>
        <th>GPA</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($sortedDescending as $index => $student): ?>
        <tr>
            <td><?= $index + 1 ?></td>
            <td><?= htmlspecialchars($student->getFullName()) ?></td>
            <td><?= number_format($student->getGpa(), 2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h3>Lowest to Highest (ASC)</h3>
<table>
    <thead>
    <tr>
        <th>Name</th>
        <th>GPA</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($sortedAscending as $student): ?>
        <tr>
            <td><?= htmlspecialchars($student->getFullName()) ?></td>
            <td><?= number_format($student->getGpa(), 2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/includes/footer.php'; ?>
