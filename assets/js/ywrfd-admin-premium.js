jQuery(function ($) {

    $('body')
        .on('click', 'button.ywrfd-purge-coupon', function () {

            var result = $('.ywrfd-clear-result'),
                data = {
                    action: 'ywrfd_clear_expired_coupons'
                };

            result.show();
            $(this).hide();

            $.post(ywrfd_admin.ajax_url, data, function (response) {

                result.removeClass('clear-progress');

                if (response.success) {

                    result.addClass('clear-success');
                    result.html(response.message);

                } else {

                    result.addClass('clear-fail');
                    result.html(response.error);

                }

            });

        });

    $(document).ready(function ($) {

        $('select#ywrfd_trigger').change(function () {

            var option = $('option:selected', this).val();

            if (option === 'review') {

                $('.ywrfd_review').show();
                $('.ywrfd_multiple').hide();
                $('#ywrfd_trigger_threshold').prop('required', false);

            } else {

                $('.ywrfd_review').hide();
                $('.ywrfd_multiple').show();
                $('#ywrfd_trigger_threshold').prop('required', true);

            }

        }).change();

        $('select#ywrfd_discount_type').change(function () {

            var option = $('option:selected', this).val();

            if (option === 'funds') {

                $('.coupon_options').hide();
                $('#ywrfd_expiry_days').parent().hide();
                $('#ywrfd_free_shipping').parent().hide();

            } else {

                $('.coupon_options').show();
                $('#ywrfd_expiry_days').parent().show();
                $('#ywrfd_free_shipping').parent().show();

            }

        }).change();

        if (ywrfd_admin.comment_moderation && ywrfd_admin.ywar_active) {

            var moderation = $('#ywar_review_moderation');

            moderation.attr('disabled', true);
            moderation.parent().append(' <i>(' + ywrfd_admin.comment_moderation_warning + ')</i>');

        }

        $('#ywrfd_trigger_enable_notify').change(function () {

            if ($(this).is(':checked')) {

                $('.ywrfd_trigger_threshold_notify_field').show();
                $('.ywrfd-review-number').change();
                $('#ywrfd_trigger_threshold_notify').prop('required', true);


            } else {

                $('.ywrfd_trigger_threshold_notify_field').hide();
                $('#ywrfd_trigger_threshold_notify').prop('required', false);

            }

        }).change();

        $('.ywrfd-review-number').change(function () {

            if ($('#ywrfd_trigger_enable_notify').is(':checked')) {

                var value = ($(this).val() !== '') ? parseInt($(this).val()) : 1;

                if ($(this).is('.ywrfd-target')) {

                    var notify = $('.ywrfd-notify'),
                        container = $('.ywrfd_multiple'),
                        data = {
                            action   : 'ywrfd_get_minimum_threshold',
                            value    : value,
                            post_id  : ywrfd_admin.post_id,
                            vendor_id: (ywrfd_admin.vendor_id !== '0') ? ywrfd_admin.vendor_id : ''
                        };

                    if (container.is('.processing')) {
                        return false;
                    }

                    container.addClass('processing');

                    container.block({
                        message   : null,
                        overlayCSS: {
                            background: '#fff',
                            opacity   : 0.6
                        }
                    });

                    $.post(ywrfd_admin.ajax_url, data, function (response) {

                        if (response.success) {

                            if (response.value > (value - 1)) {

                                notify.prop('min', ((value > 1) ? value - 1 : 1));

                            } else {

                                notify.prop('min', response.value);

                                if (notify.val() < response.value) {
                                    notify.val(response.value);
                                }

                            }

                            notify.prop('max', ((value > 1) ? value - 1 : 1));

                        } else {

                            console.log(response.error);
                            notify.prop('min', 1);
                            notify.prop('max', 1);

                        }

                        container.removeClass('processing').unblock();

                    });

                } else if ($(this).is('.ywrfd-notify')) {

                    $('.ywrfd-target').prop('min', value + 1);

                }

            }

        }).change();

    });

});

