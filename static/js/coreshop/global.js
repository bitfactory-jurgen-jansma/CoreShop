/**
 * CoreShop
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015 Dominik Pfaffenbauer (http://dominik.pfaffenbauer.at)
 * @license    http://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS("pimcore.plugin.coreshop.global");
pimcore.plugin.coreshop.global = {

    settings : {},

    initialize : function(settings)
    {
        this.settings = settings;

        if(intval(this.settings.coreshop['SYSTEM.ISINSTALLED'])) {
            this._initStores();
            this._initUpdate();
        }
    },

    _initStores : function() {
        this._createStore("coreshop_currencies", 'Currency');
        this._createStore("coreshop_zones", 'OrderStates', [
            {name:'id'},
            {name:'name'},
            {name:'active'}
        ]);
        this._createStore("coreshop_countries", 'Country');
        this._createStore("coreshop_order_states", 'OrderStates');
        this._createStore("coreshop_taxes", 'Tax', [
            {name:'id'},
            {name:'name'},
            {name:'rate'}
        ]);
        this._createStore("coreshop_tax_rule_groups", 'TaxRuleGroup');
        this._createStore("coreshop_customer_groups", 'CustomerGroup');
        this._createStore("coreshop_carriers", 'Carrier');
        this._createStore("coreshop_price_rules", 'PriceRules');
    },

    _initUpdate : function() {
        new coreshop.update();
    },

    _createStore : function(name, url, fields) {
        var proxy = new Ext.data.HttpProxy({
            url : '/plugin/CoreShop/admin_' + url + '/list'
        });

        if(!fields) {
            fields = [
                {name:'id'},
                {name:'name'}
            ]
        }

        var reader = new Ext.data.JsonReader({}, fields);

        var store = new Ext.data.Store({
            restful:    false,
            proxy:      proxy,
            reader:     reader,
            autoload:   true
        });

        pimcore.globalmanager.add(name, store);
    }
};

if (!String.prototype.format) {
    String.prototype.format = function() {
        var args = arguments;
        return this.replace(/{(\d+)}/g, function(match, number) {
            return typeof args[number] != 'undefined'
                ? args[number]
                : match
                ;
        });
    };
}