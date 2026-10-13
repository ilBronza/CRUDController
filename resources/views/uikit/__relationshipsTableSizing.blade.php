@once
<script type="text/javascript">
    (function ($)
    {
        function adjustRelationshipTables(container)
        {
            // Measure after UIkit has made the panel visible and settled its layout.
            window.requestAnimationFrame(function ()
            {
                if (! $.fn.dataTable)
                    return;

                // Use registered tables so scrolling-header clones are never initialized.
                $.each($.fn.dataTable.tables({ visible: true }), function (_, table)
                {
                    if (table !== container && ! $.contains(container, table))
                        return;

                    var api = new $.fn.dataTable.Api(table);
                    api.columns.adjust();

                    if (api.responsive && typeof api.responsive.recalc === 'function')
                        api.responsive.recalc();

                    if (api.fixedHeader && typeof api.fixedHeader.adjust === 'function')
                        api.fixedHeader.adjust();
                });
            });
        }

        $(document)
            .off('.crudRelationshipTables')
            .on('shown.crudRelationshipTables', '.relationships', function (event)
            {
                adjustRelationshipTables(event.target);
            })
            .on('init.dt.crudRelationshipTables', '.relationships table', function (event, settings)
            {
                // AJAX initialization can finish after the panel's shown event.
                adjustRelationshipTables(settings.nTable);
            });

        $(function ()
        {
            $('.relationships').each(function ()
            {
                adjustRelationshipTables(this);
            });
        });
    })(jQuery);
</script>
@endonce
