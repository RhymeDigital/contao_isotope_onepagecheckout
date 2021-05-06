<?php

declare(strict_types=1);

/**
 * Rhyme Contao Isotope One Page Checkout Bundle
 * Copyright (c) 2021 Rhyme.Digital
 * @license GPL-3.0+
 */


namespace Rhyme\ContaoIsotopeOnePageCheckoutBundle\Module;

use Contao\Controller;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\System;
use Contao\Template;
use FOS\HttpCache\ResponseTagger;
use Haste\Http\Response\JsonResponse;
use Haste\Util\Debug;
use Haste\Util\RepositoryVersion;
use Isotope\Interfaces\IsotopeProductCollection;
use Isotope\Isotope;
use Isotope\Module\Checkout;

class OnePageCheckout extends Checkout {

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_rhyme_onepagecheckout';

    /**
     * (Singleton)
     * @var OnePageCheckout
     */
    protected static $objInstance;

    /**
     * Load libraries and scripts
     *
     * @param \ModuleModel $objModule
     * @param string $strColumn
     */
    public function __construct($objModule, $strColumn = 'main')
    {
        parent::__construct($objModule, $strColumn);

        static::$objInstance = $this;

        //Load JavaScript and style sheet
        if ('FE' === TL_MODE) {
            $GLOBALS['TL_JAVASCRIPT']['onepagecheckout'] = 'bundles/rhymecontaoisotopeonepagecheckout/js/onepagecheckout.js|static';
            $GLOBALS['TL_CSS']['onepagecheckout'] = 'bundles/rhymecontaoisotopeonepagecheckout/css/onepagecheckout.css';
        }
    }

    /**
     * Display a wildcard in the back end
     * And also defer the loading of the parent module as we will handle that via AJAX
     *
     * @return string|array
     */
    public function generate()
    {
        if ('BE' === TL_MODE) {
            return $this->generateWildcard();
        }

        $this->strCurrentStep = \Haste\Input\Input::getAutoItem('step');

        if (Environment::get('isAjaxRequest')) {

            $this->setSkippableSteps();

            // User pressed "back" button
            if (null !== \Input::post('previousStep') && \strlen(\Input::post('previousStep'))) {
                //Unset the POST var so there is not a second redirect
                \Input::setPost('previousStep', null);
                $this->redirectToPreviousStep();
            } // Valid input data, generate step
            else {
                if ($this->strCurrentStep == '') {
                    $this->redirectToNextStep();
                } else {
                    static::redirectToStep($this->strCurrentStep);
                }
            }

        }

        //Need to go back to original parent Module here to avoid redirects, but DO NOT COMPILE YET unless....
        $this->Template = new FrontendTemplate($this->strTemplate);
        $this->Template->setData($this->arrData);

        //If there is a valid POST request (i.e. from a payment, etc), proceed with parent methods
        if ( \Input::post('FORM_SUBMIT') ) {
            $this->compile();
        }

        // Do not change this order (see #6191)
        $this->Template->style = !empty($this->arrStyle) ? implode(' ', $this->arrStyle) : '';
        $this->Template->class = trim('mod_' . $this->type . ' ' . $this->cssID[1]);
        $this->Template->cssID = !empty($this->cssID[0]) ? ' id="' . $this->cssID[0] . '"' : '';

        $this->Template->inColumn = $this->strColumn;

        if ($this->Template->headline == '')
        {
            $this->Template->headline = $this->headline;
        }

        if ($this->Template->hl == '')
        {
            $this->Template->hl = $this->hl;
        }

        if (!empty($this->objModel->classes) && \is_array($this->objModel->classes))
        {
            $this->Template->class .= ' ' . implode(' ', $this->objModel->classes);
        }

        // Tag the response
        if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
        {
            /** @var ResponseTagger $responseTagger */
            $responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
            $responseTagger->addTags(array('contao.db.tl_module.' . $this->id));
        }

        return $this->Template->parse();
    }

    /**
     * Return the module components for AJAX rendering
     *
     * @return array
     */
    public static function generateForAjax()
    {
        return static::$objInstance->generateParentComponents();
    }

    /**
     * Return an array of the parent components for AJAX rendering
     *
     * @return array
     */
    public function generateParentComponents()
    {
        $this->strTemplate = 'mod_rhyme_onepagecheckout_components';
        parent::generate();

        //Now get the components and parse the template for each one
        //TODO - Make these configurable?
        $arrComponents = ['steps', 'messages', 'actions'];
        if($this->iso_showCartSummary) {
            $arrComponents[] = 'summary';
            $this->Template->showSummaryPanel = true;
            $this->Template->getCartSummary = function() {
                return Controller::getFrontendModule($this->iso_cartSummaryModule);
            };
        }
        $arrReturn = [];
        foreach ($arrComponents as $component) {
            $this->Template->component = $component;
            $arrReturn[$component] = $this->Template->parse();
        }

        //Add any scripts not related to the 1PC that may not have been loaded
        $arrHead = [];
        $arrJavaScripts = [];
        $arrBody = [];

        if(\is_array($GLOBALS['TL_HEAD'])) {
            foreach ($GLOBALS['TL_HEAD'] as $key => $script) {
                if ($key !== 'onepagecheckout') {
                    $arrHead[$key] = $script;
                }
            }
        }
        if(\is_array($GLOBALS['TL_JAVASCRIPT'])) {
            foreach ($GLOBALS['TL_JAVASCRIPT'] as $key => $script) {
                if ($key !== 'onepagecheckout') {
                    $arrJavaScripts[$key] = $script;
                }
            }
        }
        if(\is_array($GLOBALS['TL_BODY'])) {
            foreach ($GLOBALS['TL_BODY'] as $key => $body) {
                if ($key !== 'onepagecheckout') {
                    $arrBody[$key] = $body;
                }
            }
        }
        $arrReturn['head']      = $arrHead;
        $arrReturn['scripts']   = $arrJavaScripts;
        $arrReturn['body']      = $arrBody;

        return $arrReturn;
    }


    /**
     * Redirect to given checkout step
     *
     * @param string $strStep
     * @param IsotopeProductCollection|null $objCollection
     */
    public static function redirectToStep($strStep, IsotopeProductCollection $objCollection = null)
    {
        Isotope::getCart()->save();

        //If there is a collection and it is not an AJAX request, we need to redirect normally
        if(!Environment::get('isAjaxRequest') && $objCollection !== null) {
            \Controller::redirect(static::generateUrlForStep($strStep, $objCollection));
        }

        \Haste\Input\Input::setGet('auto_item', $strStep);

        //Output the module with the next step instead of a redirect
        $arrResponse = ['step' => static::generateUrlForStep($strStep, $objCollection), 'components' => static::generateForAjax()];
        $response = new JsonResponse(\json_encode($arrResponse));
        $response->send(true);
    }

    /**
     * Set the skippable steps (separate from the generateSteps method)
     */
    protected function setSkippableSteps() {

        foreach ($this->getSteps() as $step => $arrModules) {
            $this->skippableSteps[$step] = true;
            foreach ($arrModules as $objModule) {
                if (!$objModule->isSkippable()) {
                    $this->skippableSteps[$step] = false;
                }
                if ($objModule->hasError()) {
                    $this->skippableSteps[$step]  = false;
                }
            }
        }
    }


}