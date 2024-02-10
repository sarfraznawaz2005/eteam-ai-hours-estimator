$(document).ready(function() {
    $('form').on('submit', function(e) {
      
        e.preventDefault();

        let formData = $(this).serialize();

        let formObject = $(this).serializeArray().reduce(function(obj, item) {
            obj[item.name] = item.value;
            return obj;
        }, {});

        // these options can be passed via hidden fields from forms
        let config = {
            "processor" : formObject.processor,
            "output_element" : formObject.output_element || 'div.success',
            "output_element_html" : formObject.output_element_html || 'div.success pre',
            "output_element_error" : formObject.output_element_error || 'div.alert-danger',
            "element_loading" : formObject.element_loading || 'div.loading',
            "element_loading_image" : formObject.element_loading_image || '#loadingImg',
            "show_random_loading" : formObject.show_random_loading || true,
            "show_lotti_animation" : formObject.show_lotti_animation || true,
            "hide_modal" : formObject.hide_modal || true,
        };

        if (config.show_random_loading) {
            $(config.element_loading_image).attr('src', getRandomLoadingImage());
        }
        else {
            $(config.element_loading_image).attr('src', '/assets/loading.gif');
        }

        $(config.output_element).hide();
        $(config.output_element_error).hide();

        $(config.element_loading).show();
        $('html, body').animate({
            scrollTop: $(config.element_loading).offset().top + 500
        }, 1000);

        $("button[type='submit']").prop('disabled', true);

        if (config.hide_modal) {
            $('.modal').modal('hide');
        }
        
        $.ajax({
            type: 'POST',
            url: config.processor,
            dataType: 'json',
            data: formData,
            success: function(response) {

                if (response.error) {
                    $(config.output_element_error).text(response.error);
                    $(config.output_element_error).show();
                } else {

                    if (config.show_lotti_animation) {
                        if (!response.result.includes('No response, please try again!')) {
                            $("lottie-player").show();
                        }
                    }
                    
                    $(config.element_loading).hide();
                    $(config.output_element_html).html(response.result);
                    $(config.output_element).show(1500);

                    hljs.highlightAll();

                    $('html, body').animate({
                        scrollTop: $(config.output_element).offset().top + 15
                    }, 1000, function() {
                        setTimeout(function() { $("lottie-player").hide(); }, 1000);
                    });
                }
            },
            error: function(xhr, status, error) {
                $(config.output_element_error).text(error);
                $(config.output_element_error).show();
            },
            complete: function() {
                $("button[type='submit']").prop('disabled', false);
                $(config.element_loading).hide();
            }
        });
    });
});

function getRandomLoadingImage() {
    const loadingImages = [
        '/assets/giphy1.gif',
        '/assets/giphy2.gif',
        '/assets/giphy3.gif',
        '/assets/giphy4.gif',
        '/assets/giphy5.gif',
        '/assets/giphy7.gif',
        '/assets/giphy8.gif',
        '/assets/giphy9.gif',
        '/assets/giphy10.gif',
        '/assets/giphy11.gif',
        '/assets/giphy12.gif',
        '/assets/giphy13.gif',
        '/assets/giphy14.gif',
        '/assets/giphy15.gif',
        '/assets/giphy16.gif',
        '/assets/giphy17.gif',
    ];
    
    const randomIndex = Math.floor(Math.random() * loadingImages.length);
    
    return loadingImages[randomIndex];
}
