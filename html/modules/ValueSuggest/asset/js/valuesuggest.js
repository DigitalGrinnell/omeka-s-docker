$(document).on('o:prepare-value', function(e, type, value) {
    if (0 === type.indexOf('valuesuggest:') || 0 === type.indexOf('valuesuggestall:')) {

        var thisValue = $(value);
        var suggestInput = thisValue.find('.valuesuggest-input');
        var labelInput = thisValue.find('input[data-value-key="o:label"]');
        var idInput = thisValue.find('input[data-value-key="@id"]');
        var valueInput = thisValue.find('input[data-value-key="@value"]');
        var languageLabel = thisValue.find('.value-language.label');
        var languageInput = thisValue.find('input[data-value-key="@language"]');
        var languageRemove = thisValue.find('.value-language.remove');
        var idContainer = thisValue.find('.valuesuggest-id-container');
        var allResults;

        // Literal is the default type.
        idInput.prop('disabled', true);
        labelInput.prop('disabled', true);
        valueInput.prop('disabled', false);
        idContainer.hide();

        // Set existing values duing initial load.
        if (idInput.val()) {
            // Set value as URI type
            suggestInput.val(labelInput.val()).attr('placeholder', labelInput.val());
            idInput.prop('disabled', false);
            labelInput.prop('disabled', false);
            valueInput.prop('disabled', true);
            var link = $('<a>')
                .attr('href', idInput.val())
                .attr('target', '_blank')
                .text(idInput.val());
            idContainer.show().find('.valuesuggest-id').html(link);
        } else if (valueInput.val()) {
            // Set value as Literal type
            suggestInput.val(valueInput.val()).attr('placeholder', valueInput.val());
            idInput.prop('disabled', true);
            labelInput.prop('disabled', true);
            valueInput.prop('disabled', false);
        }

        // Synchronize the suggest input with o:label or @value.
        suggestInput.on('input', function(e) {
            if (idInput.val()) {
                labelInput.val($(this).val());
            } else {
                valueInput.val($(this).val());
            }
        });

        // Clear the cache after any modifications to the language input.
        languageInput.on('input', function(e) {
            suggestInput.autocomplete().clearCache();
            allResults = null;
        })
        
        // Remove default lanugage toggle, use cusotm behavior
        languageLabel.unbind();

        languageLabel.on('click', function(e) {
            e.preventDefault();
            if ($(this).hasClass('active')) {
                return;
            }
            thisValue.find('.value-language').addClass('active');
        });
        
        languageRemove.on('click', function(e) {
           e.preventDefault();
           thisValue.find('.value-language.active').removeClass('active'); 
        });

        // Remove the @id from URI type and transform it into Literal type.
        idContainer.find('.valuesuggest-id-remove').on('click', function(e) {
            e.preventDefault();
            idContainer.hide();
            valueInput.val(labelInput.val());
            idInput.prop('disabled', true);
            labelInput.prop('disabled', true);
            valueInput.prop('disabled', false);
        });

        // Build the autocomplete options.
        var options = {
            // Must disable triggerSelectOnValidInput or onSelect will be
            // triggered whether the user wants it or not. The user must
            // explicitly select the suggestion.
            triggerSelectOnValidInput: false,
            // Set the lang paramater in onSearchStart so the "valuesuggest"
            // type always uses the current language when making a query. Set
            // the type parameter here as well for consistency.
            onSearchStart: function(params) {
                $(this).css('cursor', 'progress');
                params.lang = languageInput.val();
                params.type = type;
            },
            onSearchComplete: function(query, suggestions) {
                $(this).css('cursor', 'default');
            },
            onSearchError: function (query, jqXHR, textStatus, errorThrown) {
                // Silently handle error.
                $(this).css('cursor', 'default');
            },
            // Prepare the value when the user selects a suggestion.
            onSelect: function (suggestion) {
                // Set value as URI type
                suggestInput.val(suggestion.value)
                    .attr('placeholder', suggestion.value);
                idInput.val(suggestion.data.uri);
                labelInput.val(suggestion.value);
                idInput.prop('disabled', false);
                labelInput.prop('disabled', false);
                valueInput.prop('disabled', true);
                var link = $('<a>')
                    .attr('href', suggestion.data.uri)
                    .attr('target', '_blank')
                    .text(suggestion.data.uri);
                idContainer.show().find('.valuesuggest-id').html(link);
            }
        };

        // For the "valuesuggestall" type, assume the first response contains
        // all available suggestions. Do not make subsequent requests.
        if (0 === type.indexOf('valuesuggestall:')) {
            // Get suggestions immediately when input is first put into focus.
            options.minChars = 0;
            // Prepare the suggestions prior to rendering them.
            options.beforeRender = function(container, suggestions) {
                // Add title attribute to each suggestion for disambiguation.
                container.children().each(function(index) {
                    $(this).attr('title', suggestions[index].data.info);
                });
                // Hide suggestions that contain no matches.
                var hasSuggestions = container.children(':has(strong)');
                hasSuggestions.show();
                if (hasSuggestions.length) {
                    container.children().not(':has(strong)').hide();
                }
            };
            // Use custom lookup function to make only one request.
            options.lookup = function (query, done) {
                if (null == allResults) {
                    $.get(valueSuggestProxyUrl, this.params, function(data) {
                        allResults = data; // cache the data
                        done(allResults);
                    });
                } else {
                    done(allResults);
                }
            };

        // For the "valuesuggest" type, make requests as normal.
        } else {
            options.serviceUrl = valueSuggestProxyUrl;
            options.deferRequestBy = 200;
            options.minChars = 3;
            // Must disable preventBadQueries or autocomplete will not fire on
            // queries that share a root that previously returned no results.
            options.preventBadQueries = false;
            // Prepare the suggestions prior to rendering them.
            options.beforeRender = function(container, suggestions) {
                // Add title attribute to each suggestion for disambiguation.
                container.children().each(function(index) {
                    $(this).attr('title', suggestions[index].data.info);
                });
            };
        }

        suggestInput.autocomplete(options);
    }
});
