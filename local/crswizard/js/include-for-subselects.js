$(document).ready(function() {
    $('select.transformIntoSubselects').transformIntoSubselects({
        separator: / \/ /,
        labels: ["Période :", "Établissement :", "Composante :", "Type de diplôme :"]
    });

    /**
    $('#fitem_id_category > div.subselects > div:first-child').change(
        function() {
            var etab = $('#fitem_id_category > div.subselects > div:nth-child(2) > div.felement > select');
            if (etab.children().length == 2) {
                var paris = etab.children('option').eq(1);
                etab.val(paris.val()).trigger('change');
            }
        }
    );**/
});
