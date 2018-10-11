$(document).ready(function() {
    if ($("#id_urlok").prop('checked')) {
        $("#blocUrl").removeClass('cache');
    };

    $("#id_urlok").click(
        function() {
            if ($(this).prop('checked')) {
                $("#blocUrl").removeClass('cache');
            } else {
                $("#blocUrl").addClass('cache');
            }
    });

    if ($('#id_urlmodel_myurl').length) {
        if (! $("#id_urlmodel_myurl").prop('checked')) {
            $("#fitem_id_myurl").addClass('cache');
        }
    }

    $("#id_urlmodel_myurl").click(
        function() {
            if ($(this).prop('checked')) {
                $("#fitem_id_myurl").removeClass('cache');
            }
    });
    $("#id_urlmodel_fixe").click(
        function() {
            if ($(this).prop('checked')) {
                $("#fitem_id_myurl").addClass('cache');
            }
    });

});
