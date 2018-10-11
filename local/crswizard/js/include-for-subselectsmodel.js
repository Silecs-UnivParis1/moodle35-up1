$(document).ready(function() {
    var cpt= 1;
    $('select.transformIntoSubselects').transformIntoSubselects({
        separator: / \/ /,
        labels: ["Période :", "Cours :"]
    });

    if (! $("#id_modeletype_selm2").prop('checked')) {
        $("#fitem_id_selm2").addClass('cache');
        $("#bb_duplication").addClass('cache');
    }

    var selm1 = $("#id_selm1").val();
    var text = $("#id_course_summary").children('option[value='+selm1+']').text();
    $("#id_selm1").parent('fieldset').after('<div id="text_summary" class="felement fselect text_summary"><span class="text_summary">'+text+'</span></div>');

                $("#fitem_id_selm2").find(".fitemtitle").first().attr("style","float:none;display:inline;font-size:1.3em;color:#E9681D;font-weight:bold;");
                $("#fitem_id_selm2").find(".fitemtitle").first().html('Sélectionnez l\'EPI que vous souhaitez dupliquer<br /><br />');

    $("#id_modeletype_selm2").click(
        function() {
            if ($(this).prop('checked')) {

/*                if (cpt == 1) {
			$("#fitem_id_selm2").find(".fitemtitle").first().addClass("clearfix");
			$("#fitem_id_selm2").find(".fitemtitle").first().attr("style","display:block;width:0px");
			html_div_selm2 = $("#fitem_id_selm2").html();
			prevdiv = '<h3 style="color:#E9681D">Sélectionnez l\'EPI que vous souhaitez dupliquer</h3>';
			$("#fitem_id_selm2").html(prevdiv+html_div_selm2);
			cpt++;
		}
*/
                $("#fitem_id_selm2").find(".fitemtitle").first().attr("style","float:none;display:inline;font-size:1.3em;color:#E9681D;font-weight:bold;");
                $("#fitem_id_selm2").find(".fitemtitle").first().html('Sélectionnez l\'EPI que vous souhaitez dupliquer<br /><br />');
                $("#fitem_id_selm2").removeClass('cache');
                $("#bb_duplication").removeClass('cache');
            }
    });

    $("#id_modeletype_selm1").click(
        function() {
            if ($(this).prop('checked')) {
                $("#fitem_id_selm2").addClass('cache');
                $("#bb_duplication").addClass('cache');
            }
    });

    $("#id_selm1").change(
        function() {
            var sel = this.value;
            var text = $("#id_course_summary").children('option[value='+sel+']').text();
            if ($('#text_summary').length) {
                $('#text_summary').remove();
            }
            $(this).parent('fieldset').after('<div id="text_summary" class="felement fselect text_summary"><span class="text_summary">'+text+'</span></div>');
    });
});
