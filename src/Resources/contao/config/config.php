<?php

declare(strict_types=1);

/**
 * Rhyme Contao Isotope One Page Checkout Bundle
 * Copyright (c) 2021 Rhyme.Digital
 * @license GPL-3.0+
 */

namespace {

    use Contao\ArrayUtil;
    use Rhyme\ContaoIsotopeOnePageCheckoutBundle\Module;

    /**
     * Frontend Modules
     */
    ArrayUtil::arrayInsert($GLOBALS['FE_MOD']['isotope'], 99,
        [
            'rhyme_onepagecheckout'	=> Module\OnePageCheckout::class,
        ]
    );


    /**
     * Hooks
     */

}