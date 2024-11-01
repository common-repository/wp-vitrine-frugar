(function() {
	tinymce.create('tinymce.plugins.wpvitrinefrugar', {

		init : function(ed, url) {
			var t = this;
			t.url = url;

			ed.addCommand('wpvitrinefrugar', function() {
				var el = ed.selection.getNode(), vp = tinymce.DOM.getViewPort(), H = vp.h, W = ( 990 < vp.w ) ? 990 : vp.w, cls = ed.dom.getAttrib(el, 'class');
				tb_show(tinymce.activeEditor.translate('wpvitrinefrugar.embed_desc'), url + '/embedform.html?TB_iframe=true&v=1.1');

				jQuery("#TB_window iframe").attr("scrolling", "no").parent().addClass("frugarFrame").find("#TB_ajaxWindowTitle").text("Vitrine Frugar");
				tinymce.DOM.setStyles('TB_window', {
					'width':( W - 50 )+'px',
					'height':( H - 45 )+'px',
					'top':'20px',
					'marginTop':'0',
					'margin-left':'-'+parseInt((( W - 50 ) / 2),10) + 'px'
				});

				tinymce.DOM.setStyles('TB_iframeContent', {
					'width':( W - 50 )+'px',
					'height':( H - 75 )+'px'
				});
				
				tinymce.DOM.setStyle( ['TB_overlay','TB_window','TB_load'], 'z-index', '999999' );
			});

			ed.addButton('wpvitrinefrugar', {
				title : 'wpvitrinefrugar.embed_desc',
				image : wpVitrineFrugarConfig().buttonURL,
				onclick : function(){
					ed.execCommand('wpvitrinefrugar');
				}
			});
		},
		getInfo : function() {
			return {
				longname : tinymce.activeEditor.translate('wpvitrinefrugar.longname'),
				author : 'Frugar',
				infourl : '',
				version : "1.0"
			};
		}
	});

	tinymce.PluginManager.add('wpvitrinefrugar', tinymce.plugins.wpvitrinefrugar);
})();
