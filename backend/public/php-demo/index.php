<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$currentPage = 'home';
$pageTitle = 'PHP Demo Home';

require __DIR__ . '/includes/header.php';
?>
<h2>Project Task Demo</h2>
<p>This section adds the requested PHP parts with the same style used in this project.</p>
<ul>
    <li>Explicit OOP inheritance (<code>Person -> Student -> HonorStudent</code>)</li>
    <li>Getters and setters in the main classes</li>
    <li>Real sorting with PHP arrays</li>
    <li>Arrays, loops and functions shown clearly</li>
    <li>4 PHP pages with shared <code>includes</code> structure</li>
</ul>

<h3>Quick Dataset Preview</h3>
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Program</th>
        <th>Year</th>
        <th>GPA</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($students as $student): ?>
        <tr>
            <td><?= htmlspecialchars($student->getId()) ?></td>
            <td><?= htmlspecialchars($student->getFullName()) ?></td>
            <td><?= htmlspecialchars($student->getProgram()) ?></td>
            <td><?= $student->getYear() ?></td>
            <td><?= number_format($student->getGpa(), 2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/includes/footer.php'; ?>
