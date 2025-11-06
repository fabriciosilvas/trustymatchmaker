define(['jquery'], function($) {
    var M = {};
    M.init = function() {
        // Ouve cliques nos ícones de medalha
        $('.medal-trigger').on('click', function(e) {
            e.preventDefault(); // Impede que o link '#' mude a URL
            var modalId = $(this).data('modal-id'); // Pega o 'data-modal-id'
            $('#' + modalId).addClass('is-visible'); // Mostra o popup
        });
        
        // Ouve cliques nos botões 'fechar' ou 'voltar'
        $('.medal-popup-close').on('click', function(e) {
            e.preventDefault();
            $(this).closest('.medal-popup-overlay').removeClass('is-visible');
        });
    };
    return M;
});