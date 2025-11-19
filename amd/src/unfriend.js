define(['jquery'], function($) {
    var U = {};

    U.init = function() {
        $('.unfriend').on('click', function () {
            var $button = $(this);
            var $friendid = $button.data('friendtoremove');

            $.ajax({
                url: '/local/trustymatchmaker/unfriend.php',
                method: 'POST',
                data: {
                    friendtoremove: $friendid,
                },
                success: function () {
                    location.reload();
                }
            });
        });
    };
    return U;
});