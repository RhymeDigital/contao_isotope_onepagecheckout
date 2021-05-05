<?php

declare(strict_types=1);

/**
 * Rhyme Contao Isotope One Page Checkout Bundle
 * Copyright (c) 2021 Rhyme.Digital
 * @license GPL-3.0+
 */

namespace Rhyme\ContaoIsotopeOnePageCheckoutBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Rhyme\ContaoIsotopeOnePageCheckoutBundle\DependencyInjection\RhymeContaoIsotopeOnePageCheckoutExtension;

/**
 * Configures the bundle
 */
final class RhymeContaoIsotopeOnePageCheckoutBundle extends Bundle
{
    /**
     * @return RhymeContaoIsotopeOnePageCheckoutExtension
     */
    public function getContainerExtension()
    {
        return new RhymeContaoIsotopeOnePageCheckoutExtension();
    }
}