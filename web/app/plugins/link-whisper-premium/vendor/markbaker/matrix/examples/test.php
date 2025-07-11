<?php

namespace LWVendor;

use LWVendor\Matrix\Matrix;
use LWVendor\Matrix\Decomposition\QR;
include __DIR__ . '/../vendor/autoload.php';
$grid = [[0, 1], [-1, 0]];
$targetGrid = [[-1], [2]];
$matrix = new Matrix($grid);
$target = new Matrix($targetGrid);
$decomposition = new QR($matrix);
$X = $decomposition->solve($target);
echo 'X', \PHP_EOL;
\var_export($X->toArray());
echo \PHP_EOL;
$resolve = $matrix->multiply($X);
echo 'Resolve', \PHP_EOL;
\var_export($resolve->toArray());
echo \PHP_EOL;
