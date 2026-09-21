(function($){
    'use strict';

    var root = $('[data-htp-guides-1178]');
    if (!root.length) return;

    var list = root.find('[data-guide-list]');
    var template = root.find('[data-guide-template]');
    var labels = {
        cycle1: 'Cycle 1',
        cycle2: 'Cycle 2',
        cycle3: 'Cycle 3',
        cycle4: 'Cycle 4',
        multi: 'Multiniveaux'
    };
    var statuses = {
        available: 'Disponible',
        new: 'Nouveau',
        coming: 'À venir'
    };
    var flags = {fr:'🇫🇷', de:'🇩🇪', en:'🇬🇧'};

    function updateOrder(){
        list.children('[data-guide-row]').each(function(index){
            $(this).find('[data-guide-order]').val((index + 1) * 10);
        });
    }

    function refreshSummary(row){
        var title = $.trim(row.find('[data-guide-title="fr"]').val() || '');
        if (!title) title = $.trim(row.find('[data-guide-title="en"]').val() || '');
        if (!title) title = $.trim(row.find('[data-guide-title="de"]').val() || '');
        if (!title) title = 'Nouveau guide';
        row.find('[data-guide-summary-title]').text(title);

        var cycle = row.find('[data-guide-cycle]').val() || 'cycle1';
        var status = row.find('[data-guide-status]').val() || 'available';
        row.find('.htp-1178-guide-summary-main small').text((labels[cycle] || cycle) + ' · ' + (statuses[status] || status));

        var languages = [];
        row.find('[data-guide-language]:checked').each(function(){
            var language = $(this).data('guide-language');
            if (flags[language]) languages.push('<span>' + flags[language] + '</span>');
        });
        row.find('[data-guide-summary-languages]').html(languages.join(''));
    }

    root.on('click','[data-add-guide]',function(){
        var index = Date.now();
        var html = template.html().replaceAll('__INDEX__', index);
        var row = $(html);
        list.append(row);
        row.prop('open', true);
        updateOrder();
        refreshSummary(row);
    });

    root.on('click','[data-remove-guide]',function(){
        var row = $(this).closest('[data-guide-row]');
        var title = $.trim(row.find('[data-guide-summary-title]').text());
        if (window.confirm('Supprimer « ' + title + ' » de cette saison ? Les statistiques historiques associées à son ID resteront conservées.')) row.remove();
    });

    root.on('click','[data-media-field]',function(event){
        event.preventDefault();
        var button = $(this);
        var target = button.siblings('input[type=url]');
        var type = button.data('media-field');
        var frame = wp.media({
            title: type === 'pdf' ? 'Choisir un PDF' : 'Choisir une image',
            multiple: false,
            library: type === 'pdf' ? {type:'application/pdf'} : {type:'image'}
        });
        frame.on('select',function(){
            target.val(frame.state().get('selection').first().toJSON().url).trigger('change');
        });
        frame.open();
    });

    root.on('input change','[data-guide-title],[data-guide-cycle],[data-guide-status],[data-guide-language]',function(){
        refreshSummary($(this).closest('[data-guide-row]'));
    });

    if ($.fn.sortable) {
        list.sortable({
            items:'[data-guide-row]',
            handle:'[data-guide-handle]',
            update:updateOrder
        });
    }
})(jQuery);
