(function ($) {
    var activeElement = null;

    var actionsHtml = '<ul class="actions"><li><a aria-label="Remove mapping" title="Remove mapping" class="o-icon-delete remove-mapping" href="#" style="display: inline;"></a></li><li><a aria-label="Undo remove mapping" title="Undo remove mapping" class="o-icon-undo restore-mapping" href="#" style="display: none;"></a></li></ul>';

    $(document).ready(function() {
        $('.section').on('o:section-closed', function(e) {
            activeElement = null;
            $('tr.active').removeClass('active');
            switch ($(this).attr('id')) {
                case 'omeka2-import-types-map-fieldset':
                    Omeka.closeSidebar($('#resource-class-selector'));
                break;
                case 'omeka2-import-elements-map-fieldset':
                    Omeka.closeSidebar($('#property-selector'));
                break;
            }
        });

        $('.section').on('o:section-opened', function(e) {
            activeElement = null;
            $('tr.active').removeClass('active');
            switch ($(this).attr('id')) {
                case 'omeka2-import-types-map-fieldset':
                    Omeka.openSidebar($('#resource-class-selector'));
                break;
                case 'omeka2-import-elements-map-fieldset':
                    Omeka.openSidebar($('#property-selector'));
                break;
            }
        });

        $('.section').on('click', 'tr.mappable', function(e) {
            if (activeElement !== null) {
                activeElement.removeClass('active');
            }
            activeElement = $(e.target).closest('tr.mappable');
            activeElement.addClass('active');
        });

        $('#property-selector li.selector-child').on('click', function(e){
            e.stopPropagation();
            //looks like a stopPropagation on the selector-parent forces
            //me to bind the event lower down the DOM, then work back
            //up to the li
            var targetLi = $(e.target).closest('li.selector-child');
            if (activeElement == null) {
                alert("Select an element at the left before choosing a property.");
            } else {
                //first, check if the property is already added
                var hasMapping = activeElement.find('ul.mappings li[data-property-id="' + targetLi.data('property-id') + '"]');
                if (hasMapping.length === 0) {
                    var elementId = activeElement.data('element-id');
                    var newInput = $('<input type="hidden" name="element-property[' + elementId + '][]" ></input>');
                    newInput.val(targetLi.data('property-id'));
                    var newMappingLi = $('<li class="mapping" data-property-id="' + targetLi.data('property-id') + '">' + targetLi.data('child-search') + actionsHtml  + '</li>');
                    newMappingLi.append(newInput);
                    activeElement.find('ul.mappings').append(newMappingLi);
                } else {
                    alert('Element is already mapped');
                }
            }
        });

        $('#resource-class-selector li.selector-child').on('click', function(e){
            e.stopPropagation();
            //looks like a stopPropagation on the selector-parent forces
            //me to bind the event lower down the DOM, then work back
            //up to the li
            var targetLi = $(e.target).closest('li.selector-child');
            if (activeElement == null) {
                alert("Select an item type at the left before choosing a resource class.");
            } else {
                //first, check if a class is already added
                //var hasMapping = activeElement.find('ul.mappings li');
                activeElement.find('ul.mappings li').remove();
                activeElement.find('input').remove();
                //hasMapping.remove();
                var typeId = activeElement.data('item-type-id');
                var newInput = $('<input type="hidden" name="type-class[' + typeId + ']" ></input>');
                newInput.val(targetLi.data('class-id'));
                activeElement.find('td.mapping').append(newInput);
                activeElement.find('ul.mappings').append('<li class="mapping" data-class-id="' + targetLi.data('class-id') + '">' + targetLi.data('child-search') + '</li>');
            }
        });

        $('body').on('click', '.omeka2-import-fieldset-label, .omeka2-import-fieldset-label span', function(e) {
            e.stopPropagation();
            e.preventDefault();
            var target = $(e.target);
            if(! target.attr('id') ) {
                target = target.parent();
            }
            var fieldsetId = target.attr('id') + '-fieldset';
            $('#' + fieldsetId).toggle();
            var arrows = $('.expand, .collapse', target);
            arrows.toggleClass('expand collapse');
            if (arrows.hasClass('expand')) {
                arrows.attr('aria-label','Expand');
            } else {
                arrows.attr('aria-label','Collapse');
            }
        });

        // Clear default mappings
        $('body').on('click', '.clear-defaults', function(e) {
            e.stopPropagation();
            e.preventDefault();
            var fieldset = $(this).parents('fieldset');
            fieldset.find('li.mapping.default').remove();
        });


        // Remove mapping
        $('.section').on('click', 'a.remove-mapping', function(e) {
            var mappingToRemove = $(this).parents('li.mapping');
            mappingToRemove.find('input').prop('disabled', true);
            mappingToRemove.addClass('delete');
            mappingToRemove.find('.restore-mapping').show();
            $(this).hide();
        });

        // Restore a removed mapping
        $('.section').on('click', 'a.restore-mapping', function(e) {
            var mappingToRemove = $(this).parents('li.mapping');
            mappingToRemove.find('.remove-mapping').show();
            mappingToRemove.find('span.restore-mapping').hide();
            mappingToRemove.find('input').prop('disabled', false);
            mappingToRemove.removeClass('delete');
            $(this).hide();
        });

    });
})(jQuery);
