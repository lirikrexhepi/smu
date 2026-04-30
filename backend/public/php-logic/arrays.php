<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$currentPage = 'arrays';
$pageTitle = 'Arrays + Loops + Functions';

$byProgram = countByProgram($students);
$allGpas = getGpaValues($students);
$averageGpa = array_sum($allGpas) / max(1, count($allGpas));

require __DIR__ . '/includes/header.php';
?>
<h2>Arrays / Loops / Functions</h2>
<p>This page makes arrays and loops explicit with helper functions.</p>

<h3>1) Program Totals (array + foreach + function)</h3>
<table>
    <thead>
    <tr>
        <th>Program</th>
        <th>Total Students</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($byProgram as $program => $total): ?>
        <tr>
            <td><?= htmlspecialchars($program) ?></td>
            <td><?= $total ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h3>2) GPA Array (sorted with <code>sort()</code>)</h3>
<p>
    Values:
    <?php foreach ($allGpas as $gpa): ?>
        <code><?= number_format($gpa, 2) ?></code>
    <?php endforeach; ?>
</p>
<p><strong>Average GPA:</strong> <?= number_format($averageGpa, 2) ?></p>
<?php require __DIR__ . '/includes/footer.php'; ?>
