<?php
/**
 * This file is part of Oyst_OneClick for Magento.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author Oyst <plugin@oyst.com> <@oyst>
 * @category Oyst
 * @package Oyst_OneClick
 * @copyright Copyright (c) 2017 Oyst (http://www.oyst.com)
 */

/**
 * Order Model
 */
class Oyst_OneClick_Model_Magento_Tax_Rule_Builder
{
    const TAX_CLASS_NAME_PRODUCT  = 'M2E Pro Product Tax Class';
    const TAX_CLASS_NAME_CUSTOMER = 'M2E Pro Customer Tax Class';

    const TAX_RATE_CODE = 'M2E Pro Tax Rate';
    const TAX_RULE_CODE = 'M2E Pro Tax Rule';

    /** @var $rule Mage_Tax_Model_Calculation_Rule */
    private $rule = null;

    //########################################

    public function getRule()
    {
        return $this->rule;
    }

    //########################################

    public function buildTaxRule($rate = 0, $countryId, $customerTaxClassId = null)
    {
        // Init product tax class
        // ---------------------------------------
        $productTaxClass = Mage::getModel('tax/class')->getCollection()
            ->addFieldToFilter('class_name', self::TAX_CLASS_NAME_PRODUCT)
            ->addFieldToFilter('class_type', Mage_Tax_Model_Class::TAX_CLASS_TYPE_PRODUCT)
            ->getFirstItem();

        if (null === $productTaxClass->getId()) {
            $productTaxClass->setClassName(self::TAX_CLASS_NAME_PRODUCT)
                ->setClassType(Mage_Tax_Model_Class::TAX_CLASS_TYPE_PRODUCT);
            $productTaxClass->save();
        }
        // ---------------------------------------

        // Init customer tax class
        // ---------------------------------------
        if (null === $customerTaxClassId) {
            $customerTaxClass = Mage::getModel('tax/class')->getCollection()
                ->addFieldToFilter('class_name', self::TAX_CLASS_NAME_CUSTOMER)
                ->addFieldToFilter('class_type', Mage_Tax_Model_Class::TAX_CLASS_TYPE_CUSTOMER)
                ->getFirstItem();

            if (null === $customerTaxClass->getId()) {
                $customerTaxClass->setClassName(self::TAX_CLASS_NAME_CUSTOMER)
                    ->setClassType(Mage_Tax_Model_Class::TAX_CLASS_TYPE_CUSTOMER);
                $customerTaxClass->save();
            }

            $customerTaxClassId = $customerTaxClass->getId();
        }
        // ---------------------------------------

        // Init tax rate
        // ---------------------------------------
        $taxCalculationRate = Mage::getModel('tax/calculation_rate')->load(self::TAX_RATE_CODE, 'code');

        $taxCalculationRate->setCode(self::TAX_RATE_CODE)
            ->setRate((float)$rate)
            ->setTaxCountryId((string)$countryId)
            ->setTaxPostcode('*')
            ->setTaxRegionId(0);
        $taxCalculationRate->save();
        // ---------------------------------------

        // Combine tax classes and tax rate in tax rule
        // ---------------------------------------
        $this->rule = Mage::getModel('tax/calculation_rule')->load(self::TAX_RULE_CODE, 'code');

        $this->rule->setCode(self::TAX_RULE_CODE)
            ->setTaxCustomerClass(array($customerTaxClassId))
            ->setTaxProductClass(array($productTaxClass->getId()))
            ->setTaxRate(array($taxCalculationRate->getId()));
        $this->rule->save();
        // ---------------------------------------
    }

    //########################################
}