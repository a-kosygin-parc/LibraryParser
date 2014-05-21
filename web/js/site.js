(function(){
    $('.js-search-form-hide').on('click', function(){
        var button = $(this),
            form = $(document.getElementById(button.data('related')));

        button.text(form.css('display') == 'none' ? 'Скрыть' : 'Поиск');
        form.toggleClass('g-hidden');
    });
})();