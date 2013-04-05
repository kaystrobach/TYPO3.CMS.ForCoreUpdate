define('TYPO3/CMS/Backend/TabMenu', ['jquery'], function ($) {

    /*
     * jQuery Plugin "TabMenuPlugin"
     */

    var TabMenuPlugin = function (elem) {
        this.elem = $(elem)
    }
    TabMenuPlugin.prototype = {

        constructor : TabMenuPlugin

        ,toggle : function() {
            var
                $this    = this.elem
                ,$ul     = $this.closest('ul')
                ,$parent = $this.closest('li')
                    // get DOM id of the target tab container
                ,$target = $($this.attr('href'));

                // click on active tab
            if ($parent.hasClass('active')) { return; }

                // trigger jQuery hook: "show"
            $this.trigger({
                type: 'show'
                ,relatedTarget: $target
            });

                // update tab menu
            $parent.addClass('active').siblings().removeClass('active');

                // display target tab container
            $target.show().siblings().hide();

                // trigger jQuery hook "shown"
            $this.trigger({
                type: 'shown'
                ,relatedTarget: $target
            });
        }
    }


    /*
     * Tab Menu settings
     */

    var TabMenu = {
        settings: {
            $tabMenu : $('.typo3-dyntabmenu')
        }
    };


    /*
     * initialize TabMenu
     */

    TabMenu.initialize = function() {
        var
            me = this
            ,s = me.settings;

            // register the jQuery plugin "TabMenuPlugin"
        $.fn.TabMenuPlugin = function(option) {
            return this.each(function () {
                var $this = $(this)
                    , data = $this.data('TabMenuPlugin')
                if (!data) $this.data('TabMenuPlugin', (data = new TabMenuPlugin(this)))
                if (typeof option == 'string') data[option]()
            })
        }

            // show first tab
        s.$tabMenu.each(function() {
           $(this).children('li:first').children('[data-toggle="TabMenu"]').TabMenuPlugin('toggle');
        });

            // events binding to toggle the tab menu
        $(document).on('click', '[data-toggle="TabMenu"]', function(evt) {
            evt.preventDefault();
            $(this).TabMenuPlugin('toggle');
        });
    }

        // initialize function, always require possible post-render hooks return the main object
    var initialize = function(options) {

        TabMenu.initialize();

            // load required modules to hook in the post initialize function
        if (undefined !== TYPO3.settings.RequireJS.PostInitializationModules['TYPO3/CMS/Backend/TabMenu']) {
            $.each(TYPO3.settings.RequireJS.PostInitializationModules['TYPO3/CMS/Backend/TabMenu'], function(pos, moduleName) {
                require([moduleName]);
            });
        }

            // return the object in the global space
        return TabMenu;
    };

        // call the main initialize function and execute the hooks
    return initialize();
});