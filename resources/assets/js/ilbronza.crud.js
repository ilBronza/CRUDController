require('./ilBronza.crud.nestable.min.js');
require('./ilBronza.crud.dropzone.min.js');

    jQuery(document).ready(function ($)
    {
        $('body').on('click', '.fieldreplicatorbutton', function (e)
        {
            e.preventDefault();

            var that = $(this);
            var url = that.data('repeatable-url');

            $.ajax({
                url: url,
                type: 'POST',
                success: function (response)
                {
                    window.addSuccessNotification(response.message);

                    // inserisce il nuovo field subito dopo l’ultimo fieldcontainer prima del bottone
                    that.prevAll('.fieldcontainer').first().after(response.html);
                },
                error: function (response)
                {
                    window.addDangerNotification(response.message);
                }
            });
        });


        // Show delete button on hover for repeatable instances except the first one
        $('body').on('mouseenter', '.fieldcontainer.ib-repeatable:not(.ib-first-field)', function ()
        {
            var $container = $(this);

            // Prevent duplicates
            if ($container.find('.ib-repeatable-delete-wrap').length)
                return;

            var $controls = $container.find('> .uk-form-controls').first();
            if (! $controls.length)
            {
                // Fallback for nested markup
                $controls = $container.find('.uk-form-controls').first();
            }

            if (! $controls.length)
                return;

            var deleteUrl = $container.data('delete-url') || $controls.data('delete-url') || '#';

            var $wrap = $('<div class="ib-repeatable-delete-wrap"></div>');
            var $btn = $('<button type="button" class="uk-button uk-button-small uk-button-danger ib-repeatable-delete-btn" title="Delete instance"><i class="fas fa-trash"></i></button>');

            $btn.data('delete-url', deleteUrl);

            $wrap.append($btn);

            $controls.append($wrap);
        });

        // Remove delete button when leaving the container
        $('body').on('mouseleave', '.fieldcontainer.ib-repeatable:not(.ib-first-field)', function ()
        {
            $(this).find('.ib-repeatable-delete-wrap').remove();
        });

        // Handle delete click
        $('body').on('click', '.ib-repeatable-delete-btn', function (e)
        {
            e.preventDefault();
            e.stopPropagation();

            var $btn = $(this);
            var $container = $btn.closest('.fieldcontainer.ib-repeatable');

            var deleteUrl = $container.data('delete-instance-url');

            if (! deleteUrl || deleteUrl === '#')
            {
                window.addDangerNotification('Missing delete URL');
                return;
            }

            if (! confirm('Delete this field instance?'))
                return;


            $.ajax({
                url: deleteUrl,
                type: 'POST',
                data: { _method: 'DELETE' },
                success: function (response)
                {
                    window.addSuccessNotification(response.message || 'Deleted');
                    $container.remove();
                },
                error: function (response)
                {
                    window.addDangerNotification((response.responseJSON && response.responseJSON.message) ? response.responseJSON.message : 'Delete failed');
                }
            });
        });


    });
