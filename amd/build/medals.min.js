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
                    .removeData('medalid') // Limpa o ID antigo
                    .text('Selecione uma medalha'); 
            }
            
            $('#' + modalId).addClass('is-visible'); 
        });
        
        // 2. Fechar modal
        $(document).on('click', '.medal-popup-close', function(e) {
            e.preventDefault();
            $(this).closest('.medal-popup-overlay').removeClass('is-visible');
        });

        // 3. Selecionar medalha (Comportamento de "Radio Button")
        $(document).on('click', '.medal-selectable', function(e) {
            e.preventDefault();
            
            var $btn = $('#btn-confirm-medal');
            var $caixaClicada = $(this);
            
            // Se ele clicou na que já estava selecionada, ele quer desmarcar
            if ($caixaClicada.hasClass('selected-medal-item')) {
                $caixaClicada.removeClass('selected-medal-item');
                $btn.prop('disabled', true).addClass('disabled').text('Selecione uma medalha').removeData('medalid');
            } 
            // Se clicou em uma nova, seleciona só ela
            else {
                // Remove a marcação de todas as outras caixas
                $('.medal-selectable').removeClass('selected-medal-item');
                // Adiciona a marcação apenas na que ele clicou
                $caixaClicada.addClass('selected-medal-item');
                
                var medalId = $caixaClicada.data('medalid');
                
                $btn.prop('disabled', false)
                    .removeClass('disabled')
                    .data('medalid', medalId)
                    .text('Presentear medalha');
            }
        });
        
        // 4. Buscar e enviar no PHP
        $(document).on('click', '#btn-confirm-medal', function(e) {
            e.preventDefault();
            
            if ($(this).prop('disabled') || $(this).hasClass('disabled')) return;
            
            var $btn = $(this);
            var receiverId = $btn.data('receiverid');
            var medalId = $btn.data('medalid'); // Agora é singular
            var $modal = $btn.closest('.medal-popup-overlay');

            $btn.prop('disabled', true).addClass('disabled').text('Enviando...');

            $.ajax({
                url: cfg.wwwroot + '/local/trustymatchmaker/ajax_give_medal.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    receiverid: receiverId,
                    medalid: medalId // Passando o ID único
                },
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        $modal.removeClass('is-visible'); 
                        location.reload(); 
                    } else {
                        alert('Erro: ' + response.message);
                        $btn.prop('disabled', false).removeClass('disabled').text('Presentear medalha');
                    }
                },
                error: function() {
                    alert('Ocorreu um erro de comunicação com o servidor.');
                    $btn.prop('disabled', false).removeClass('disabled').text('Presentear medalha');
                }
            });
        });
    };
    return M;
});