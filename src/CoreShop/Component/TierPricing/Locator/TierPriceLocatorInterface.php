<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2019 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Component\TierPricing\Locator;

use CoreShop\Component\TierPricing\Model\ProductSpecificTierPriceRuleInterface;
use CoreShop\Component\TierPricing\Model\ProductTierPriceRangeInterface;

interface TierPriceLocatorInterface
{
    /**
     * @param ProductSpecificTierPriceRuleInterface $priceRule
     * @param int                                   $quantity
     *
     * @return ProductTierPriceRangeInterface|null
     */
    public function locate(ProductSpecificTierPriceRuleInterface $priceRule, int $quantity);
}
