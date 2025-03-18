<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();
$config
    ->ignoreErrorsOnPackage('contao/image', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackage('symfony/translation-contracts', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackage('codefog/contao-haste', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('mvo/contao-group-widget', [ErrorType::UNUSED_DEPENDENCY]);

return $config;
