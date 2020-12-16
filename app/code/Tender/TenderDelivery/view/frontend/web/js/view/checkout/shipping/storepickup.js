define([
    'jquery',
    "underscore",
    'ko',
    'Magento_Checkout/js/model/quote',
    'uiComponent',
    'mage/calendar',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'tenderCalendarPicker',
    'Magento_Checkout/js/model/shipping-save-processor'
], function ($, _, ko, quote, Component, calendar, modal,$t,tenderCalendarPicker,shippingSaveProcessor ) {
    'use strict';
    var show_hide_custom_blockConfig = window.checkoutConfig.show_hide_custom_block;
   
    
    $(document).ready(function () {
        
        
        
        $(document).on('change','.storepickup-shipping-method select',function () {
            $('.store_info ul li').hide();
            if ($(this).val() != "") {
                $(document).find('.storepickup_checked').val('1');
                $('li.store_info_'+$(this).val()).show();
            } else {
                $(document).find('.storepickup_checked').val('0');
            }
            
            
            //$('#mobile_delivery_time option').each(function () {
            //    var timeoption = $(this).attr('value');
            //    console.log('timeoption',timeoption);
            //    var now = new Date();
            //
            //    var time = now.getTime();
            //     console.log('time',time);
            //    if (time > timeoption) {
            //         console.log('disab',timeoption);
            //        $(this).attr('disabled');
            //    }
            //});
            
            
        });
        
        setTimeout(function(){
            $(document).on('change','#mobile_delivery_time',function () {
                //var formatted = $.datepicker.formatDate(format, new Date(cal.currentDate));
                var selected_date = new Date($("#mobile_delivery_date").val());
                if(!isNaN(selected_date)){
                    console.log('selected_date ',selected_date + selected_date.getDate());
                    var today = new Date();   
                    if(
                        selected_date.getYear() >= today.getYear() &&
                        selected_date.getMonth() >= today.getMonth() &&
                        selected_date.getDate() >= today.getDate()){                        
                        shippingSaveProcessor.saveShippingInformation(quote.shippingAddress().getType());
                    }else{
                        $("#mobile_delivery_time").val('');
                        alert($t('Date must be greater than or equal to today.'));
                    }
                    //shippingSaveProcessor.saveShippingInformation(quote.shippingAddress().getType());
                }
            });
        },500);
        
        
        
        $(document).on('click','#click-me',function () {
            var options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: 'Stores in Map',
                buttons: [{
                    text:'Close',
                    class: '',
                    click: function () {
                        this.closeModal();
                    }
                }]
            };
            
            var popup = modal(options, $('#ci-storepickup-popup-modal'));
            $("#ci-storepickup-popup-modal").modal("openModal");
           
        });
    });
    
    return Component.extend({
        defaults: {
            formSelector: '#checkout-step-shipping_method button',
            template: 'Tender_TenderDelivery/checkout/shipping/storepickup',
            storepickConfig: window.checkoutConfig.storepick_config,
            storepickConfigEncode: window.checkoutConfig.storepick_config_encode,
            storepickInfo: window.checkoutConfig.storepick_info,
            deliveryTimeInterval: window.checkoutConfig.delivery_time_interval
        },
        
        initObservable: function () {
                this._super();

                var currentDate = new Date();
                var mm = String(currentDate.getMonth() + 1).padStart(2, '0'); //January is 0!
                var yyyy = currentDate.getFullYear();
                var hour = currentDate.getHours();
                var min = currentDate.getMinutes();
                var second = currentDate.getSeconds();
                var dd = String(currentDate.getDate()).padStart(2, '0');

                var currentFormattedDate = yyyy+ '-' + mm + '-' + dd + ' ' + hour+ ':' +min+ ':' +second;
                this.tenderInstantTime = currentFormattedDate;


                this.selectedMethod = ko.computed(function () {
                var method = quote.shippingMethod();
                var selectedMethod = method != null ? method.carrier_code + '_' + method.method_code : null;
                return selectedMethod;
            }, this);

            return this;
        },
        
        initialize: function () {
            this._super();
            
            ko.bindingHandlers.tenderCalendarPicker = {
                init: function (element, valueAccessor, allBindingsAccessor) {
                    var $el = $(element);
                    var format = 'dd-mm-yy';
                    

                    $('#mobile_delivery_calender').calendarPicker({
                        monthNames:[$t('January'), $t('February'),$t('March'),$t('April'),
                                    $t('May'),$t('June'),$t('July'),$t('August'),$t('September'),
                                    $t('October'),$t('November'),$t('December')],
                        dayNames: [$t('Sun'),$t('Mon'),$t('Tue'),$t('Wed'),$t('Thu'),$t('Fri'),$t('Sat')],
                        useWheel:false,
                        //callbackDelay:500,
                        years:1,
                        months:1,
                        days:2,
                        showDayArrows:true,
                        callback:function(cal) {
                            var currentDate = new Date(cal.currentDate)

                            var dd = String(currentDate.getDate()).padStart(2, '0');
                            var mm = String(currentDate.getMonth() + 1).padStart(2, '0'); //January is 0!
                            var yyyy = currentDate.getFullYear();

                            var currentFormattedDate = mm + '-' + dd + '-' + yyyy;

                            $("#mobile_delivery_date").val(currentFormattedDate);
                            //$("#mobile_delivery_date").val(formatted);
                            var today = new Date();   
                            if(cal.currentDate.getDate() >= today.getDate()){                        
                                //shippingSaveProcessor.saveShippingInformation(quote.shippingAddress().getType());
                                $("#mobile_delivery_time").val('');
                            }
                            
                        }
                    });
                    
                    $('.cls-times-cntr label').html($t('Delivery time'));
                    
                }
            };

            return this;
        },
        setShippingInformation: function () {
            var shippingMethod = quote.shippingMethod();
            console.log('shippingMethod',shippingMethod);
            if (shippingMethod['method_code'] == 'tenderschedule') {
                if ($("#calendar_inputField").val() == '') {
                    this.focusInvalid();
                    return false;
                }
            }
            this._super();
            },
        selectDeliveryTime: function(option, item) {
            //console.log('item ', item);

            var currentDate = new Date();
            var dt = (currentDate.getMonth()+1) + "/" + currentDate.getDate() + "/" + currentDate.getFullYear() + " " + item.value+ ":00";
            var userval = new Date(dt);
            var min = 90*60*1000; 
            userval.setTime(userval.getTime() + min);
            if(currentDate > userval){
                ko.applyBindingsToNode(option, {disable: true}, item);
            }
            
        },
        canVisibleBlock: show_hide_custom_blockConfig
    });

});
