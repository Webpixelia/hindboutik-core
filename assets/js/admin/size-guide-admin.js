/**
 * HindBoutik Core — Size Guide Admin (repeater UI)
 * Manages dynamic add/remove of category rows and size table rows.
 */
(function ($) {
    window.hindboutikSizeGuide = {
        rowIndex: 0,

        init: function () {
            var self = this;

            // Add new category row.
            $('#hindboutik-add-category').on('click', function (e) {
                e.preventDefault();
                self.addCategoryRow();
            });

            // Remove category row (delegated).
            $('#hindboutik-size-guide-repeater').on('click', '.hindboutik-remove-row', function (e) {
                e.preventDefault();
                $(this).closest('.hindboutik-repeater-row').remove();
                self.updateRowIndices();
            });

            // Add size table row (delegated).
            $('#hindboutik-size-guide-repeater').on('click', '.hindboutik-add-table-row', function (e) {
                e.preventDefault();
                self.addTableRow($(this).closest('.hindboutik-repeater-row'));
            });

            // Remove size table row (delegated).
            $('#hindboutik-size-guide-repeater').on('click', '.hindboutik-remove-table-row', function (e) {
                e.preventDefault();
                $(this).closest('tr').remove();
            });

            // Initialize row indices based on existing rows.
            $('.hindboutik-repeater-row').each(function (i) {
                $(this).data('index', i);
            });
            this.rowIndex = $('.hindboutik-repeater-row').length;

            // --- Setup form submission handler ---
            this.setupFormSubmission();
        },

        /**
         * Setup form submission to populate hidden field before submit.
         */
        setupFormSubmission: function () {
            var self = this;

            // Fonction pour remplir le champ caché
            function fillHiddenField() {
                try {
                    var data = self.buildData();
                    var jsonData = JSON.stringify(data);
                    $('#hindboutik_size_guide_data_input').val(jsonData);
                    return true;
                } catch (e) {
                    console.error('Size Guide: Error filling hidden field', e);
                    return false;
                }
            }

            // Remplir au clic sur le bouton Enregistrer (tous les boutons submit)
            $('input[type="submit"]').on('click', function (e) {
                fillHiddenField();
            });

            // Remplir au submit du formulaire (backup)
            $('form').on('submit', function (e) {
                var val = $('#hindboutik_size_guide_data_input').val();
                if (!val || val === '') {
                    console.warn('Size Guide: Hidden field empty before submit, forcing fill');
                    fillHiddenField();
                }
                return true;
            });
        },

        addCategoryRow: function () {
            var index = this.rowIndex++;
            var categories = window.hindboutikSizeGuideData.categories;

            // Vérifier que les catégories sont disponibles
            if (!categories || categories.length === 0) {
                console.warn('Size Guide: No categories available');
                var optionsHtml = '<option value="">' + 
                    (window.wp && window.wp.i18n ? window.wp.i18n.__('Aucune catégorie disponible', 'hindboutik-core') : 'Aucune catégorie disponible') + 
                    '</option>';
            } else {
                var optionsHtml = '';
                for (var i = 0; i < categories.length; i++) {
                    optionsHtml += '<option value="' + categories[i].id + '">' + categories[i].name + '</option>';
                }
            }

            var html = '<div class="hindboutik-repeater-row" data-index="' + index + '">' +
                '<h3>' + (window.wp && window.wp.i18n ? window.wp.i18n.__('Catégorie', 'hindboutik-core') : 'Catégorie') + ' #' + (index + 1) +
                ' <button type="button" class="hindboutik-remove-row dashicons dashicons-dismiss"></button></h3>' +
                '<select name="hindboutik_row_' + index + '_categories[]" multiple style="width:100%;height:150px;">' +
                optionsHtml +
                '</select>' +
                '<h4>' + (window.wp && window.wp.i18n ? window.wp.i18n.__('Tableau des tailles', 'hindboutik-core') : 'Tableau des tailles') + '</h4>' +
                '<table class="hindboutik-size-table"><thead><tr>' +
                '<th>' + (window.wp && window.wp.i18n ? window.wp.i18n.__('Taille', 'hindboutik-core') : 'Taille') + '</th>' +
                '<th>' + (window.wp && window.wp.i18n ? window.wp.i18n.__('Taille FR', 'hindboutik-core') : 'Taille FR') + '</th>' +
                '<th></th></tr></thead><tbody></tbody></table>' +
                '<button type="button" class="hindboutik-add-table-row button button-secondary">' +
                (window.wp && window.wp.i18n ? window.wp.i18n.__('Ajouter une taille', 'hindboutik-core') : 'Ajouter une taille') + 
                '</button>' +
                '</div>';

            $('#hindboutik-size-guide-repeater').append(html);
        },

        addTableRow: function ($row) {
            var rowIndex = $row.data('index');
            var tableRowIndex = $row.find('.hindboutik-size-table tbody tr').length;
            var sizes = ['S', 'M', 'L', 'XL', 'XXL', '3XL', 'Taille unique'];

            var selectHtml = '<select name="hindboutik_row_' + rowIndex + '_tableau_' + tableRowIndex + '[taille]">';
            for (var i = 0; i < sizes.length; i++) {
                selectHtml += '<option value="' + sizes[i] + '">' + sizes[i] + '</option>';
            }
            selectHtml += '</select>';

            var rowHtml = '<tr class="hindboutik-table-row">' +  // ← La classe EST déjà là
                '<td>' + selectHtml + '</td>' +
                '<td><input type="text" name="hindboutik_row_' + rowIndex + '_tableau_' + tableRowIndex + '[taille_fr]" class="small-text"></td>' +
                '<td><button type="button" class="hindboutik-remove-table-row dashicons dashicons-dismiss"></button></td>' +
                '</tr>';

            $row.find('.hindboutik-size-table tbody').append(rowHtml);
        },

        updateRowIndices: function () {
            var i = 0;
            $('.hindboutik-repeater-row').each(function () {
                $(this).attr('data-index', i);
                i++;
            });
            this.rowIndex = i;
        },

        buildData: function () {
            var data = [];
            
            $('.hindboutik-repeater-row').each(function (rowIndex) {
                var row = {
                    categories: [],
                    tableau: []
                };

                // 1. Récupérer les catégories sélectionnées
                $(this).find('select[name*="_categories[]"] option:selected').each(function () {
                    var val = parseInt($(this).val(), 10);
                    if (!isNaN(val)) {
                        row.categories.push(val);
                    }
                });

                // 2. Récupérer les tailles
                // Utiliser .hindboutik-table-row pour les rows existantes ET les nouvelles
                $(this).find('.hindboutik-table-row').each(function () {
                    // Chercher le select et l'input dans cette row
                    var sizeSelect = $(this).find('select');
                    var tailleFrInput = $(this).find('input[type="text"]');
                    
                    if (sizeSelect.length > 0 && tailleFrInput.length > 0) {
                        row.tableau.push({
                            taille: sizeSelect.val(),
                            taille_fr: tailleFrInput.val()
                        });
                    }
                });

                data.push(row);
            });

            return data;
        }
    };
    
    $(function () {
        window.hindboutikSizeGuide.init();
    });

})(jQuery);