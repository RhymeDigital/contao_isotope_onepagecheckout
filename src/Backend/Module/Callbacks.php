<?php

declare(strict_types=1);

/**
 * Rhyme Contao Isotope One Page Checkout Bundle
 * Copyright (c) 2021 Rhyme.Digital
 * @license GPL-3.0+
 */

namespace Rhyme\ContaoIsotopeOnePageCheckoutBundle\Backend\Module;

use Contao\Backend;

/**
 * Class Callbacks
 * @package Rhyme\MRMBundle\Backend\Module
 */
class Callbacks extends Backend
{
    /**
     * Load tl_iso_product data container and language file
     */
    public function __construct()
    {
        parent::__construct();

        \Controller::loadDataContainer('tl_iso_product');
        \System::loadLanguageFile('tl_iso_product');
    }

    /**
     * Get all cart modules and return them as array
     * @return array
     */
    public function getCartModules()
    {
        $arrModules = array();
        $objModules = \Database::getInstance()->execute("SELECT id, name FROM tl_module WHERE type='iso_cart'");

        while ($objModules->next()) {
            $arrModules[$objModules->id] = $objModules->name;
        }

        return $arrModules;
    }
}