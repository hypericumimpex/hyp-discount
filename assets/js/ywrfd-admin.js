jQuery(function ($) {

    $('body')
        .on('click', 'button.ywrfd-send-test-email', function () {

            var result = $(this).next(),
                email = $(this).prev().attr('value'),
                type = $(this).prev().attr('id').replace('ywrfd_email_', '').replace('_test', ''),
                re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

            result.show();
            result.removeClass('send-progress send-fail send-success');

            if (!re.test(email)) {

                result.addClass('send-fail');
                result.html(ywrfd_admin.test_mail_wrong);

            } else {

                var data = {
                    action: 'ywrfd_send_test_mail',
                    email : email,
                    type  : type,
                    vendor_id: (ywrfd_admin.vendor_id != '0') ? ywrfd_admin.vendor_id : ''
                };

                result.addClass('send-progress');
                result.html(ywrfd_admin.before_send_test_email);

                $.post(ywrfd_admin.ajax_url, data, function (response) {

                    result.removeClass('send-progress');

                    if (response === true) {

                        result.addClass('send-success');
                        result.html(ywrfd_admin.after_send_test_email);

                    } else {

                        result.addClass('send-fail');
                        result.html(response.error);

                    }

                });

            }

        });

    $(document).ready(function ($) {

        if (ywrfd_admin.comment_moderation && !ywrfd_admin.ywar_active) {

            var moderation = $('#comment_moderation');

            moderation.attr('disabled', true);
            moderation.parent().append(' <i>(' + ywrfd_admin.comment_moderation_warning + ')</i>');

        }

    });

});

