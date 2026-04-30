<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$currentPage = 'inheritance';
$pageTitle = 'Inheritance + Getters/Setters';

require __DIR__ . '/includes/header.php';

$example = $students[0];
$example->setFullName('Luan Berisha Updated');
$example->setGpa(8.65);
?>
<h2>OOP Inheritance (Explicit)</h2>
<p>Inheritance chain used in this demo:</p>
<ul>
    <li><code>Person</code> (base class with common properties + getter/setter)</li>
    <li><code>Student extends Person</code></li>
    <li><code>HonorStudent extends Student</code></li>
</ul>

<h3>Getter/Setter Example</h3>
<p>The first student was updated using setters and then read back using getters.</p>
<table>
    <thead>
    <tr>
        <th>Getter</th>
        <th>Value</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td><code>getId()</code></td>
        <td><?= htmlspecialchars($example->getId()) ?></td>
    </tr>
    <tr>
        <td><code>getFullName()</code></td>
        <td><?= htmlspecialchars($example->getFullName()) ?></td>
    </tr>
    <tr>
        <td><code>getEmail()</code></td>
        <td><?= htmlspecialchars($example->getEmail()) ?></td>
    </tr>
    <tr>
        <td><code>getProgram()</code></td>
        <td><?= htmlspecialchars($example->getProgram()) ?></td>
    </tr>
    <tr>
        <td><code>getGpa()</code></td>
        <td><?= number_format($example->getGpa(), 2) ?></td>
    </tr>
    </tbody>
</table>
<?php require __DIR__ . '/includes/footer.php'; ?>
