<?php

declare(strict_types=1);

use Demo\Classes\HonorStudent;
use Demo\Classes\Student;

return [
    new Student('S1001', 'Luan Berisha', 'luan@sems.edu', 'Computer Science', 2, 8.40),
    new HonorStudent('S1002', 'Arta Dema', 'arta@sems.edu', 'Software Engineering', 3, 9.35, 600.00),
    new Student('S1003', 'Dren Gashi', 'dren@sems.edu', 'Data Science', 1, 7.95),
    new HonorStudent('S1004', 'Era Kelmendi', 'era@sems.edu', 'Computer Science', 4, 9.70, 850.00),
    new Student('S1005', 'Blerim Krasniqi', 'blerim@sems.edu', 'Information Systems', 2, 8.10),
];
