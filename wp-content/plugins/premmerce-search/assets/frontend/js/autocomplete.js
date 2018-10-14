jQuery(document).ready(function($){
    
    // Config object is required
    if(typeof premmerceSearch === 'undefined'){
        return
    }
    
    var search = $('[id^="woocommerce-product-search-field-"]');
    
    var autocompleteItemTemplate = $('[data-autocomplete-template="item"]').clone();
    var autocompleteAllResultTemplate = $('[data-autocomplete-template="allResult"]').clone();
    
    search.each(function () {
        var search = $(this);

        search.autocomplete({
            source: function (name, response) {
                $.get(premmerceSearch.url, name, function (data) {
                    response(data);
                }, 'json');
            },
            messages: {
                noResults: '',
                results: function() {}
            },
            delay: 500,
            minLength: parseInt(premmerceSearch.minLength),
            open: function(){
                var form = $(this).closest('form');
                
                // Show all result handler
                $(autocompleteAllResultTemplate , '[data-autocomplete-show-all-result]').on('click', function (event) {
                    event.preventDefault();
                    form.submit();
                });
    
                $('.ui-autocomplete').css('width', search.css('width'));
                $('.ui-autocomplete').append(autocompleteAllResultTemplate);
                
            }
        });
        
        search.autocomplete('instance')._renderItem = function (ul, item) {
            
            ul.addClass('autocomplete autocomplete__frame');
            
            var li = autocompleteItemTemplate.clone();
    
            li.find('[data-autocomplete-product-name]').html(item.label);
            li.find('[data-autocomplete-product-price]').html(item.price);
            li.attr('href', item.link);
            
            if(item.image){
                li.find('[data-autocomplete-product-img]').attr({'src': item.image, 'alt': item.label});
                li.find('[data-autocomplete-product-photo]').show();
            }
            
            return li.appendTo(ul);
        };
    });
    
});