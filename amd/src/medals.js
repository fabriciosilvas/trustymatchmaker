define(['jquery', 'core/config'], function($, cfg) {
    var M = {};
    M.init = function() {
        
        $(document).off('click', '.medal-trigger');
        $(document).off('click', '.medal-popup-close');
        $(document).off('click', '.medal-selectable');
        $(document).off('click', '#btn-confirm-medal');

        // 1. Abrir modal
        $(document).on('click', '.medal-trigger', function(e) {
            e.preventDefault();
            var modalId = $(this).data('modal-id'); 
            
            if ($(this).hasClass('give-medal-btn')) {
                var receiverId = $(this).data('collaboratorid');
                $('#btn-confirm-medal').data('receiverid', receiverId);
                
                $('.medal-selectable').removeClass('selected-medal-item');
                $('#btn-confirm-medal')
                    .prop('disabled', true)
                    .addClass('disabled')
                    .removeData('medalids')
                    .text('Selecione 2 medalhas'); 
            }
            
            $('#' + modalId).addClass('is-visible'); 
        });
        
        // 2. Fechar modal
        $(document).on('click', '.medal-popup-close', function(e) {
            e.preventDefault();
            $(this).closest('.medal-popup-overlay').removeClass('is-visible');
        });

        // 3. Selecionar medalhas
        $(document).on('click', '.medal-selectable', function(e) {
            e.preventDefault();
            
            var $btn = $('#btn-confirm-medal');
            
            $(this).toggleClass('selected-medal-item');
            
            var medalIds = [];
            $('.selected-medal-item').each(function() {
                medalIds.push($(this).data('medalid'));
            });
            
            var count = medalIds.length;

            if (count > 2) {
                $(this).removeClass('selected-medal-item');
                alert("Você só pode selecionar exatamente 2 medalhas.");
                return;
            }
            
            if (count === 0) {
                $btn.prop('disabled', true).addClass('disabled').text('Selecione 2 medalhas');
            } else if (count === 1) {
                $btn.prop('disabled', true).addClass('disabled').text('Selecione mais 1 medalha');
            } else if (count === 2) {
                $btn.prop('disabled', false)
                    .removeClass('disabled')
                    .data('medalids', medalIds)
                    .text('Confirmar e Presentear');
            }
        });
        
        // 4. PHP
        $(document).on('click', '#btn-confirm-medal', function(e) {
            e.preventDefault();
            
            if ($(this).prop('disabled') || $(this).hasClass('disabled')) return;
            
            var $btn = $(this);
            var receiverId = $btn.data('receiverid');
            var medalIds = $btn.data('medalids');
            var $modal = $btn.closest('.medal-popup-overlay');

            $btn.prop('disabled', true).addClass('disabled').text('Enviando...');

            $.ajax({
                url: cfg.wwwroot + '/local/trustymatchmaker/ajax_give_medal.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    receiverid: receiverId,
                    medalids: medalIds
                },
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        $modal.removeClass('is-visible'); 
                        location.reload(); 
                    } else {
                        alert('Erro: ' + response.message);
                        $btn.prop('disabled', false).removeClass('disabled').text('Confirmar e Presentear');
                    }
                },
                error: function() {
                    alert('Ocorreu um erro de comunicação com o servidor.');
                    $btn.prop('disabled', false).removeClass('disabled').text('Confirmar e Presentear');
                }
            });
        });
    };
    return M;
});