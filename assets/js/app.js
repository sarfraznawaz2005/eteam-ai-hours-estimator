$(document).ready(function() {
    $('form').on('submit', function(e) {
        e.preventDefault();

        let formData = $(this).serialize();

        $('#loadingImg').attr('src', getRandomLoadingImage());

        $('div.alert-danger').hide();
        $('div.success').hide();

        $("div.loading").show();
        $('html, body').animate({
            scrollTop: $("div.loading").offset().top + 500
        }, 1000);

        $("button[type='submit']").prop('disabled', true);

        $('.modal').modal('hide');

        $.ajax({
            type: 'POST',
            url: 'process.php',
            dataType: 'json',
            data: formData,
            success: function(response) {

                if (response.error) {
                    $('div.alert-danger').text(response.error);
                    $('div.alert-danger').show();
                } else {

                    $("div.loading").hide();
                    $('div.success pre').html(response.result);
                    $('div.success').show(1500);

                    $('html, body').animate({
                        scrollTop: $("div.success").offset().top + 15
                    }, 1000);
                }
            },
            error: function(xhr, status, error) {
                $('div.alert-danger').text(error);
                $('div.alert-danger').show();
            },
            complete: function() {
                $("button[type='submit']").prop('disabled', false);
                $("div.loading").hide();
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
        '/assets/giphy6.gif',
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
