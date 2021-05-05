<?php

declare(strict_types=1);

/**
 * Rhyme Contao Isotope One Page Checkout Bundle
 * Copyright (c) 2021 Rhyme.Digital
 * @license GPL-3.0+
 */

namespace Rhyme\ContaoIsotopeOnePageCheckoutBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Rhyme\ContaoIsotopeOnePageCheckoutBundle\RhymeContaoIsotopeOnePageCheckoutBundle;

final class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(RhymeContaoIsotopeOnePageCheckoutBundle::class)
                ->setLoadAfter(
                    [
                        ContaoCoreBundle::class,
                        'isotope'
                    ]
                ),
        ];
    }
}