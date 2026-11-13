{{--
    Workspace card of the operations pages. Pass count-table (the DataTables id) and count-noun to show the shared
    "Menampilkan x dari y ..." count (x-erp.list-count) right under the toolbar; the numbers follow the table's draws.
--}}
@props(['title', 'subtitle' => '', 'icon' => 'squares-four', 'countTable' => null, 'countNoun' => null])
<section {{ $attributes->class(['erp-workspace']) }} aria-label="{{ $title }}">
    <header class="erp-workspace-heading"><div><h2>{{ $title }}</h2>@if($subtitle)<p>{{ $subtitle }}</p>@endif</div></header>
    <div class="erp-workspace-sticky">
    @isset($toolbar)<div class="erp-workspace-toolbar">{{ $toolbar }}</div>@endisset
    @if ($countTable)
        <x-erp.list-count :noun="$countNoun ?? strtolower($title)" :shown-id="$countTable.'-count-shown'" :total-id="$countTable.'-count-total'" />
    @endif
    </div>
    @if ($countTable)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (!window.jQuery) return;
                var table = '#{{ $countTable }}';
                var fill = function (settings) {
                    var info = new jQuery.fn.dataTable.Api(settings).page.info();
                    var shown = info.recordsDisplay ? info.end - info.start : 0;
                    var format = function (number) { return Number(number).toLocaleString('id-ID'); };
                    jQuery(table + '-count-shown, ' + table + '-count-shown-mobile').text(format(shown));
                    jQuery(table + '-count-total, ' + table + '-count-total-mobile').text(format(info.recordsDisplay));
                };
                jQuery(document).on('draw.dt', table, function (event, settings) { fill(settings); });
                if (jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable(table)) fill(jQuery(table).DataTable().settings()[0]);
            });
        </script>
    @endif
    <div class="erp-workspace-content">{{ $slot }}</div>
    @isset($footer)<footer class="erp-workspace-footer">{{ $footer }}</footer>@endisset
</section>
