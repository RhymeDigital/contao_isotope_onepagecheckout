<?php

declare(strict_types=1);

/**
 * Rhyme Contao Isotope One Page Checkout Bundle
 * Copyright (c) 2021 Rhyme.Digital
 * @license GPL-3.0+
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][]                   = 'iso_showCartSummary';
$GLOBALS['TL_DCA']['tl_module']['palettes']['rhyme_onepagecheckout']            = $GLOBALS['TL_DCA']['tl_module']['palettes']['iso_checkout'];

PaletteManipulator::create()
    ->addLegend('summary_legend', 'template_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField('iso_showCartSummary', 'summary_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('rhyme_onepagecheckout', 'tl_module')
;

/**
 * Subpalettes
 */
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['iso_showCartSummary'] = 'iso_cartSummaryModule';

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['iso_showCartSummary'] = array
(
    'label'                     => &$GLOBALS['TL_LANG']['tl_module']['iso_showCartSummary'],
    'exclude'                   => true,
    'inputType'                 => 'checkbox',
    'eval'                      => array('submitOnChange'=>true, 'tl_class'=>'clr w50 m12'),
    'sql'                       => "char(1) NOT NULL default ''",
);
$GLOBALS['TL_DCA']['tl_module']['fields']['iso_cartSummaryModule'] = array
(
    'label'                     => &$GLOBALS['TL_LANG']['tl_module']['iso_cartSummaryModule'],
    'exclude'                   => true,
    'inputType'                 => 'select',
    'foreignKey'                => 'tl_module.name',
    'options_callback'          => array('Rhyme\ContaoIsotopeOnePageCheckoutBundle\Backend\Module\Callbacks', 'getCartModules'),
    'eval'                      => array('tl_class'=>'w50 clr'),
    'sql'                       => "int(10) unsigned NOT NULL default '0'",
    'relation'                  => array('type'=>'hasOne', 'load'=>'lazy'),
);