jQuery(document).ready(function ($) {
    $('.training__show-planning').on('click', function (e) {
        e.preventDefault();
        var $training = $(this).closest('.training');
        var $planning = $training.find('.training__planning');

        $planning.slideToggle(300, function () {
            $training.toggleClass('opened', $planning.is(':visible'));
        });
    });
});