<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2017 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use CoreShop\Behat\Service\SharedStorageInterface;
use CoreShop\Bundle\CoreBundle\Form\Type\Rule\Condition\CategoriesConfigurationType;
use CoreShop\Bundle\CoreBundle\Form\Type\Rule\Condition\CountriesConfigurationType;
use CoreShop\Bundle\CoreBundle\Form\Type\Rule\Condition\CurrenciesConfigurationType;
use CoreShop\Bundle\CoreBundle\Form\Type\Rule\Condition\CustomerGroupsConfigurationType;
use CoreShop\Bundle\CoreBundle\Form\Type\Rule\Condition\CustomersConfigurationType;
use CoreShop\Bundle\CoreBundle\Form\Type\Rule\Condition\ProductsConfigurationType;
use CoreShop\Bundle\CoreBundle\Form\Type\Rule\Condition\StoresConfigurationType;
use CoreShop\Bundle\CoreBundle\Form\Type\Rule\Condition\ZonesConfigurationType;
use CoreShop\Bundle\ResourceBundle\Form\Registry\FormTypeRegistryInterface;
use CoreShop\Bundle\ShippingBundle\Form\Type\Rule\Condition\AmountConfigurationType;
use CoreShop\Bundle\ShippingBundle\Form\Type\Rule\Condition\DimensionConfigurationType;
use CoreShop\Bundle\ShippingBundle\Form\Type\Rule\Condition\PostcodeConfigurationType;
use CoreShop\Bundle\ShippingBundle\Form\Type\Rule\Condition\WeightConfigurationType;
use CoreShop\Bundle\ShippingBundle\Form\Type\ShippingRuleConditionType;
use CoreShop\Component\Address\Model\ZoneInterface;
use CoreShop\Component\Core\Model\CarrierInterface;
use CoreShop\Component\Core\Model\CategoryInterface;
use CoreShop\Component\Core\Model\CountryInterface;
use CoreShop\Component\Core\Model\CurrencyInterface;
use CoreShop\Component\Core\Model\CustomerInterface;
use CoreShop\Component\Core\Model\ProductInterface;
use CoreShop\Component\Core\Model\StoreInterface;
use CoreShop\Component\Core\Repository\CarrierRepositoryInterface;
use CoreShop\Component\Customer\Model\CustomerGroupInterface;
use CoreShop\Component\Resource\Factory\FactoryInterface;
use CoreShop\Component\Rule\Model\ActionInterface;
use CoreShop\Component\Rule\Model\Condition;
use CoreShop\Component\Rule\Model\ConditionInterface;
use CoreShop\Component\Shipping\Model\ShippingRuleInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\FormFactoryInterface;

final class ShippingContext implements Context
{
    use ConditionFormTrait;

    /**
     * @var SharedStorageInterface
     */
    private $sharedStorage;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var FormTypeRegistryInterface
     */
    private $conditionFormTypeRegistry;

    /**
     * @var FormTypeRegistryInterface
     */
    private $actionFormTypeRegistry;

    /**
     * @var CarrierRepositoryInterface
     */
    private $carrierRepository;

    /**
     * @var FactoryInterface
     */
    private $carrierFactory;

    /**
     * @var FactoryInterface
     */
    private $shippingRuleFactory;

    /**
     * @param SharedStorageInterface $sharedStorage
     * @param ObjectManager $objectManager
     * @param FormFactoryInterface $formFactory
     * @param FormTypeRegistryInterface $conditionFormTypeRegistry
     * @param FormTypeRegistryInterface $actionFormTypeRegistry
     * @param CarrierRepositoryInterface $carrierRepository
     * @param FactoryInterface $carrierFactory
     * @param FactoryInterface $shippingRuleFactory
     */
    public function __construct(
        SharedStorageInterface $sharedStorage,
        ObjectManager $objectManager,
        FormFactoryInterface $formFactory,
        FormTypeRegistryInterface $conditionFormTypeRegistry,
        FormTypeRegistryInterface $actionFormTypeRegistry,
        CarrierRepositoryInterface $carrierRepository,
        FactoryInterface $carrierFactory,
        FactoryInterface $shippingRuleFactory
    )
    {
        $this->sharedStorage = $sharedStorage;
        $this->objectManager = $objectManager;
        $this->formFactory = $formFactory;
        $this->conditionFormTypeRegistry = $conditionFormTypeRegistry;
        $this->actionFormTypeRegistry = $actionFormTypeRegistry;
        $this->carrierRepository = $carrierRepository;
        $this->carrierFactory = $carrierFactory;
        $this->shippingRuleFactory = $shippingRuleFactory;
    }

    /**
     * @Given /^the site has a carrier "([^"]+)"$/
     */
    public function theSiteHasACarrier($name)
    {
        $this->createCarrier($name);
    }

    /**
     * @Given /^adding a shipping rule named "([^"]+)"$/
     */
    public function addingAShippingRule($ruleName)
    {
        /**
         * @var $rule ShippingRuleInterface
         */
        $rule = $this->shippingRuleFactory->createNew();
        $rule->setName($ruleName);

        $this->objectManager->persist($rule);
        $this->objectManager->flush();

        $this->sharedStorage->set('shipping-rule', $rule);
    }

    /**
     * @Given /^the (shipping rule "[^"]+") has a condition amount from "([^"]+)" to "([^"]+)"$/
     * @Given /^the (shipping rule) has a condition amount from "([^"]+)" to "([^"]+)"$/
     */
    public function theShippingRuleHasAAmountCondition(ShippingRuleInterface $rule, $minAmount, $maxAmount)
    {
        $this->assertConditionForm(AmountConfigurationType::class, 'amount');

        $this->addCondition($rule, $this->createConditionWithForm('amount', [
            'minAmount' => $minAmount,
            'maxAmount' => $maxAmount
        ]));
    }

    /**
     * @Given /^the (shipping rule "[^"]+") has a condition postcode with "([^"]+)"$/
     * @Given /^the (shipping rule) has a condition postcode with "([^"]+)"$/
     */
    public function theShippingRuleHasAPostcodeCondition(ShippingRuleInterface $rule, $postcodes)
    {
        $this->assertConditionForm(PostcodeConfigurationType::class, 'postcodes');

        $this->addCondition($rule, $this->createConditionWithForm('postcodes', [
            'postcodes' => $postcodes,
            'exclusion' => false
        ]));
    }

    /**
     * @Given /^the (shipping rule "[^"]+") has a condition postcode exclusion with "([^"]+)"$/
     * @Given /^the (shipping rule) has a condition postcode exclusion with "([^"]+)"$/
     */
    public function theShippingRuleHasAPostcodeExclusionCondition(ShippingRuleInterface $rule, $postcodes)
    {
        $this->assertConditionForm(PostcodeConfigurationType::class, 'postcodes');

        $this->addCondition($rule, $this->createConditionWithForm('postcodes', [
            'postcodes' => $postcodes,
            'exclusion' => true
        ]));
    }

    /**
     * @Given /^the (shipping rule "[^"]+") has a condition weight from "([^"]+)" to "([^"]+)"$/
     * @Given /^the (shipping rule) has a condition weight from "([^"]+)" to "([^"]+)"$/
     */
    public function theShippingRuleHasAWeightCondition(ShippingRuleInterface $rule, $minWeight, $maxWeight)
    {
        $this->assertConditionForm(WeightConfigurationType::class, 'weight');

        $this->addCondition($rule, $this->createConditionWithForm('weight', [
            'minWeight' => $minWeight,
            'maxWeight' => $maxWeight
        ]));
    }

    /**
     * @Given /^the (shipping rule "[^"]+") has a condition dimension with ([^"]+)x([^"]+)x([^"]+)$/
     * @Given /^the (shipping rule) has a condition dimension with ([^"]+)x([^"]+)x([^"]+)$/
     */
    public function theShippingRuleHasADimensionCondition(ShippingRuleInterface $rule, $width, $height, $depth)
    {
        $this->assertConditionForm(DimensionConfigurationType::class, 'dimension');

        $this->addCondition($rule, $this->createConditionWithForm('dimension', [
            'width' => $width,
            'height' => $height,
            'depth' => $depth
        ]));
    }

    /**
     * @Given /^the (shipping rule "[^"]+") has a condition categories with (category "[^"]+")$/
     * @Given /^the (shipping rule) has a condition categories with (category "[^"]+")$/
     */
    public function theShippingRuleHasACategoriesCondition(ShippingRuleInterface $rule, CategoryInterface $category)
    {
        $this->assertConditionForm(CategoriesConfigurationType::class, 'categories');

        $this->addCondition($rule, $this->createConditionWithForm('categories', [
            'categories' => [$category->getId()]
        ]));
    }

    /**
     * @Given /^the (shipping rule "[^"]+") has a condition categories with (categories "[^"]+", "[^"]+")$/
     * @Given /^the (shipping rule) has a condition categories with (categories "[^"]+", "[^"]+")$/
     */
    public function theShippingRuleHasACategoriesConditionWithTwoCategories(ShippingRuleInterface $rule, array $categories)
    {
        $this->assertConditionForm(CategoriesConfigurationType::class, 'categories');

        $this->addCondition($rule, $this->createConditionWithForm('categories', [
            'categories' => array_map(function($category) {return $category->getId();}, $categories)
        ]));
    }

    /**
     * @Given /^the (shipping rule "[^"]+") has a condition products with (product "[^"]+")$/
     * @Given /^the (shipping rule) has a condition products with (product "[^"]+")$/
     */
    public function theShippingRuleHasAProductsCondition(ShippingRuleInterface $rule, ProductInterface $product)
    {
        $this->assertConditionForm(ProductsConfigurationType::class, 'products');

        $this->addCondition($rule, $this->createConditionWithForm('products', [
            'products' => [$product->getId()]
        ]));
    }

    /**
     * @Given /^the (shipping rule "[^"]+") has a condition products with (products "[^"]+", "[^"]+")$/
     * @Given /^the (shipping rule) has a condition products with (products "[^"]+", "[^"]+")$/
     */
    public function theShippingRuleHasAProductsConditionWithTwoProducts(ShippingRuleInterface $rule, array $products)
    {
        $this->assertConditionForm(ProductsConfigurationType::class, 'products');

        $this->addCondition($rule, $this->createConditionWithForm('products', [
            'products' => array_map(function($product) {return $product->getId();}, $products)
        ]));
    }

    /**
     * @Given /^the (shipping rule "[^"]+") has a condition countries with (country "[^"]+")$/
     * @Given /^the (shipping rule) has a condition countries with (country "[^"]+")$/
     */
    public function theShippingRuleHasACountriesCondition(ShippingRuleInterface $rule, CountryInterface $country)
    {
        $this->assertConditionForm(CountriesConfigurationType::class, 'countries');

        $this->addCondition($rule, $this->createConditionWithForm('countries', [
            'countries' => [$country->getId()]
        ]));
    }

    /**
     * @Given /^the (shipping rule "[^"]+") has a condition customers with (customer "[^"]+")$/
     * @Given /^the (shipping rule) has a condition customers with (customer "[^"]+")$/
     */
    public function theShippingRuleHasACustomersCondition(ShippingRuleInterface $rule, CustomerInterface $customer)
    {
        $this->assertConditionForm(CustomersConfigurationType::class, 'customers');

        $this->addCondition($rule, $this->createConditionWithForm('customers', [
            'customers' => [$customer->getId()]
        ]));
    }

    /**
     * @Given /^the (shipping rule "[^"]+") has a condition customer-groups with (customer-group "[^"]+")$/
     * @Given /^the (shipping rule) has a condition customer-groups with (customer-group "[^"]+")$/
     */
    public function theShippingRuleHasACustomerGroupsCondition(ShippingRuleInterface $rule, CustomerGroupInterface $customerGroup)
    {
        $this->assertConditionForm(CustomerGroupsConfigurationType::class, 'customerGroups');

        $this->addCondition($rule, $this->createConditionWithForm('customerGroups', [
            'customerGroups' => [$customerGroup->getId()]
        ]));
    }

    /**
     * @Given /^the (shipping rule "[^"]+") has a condition zones with (zone "[^"]+")$/
     * @Given /^the (shipping rule) has a condition zones with (zone "[^"]+")$/
     */
    public function theShippingRuleHasAZonesCondition(ShippingRuleInterface $rule, ZoneInterface $zone)
    {
        $this->assertConditionForm(ZonesConfigurationType::class, 'zones');

        $this->addCondition($rule, $this->createConditionWithForm('zones', [
            'zones' => [$zone->getId()]
        ]));
    }

    /**
     * @Given /^the (shipping rule "[^"]+") has a condition stores with (store "[^"]+")$/
     * @Given /^the (shipping rule) has a condition stores with (store "[^"]+")$/
     */
    public function theShippingRuleHasAStoresCondition(ShippingRuleInterface $rule, StoreInterface $store)
    {
        $this->assertConditionForm(StoresConfigurationType::class, 'stores');

        $this->addCondition($rule, $this->createConditionWithForm('stores', [
            'stores' => [$store->getId()]
        ]));
    }

    /**
     * @Given /^the (shipping rule "[^"]+") has a condition currencies with (currency "[^"]+")$/
     * @Given /^the (shipping rule) has a condition currencies with (currency "[^"]+")$/
     */
    public function theShippingRuleHasACurrenciesCondition(ShippingRuleInterface $rule, CurrencyInterface $currency)
    {
        $this->assertConditionForm(CurrenciesConfigurationType::class, 'currencies');

        $this->addCondition($rule, $this->createConditionWithForm('currencies', [
            'currencies' => [$currency->getId()]
        ]));
    }

    /**
     * @param $name
     */
    private function createCarrier($name)
    {
        /**
         * @var $carrier CarrierInterface
         */
        $carrier = $this->carrierFactory->createNew();
        $carrier->setName($name);

        $this->saveCarrier($carrier);
    }

    /**
     * @param CarrierInterface $carrier
     */
    private function saveCarrier(CarrierInterface $carrier)
    {
        $this->objectManager->persist($carrier);
        $this->objectManager->flush();

        $this->sharedStorage->set('carrier', $carrier);
    }

    /**
     * @param ShippingRuleInterface $rule
     * @param ConditionInterface $condition
     */
    private function addCondition(ShippingRuleInterface $rule, ConditionInterface $condition)
    {
        $rule->addCondition($condition);

        $this->objectManager->persist($rule);
        $this->objectManager->flush();
    }

    /**
     * @param ShippingRuleInterface $rule
     * @param ActionInterface $action
     */
    private function addAction(ShippingRuleInterface $rule, ActionInterface $action)
    {
        $rule->addAction($action);

        $this->objectManager->persist($rule);
        $this->objectManager->flush();
    }


    /**
     * {@inheritdoc}
     */
    protected function getConditionFormRegistry()
    {
        return $this->conditionFormTypeRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConditionFormClass()
    {
        return ShippingRuleConditionType::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormFactory()
    {
        return $this->formFactory;
    }
}
