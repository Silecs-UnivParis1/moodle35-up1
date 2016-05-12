$(document).ready(function() {
    if ($("#id_urlok").attr("checked")) {
        $("#blocUrl").removeClass('cache');
    };

    $("#id_urlok").click(
        function() {
            if ($(this).attr("checked")) {
                $("#blocUrl").removeClass('cache');
            } else {
                $("#blocUrl").addClass('cache');
            }
    });

    if ($('#id_urlmodel_myurl').size()) {
        if (! $("#id_urlmodel_myurl").attr("checked")) {
            $("#fitem_id_myurl").addClass('cache');
        }
    }

    $("#id_urlmodel_myurl").click(
        function() {
            if ($(this).attr("checked")) {
                $("#fitem_id_myurl").removeClass('cache');
            }
    });
    $("#id_urlmodel_fixe").click(
        function() {
            if ($(this).attr("checked")) {
                $("#fitem_id_myurl").addClass('cache');
            }
    });

});
