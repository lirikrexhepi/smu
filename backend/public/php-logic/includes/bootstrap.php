<?php

declare(strict_types=1);

require_once __DIR__ . '/../classes/Person.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/HonorStudent.php';
require_once __DIR__ . '/helpers.php';

/** @var array<int, Demo\Classes\Student> $students */
$students = require __DIR__ . '/../data/students.php';
