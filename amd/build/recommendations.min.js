define(['jquery', 'core/config'], function($, cfg) {
    var M = {};

    M.init = function() {
        
        $(document).off('click', '#btn-get-recommendations');
        $(document).off('click', '.criteria-selectable');
        $(document).off('click', '#btn-back-to-filters');
        $(document).off('click', '#btn-search-recommendations');
        $(document).off('click', '#btn-next-step');
        $(document).off('click', '#btn-prev-step');

        // Configurações de Limites
        var MIN_SELECOES = 1;
        var MAX_SELECOES = 3;

        // 1. ABRIR O MODAL E RESETAR TUDO
        $(document).on('click', '#btn-get-recommendations', function(e) {
            e.preventDefault();
            $('.criteria-selectable').removeClass('selected-criteria-item'); 
            
            $('#recommendation-results-area').hide();
            $('#step-2-integridade').hide();
            $('#step-1-capacidade').show();
            $('#recommendation-selection-area').show();
            
            $('#btn-next-step').prop('disabled', true).addClass('disabled');
            $('#btn-search-recommendations').prop('disabled', true).addClass('disabled');
            
            $('#modal-recommendation').addClass('is-visible'); 
        });

        // 2. SELECIONAR ATRIBUTOS COM LIMITES
        $(document).on('click', '.criteria-selectable', function(e) {
            e.preventDefault();
            
            var $caixaClicada = $(this);
            var $telaAtual = $caixaClicada.closest('div[id^="step-"]'); 
            
            var jaEstavaSelecionado = $caixaClicada.hasClass('selected-criteria-item');
            var quantidadeSelecionadaNaTela = $telaAtual.find('.selected-criteria-item').length;

            if (!jaEstavaSelecionado && quantidadeSelecionadaNaTela >= MAX_SELECOES) {
                alert('Você pode selecionar no máximo ' + MAX_SELECOES + ' atributos nesta categoria.');
                return;
            }

            $caixaClicada.toggleClass('selected-criteria-item');
            
            var novaQuantidade = $telaAtual.find('.selected-criteria-item').length;

            if ($telaAtual.attr('id') === 'step-1-capacidade') {
                if (novaQuantidade >= MIN_SELECOES) {
                    $('#btn-next-step').prop('disabled', false).removeClass('disabled');
                } else {
                    $('#btn-next-step').prop('disabled', true).addClass('disabled');
                }
            } 
            // Regra de Liberação: Passo 2 (Integridade)
            else if ($telaAtual.attr('id') === 'step-2-integridade') {
                if (novaQuantidade >= MIN_SELECOES) {
                    $('#btn-search-recommendations').prop('disabled', false).removeClass('disabled');
                } else {
                    $('#btn-search-recommendations').prop('disabled', true).addClass('disabled');
                }
            }
        });

        // 3. NAVEGAÇÃO ENTRE OS PASSOS
        $(document).on('click', '#btn-next-step', function(e) {
            e.preventDefault();
            $('#step-1-capacidade').hide();
            $('#step-2-integridade').fadeIn();
        });

        $(document).on('click', '#btn-prev-step', function(e) {
            e.preventDefault();
            $('#step-2-integridade').hide();
            $('#step-1-capacidade').fadeIn();
        });

        // 4. VOLTAR PARA OS FILTROS APÓS VER RESULTADOS
        $(document).on('click', '#btn-back-to-filters', function(e) {
            e.preventDefault();
            $('#recommendation-results-area').hide();
            $('#step-2-integridade').hide();
            $('#step-1-capacidade').show(); 
            $('#recommendation-selection-area').fadeIn();
            
            // CORREÇÃO: Destrava os botões ao voltar, caso as opções ainda estejam selecionadas
            if ($('#step-1-capacidade').find('.selected-criteria-item').length >= MIN_SELECOES) {
                $('#btn-next-step').prop('disabled', false).removeClass('disabled');
            }
            if ($('#step-2-integridade').find('.selected-criteria-item').length >= MIN_SELECOES) {
                $('#btn-search-recommendations').prop('disabled', false).removeClass('disabled');
            }
        });

        // 5. BUSCAR RESULTADOS NO BACK-END
        $(document).on('click', '#btn-search-recommendations', function(e) {
            e.preventDefault();
            
            if ($(this).prop('disabled') || $(this).hasClass('disabled')) return;
            
            var $btn = $(this);
            var btnOriginalText = $btn.html();
            
            var selectedCriteria = [];
            $('.criteria-selectable.selected-criteria-item').each(function() {
                selectedCriteria.push($(this).data('criteria'));
            });

            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Buscando...');

            $.ajax({
                url: cfg.wwwroot + '/local/trustymatchmaker/ajax_get_recommendations.php',
                type: 'POST',
                dataType: 'json',
                data: { criteria: selectedCriteria },
                success: function(response) {
                    $btn.html(btnOriginalText); 
                    
                    // CORREÇÃO: Destrava o botão IMEDIATAMENTE após a busca terminar
                    $btn.prop('disabled', false).removeClass('disabled');

                    if (response.status === 'success') {
                        $('#recommendation-selection-area').hide();
                        
                        var html = '';
                        if (response.data.length === 0) {
                            html = '<div class="alert alert-warning text-center">Nenhum colaborador encontrado com estes critérios.</div>';
                        } else {
                            var getScoreLabel = function(score) {
                                if (score >= 0.75) return { text: 'Altamente recomendado', css: 'text-success', icon: 'fa-star' };
                                if (score >= 0.5)  return { text: 'Recomendado',           css: 'text-primary', icon: 'fa-check-double' };
                                return                     { text: 'Pouco recomendado',     css: 'text-danger',  icon: 'fa-circle-info' };
                            };
                            response.data.forEach(function(user) {
                                var badge = getScoreLabel(user.score);
                                html += '<div class="d-flex align-items-center p-3 mb-2" style="border: 1px solid #dee2e6; border-radius: 8px; background-color: #f8f9fa;">';
                                html += '  <img src="' + user.profileimageurl + '" alt="Foto" class="rounded-circle me-3" width="50" height="50" style="object-fit: cover;">';
                                html += '  <div>';
                                html += '    <h5 class="mb-0" style="color: #495057;">' + user.fullname + '</h5>';
                                html += '    <small class="' + badge.css + '"><i class="fa-solid ' + badge.icon + '"></i> ' + badge.text + '</small>';
                                html += '  </div>';
                                html += '  <div class="d-flex gap-2 ms-auto">';
                                html += '    <button class="btn btn-sm btn-outline-primary" style="margin-right: 10px; title="Colaborar"><i class="fa-solid fa-handshake"></i></button>';
                                html += '    <button class="btn btn-sm btn-outline-primary btn-add-friend" data-userid="' + user.id + '" title="Adicionar à lista de contatos">';
                                html += '      <i class="fa-solid fa-user-plus"></i>';
                                html += '    </button>';                               
                                html += '  </div>';
                                html += '</div>';
                            });
                        }
                        
                        $('#recommendation-list').html(html);
                        $('#recommendation-results-area').fadeIn();
                    } else {
                        alert('Erro ao buscar recomendações: ' + response.message);
                    }
                },
                error: function() {
                    alert('Erro de comunicação com o servidor.');
                    $btn.html(btnOriginalText).prop('disabled', false).removeClass('disabled');
                }
            });
        });

        // 6. MOSTRAR MAIS / MOSTRAR MENOS
        $(document).off('click', '.btn-toggle-attributes');
        $(document).on('click', '.btn-toggle-attributes', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var targetId = $btn.data('target'); // Pega o ID da lista (Ex: #lista-capacidade)
            var $container = $(targetId);
            
            // Alterna a classe que mostra os itens escondidos
            $container.toggleClass('show-extras');
            
            // Troca o texto e o ícone da setinha
            if ($container.hasClass('show-extras')) {
                $btn.html('Mostrar menos <i class="fa-solid fa-chevron-up ms-1"></i>');
            } else {
                $btn.html('Mostrar mais <i class="fa-solid fa-chevron-down ms-1"></i>');
            }
        });

        // 7. ADICIONAR AMIGO (CONTATO NO MOODLE)
        $(document).off('click', '.btn-add-friend');
        $(document).on('click', '.btn-add-friend', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var userId = $btn.data('userid'); // Pega o ID da pessoa na tela
            var originalHtml = $btn.html(); 

            // Efeito de carregamento e trava do botão
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

            $.ajax({
                url: cfg.wwwroot + '/local/trustymatchmaker/addfriend.php', 
                type: 'POST',
                dataType: 'json',
                data: { friendtoadd: userId }, 
                success: function(response) {
                    if (response.status === 'success') {
                        $btn.removeClass('btn-outline-primary').addClass('btn-success');
                        $btn.html('<i class="fa-solid fa-check"></i>');
                        $btn.attr('title', 'Adicionado com sucesso!');
                        
                    } else if (response.status === 'already_friends') {
                        alert(response.message);
                        
                        $btn.removeClass('btn-outline-primary').addClass('btn-info text-white');
                        $btn.html('<i class="fa-solid fa-user-check"></i>');
                        $btn.attr('title', 'Vocês já são contatos');
                        
                    } else if (response.status === 'already_friends') {
                        alert(response.message);
                        $btn.removeClass('btn-outline-primary').addClass('btn-info text-white');
                        $btn.html('<i class="fa-solid fa-user-check"></i>');
                        $btn.attr('title', 'Vocês já são contatos');
                    }else {
                        alert('Erro: ' + response.message);
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function() {
                    alert('Erro de comunicação com o servidor.');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        });

    };

    return M;
});