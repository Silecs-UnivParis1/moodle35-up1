/*
 * @license http://www.gnu.org/licenses/gpl-3.0.html  GNU GPL v3
 */
(function() {

    var rootUrl = findScriptUrl('widget.js');
    var ieWait = 2; // number of scripts, for IE < 9

    if (window.jQuery === undefined) {
        loadJs(rootUrl + "../jquery/jquery.js");
        loadJs(rootUrl + "assets/tree.jquery.js", true);
    } else if (window.jQuery.fn.tree === undefined) {
        ieWait--;
        loadJs(rootUrl + "assets/tree.jquery.js", true);
    } else {
        onLoadFinished();
    }

    function findScriptUrl(name) {
        var scripts = document.getElementsByTagName('script');
        for (var i=0; i<scripts.length; i++) {
            if (scripts[i].src.indexOf('/'+name) !== -1) {
                return scripts[i].src.replace('/'+name, '/');
            }
        }
        return false;
    }

    function loadJs(url, last) {
        var script_tag = document.createElement('script');
        script_tag.setAttribute("type","text/javascript");
        script_tag.setAttribute("src", url);
        (document.getElementsByTagName("head")[0] || document.documentElement).appendChild(script_tag);
        if (last !== undefined) {
            if (script_tag.readyState) { // IE < 9
                script_tag.onreadystatechange = function () {
                    if (this.readyState === 'complete' || this.readyState === 'loaded') {
                        script_tag.onreadystatechange = null; // bug IE8
                        ieWait--;
                        if (ieWait === 0) {
                            onLoadFinished();
                        }
                    }
                };
            } else {
                ieWait = 0;
                script_tag.onload = onLoadFinished;
            }
        } else {
            if (script_tag.readyState) { // IE < 9, bug on async script loading
                script_tag.onreadystatechange = function () {
                    if (this.readyState === 'complete' || this.readyState === 'loaded') {
                        script_tag.onreadystatechange = null; // bug IE8
                        ieWait--;
                        if (ieWait === 0) {
                            onLoadFinished();
                        }
                    }
                };
            }
        }
    }

    function onLoadFinished() {
        var linkTag = document.createElement('link');
        linkTag.setAttribute("type","text/css");
        linkTag.setAttribute("rel","stylesheet");
        linkTag.setAttribute("href", rootUrl + 'assets/jqtree.css');
        (document.getElementsByTagName("head")[0] || document.documentElement).appendChild(linkTag);

        jQuery(function () {
            $('.coursetree').each(function(){
                var $tree = $(this);
                var rootNode = $tree.data('root');
                $tree.tree({
                    dataUrl: function(node) {
                        var result = {
                            "url": rootUrl + 'service-children.php',
                            "data": {
                                "node": (node ? $(node).attr('id') : rootNode),
                                "debug": $tree.data('debug'),
                                "stats": $tree.data('stats'),
                            }
                        };
                        return result;
                    },
                    onCreateLi: function(node, $li) {
                        if (!node.load_on_demand && !('is_open' in node) && node.children.length === 0) {
                            var $name = $li.find('.jqtree-title:first').first().find('.coursetree-name').first();
                            var $info = $('.jqtree-title > .coursetree-info:first', $li).first();
                            setTimeout(function(){ // trick to wait for the CSS to be applied
                                var lineWidth = $li.width();
                                var spansWidth = 0;
                                $('.jqtree-title > span:not(.coursetree-name)', $li).each(function() { spansWidth += $(this).width(); });
                                $name.width(function(i,w){
                                    return (lineWidth - spansWidth - 30 - ($info ? $info.width() : 0)); // 20px margin-right
                                });
                            }, 0);
                        }
                    },
                    autoEscape: false, // allow HTML labels
                    autoOpen: false,
                    openedIcon: $("<img style='margin-top: -4px' src='" + M.util.image_url('t/expanded', 'core') + "'>"),
                    closedIcon: $("<img style='margin-top: -4px' src='" + M.util.image_url('t/collapsed', 'core') + "'>"),
                    slide: false, // turn off the animation
                    dragAndDrop: false
                });
            });
            $(window).resize(function () {
                $('.coursetree-name').each(function() {
                    var n = $(this);
                    var title = n.closest('.jqtree-title');
                    var $info = title.find('.coursetree-info:first').first();
                    var spansWidth = 0;
                    n.nextAll().each(function() { spansWidth += $(this).width(); });
                    var w = title.parent().width() - spansWidth - 30 - ($info ? $info.width() : 0);
                    n.width(w);
                });
            });
        });
    }

})();
