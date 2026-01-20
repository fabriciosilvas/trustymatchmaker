define(['jquery'], function($) {
    var I = {};

    I.init = function() {
        $('#invite-friend').on('click', function () {
            var $button = $(this);
            var $friendid = $button.data('friendtoadd');

            $.ajax({
                url: '/local/trustymatchmaker/addfriend.php',
                method: 'POST',
                data: {
                    friendtoadd: $friendid,
                },
                success: function () {
                    location.reload();
                }
            });
        });
    };
    return I;
});