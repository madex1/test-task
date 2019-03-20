<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/photoalbum" [
		<!ENTITY sys-module        'photoalbum'>
		<!ENTITY sys-method-add        'add'>
		<!ENTITY sys-method-edit    'edit'>
		<!ENTITY sys-method-del        'del'>
		<!ENTITY sys-method-list    'lists'>
		<!ENTITY sys-method-acivity     'activity'>

		<!ENTITY sys-type-list        'album'>
		<!ENTITY sys-type-item        'photo'>
		]>


<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xlink="http://www.w3.org/TR/xlink">

	<xsl:template match="data[@type = 'list' and @action = 'view']">
		<div class="tabs-content module">
			<div class="section selected">
				<script language="javascript">
				<xsl:choose>
					<xsl:when test="/result/@demo">
					function photoalbumShowForm(parentId) {
						jQuery.jGrowl('<p>В демонстрационном режиме эта функция недоступна</p>', {
							'header': 'UMI.CMS',
							'life': 10000
							});
						return false;
					}
					function submitUploadImages() {
						jQuery.jGrowl('<p>В демонстрационном режиме эта функция недоступна</p>', {
							'header': 'UMI.CMS',
							'life': 10000
							});
						return false;
					}
					</xsl:when>
					<xsl:otherwise>
					<![CDATA[
					function submitUploadImages() {
						var aPar = document.getElementById("a_parent_id");
						var formSubmited = document.getElementById("upload_images_form");
						formSubmited.action = aPar;
						return true;
					}
					function photoalbumShowForm(parentId) {
						openDialog('', "Загрузка из архива",
							{html : "<form id='zipform' action='/admin/photoalbum/upload_arhive/' method='post' enctype='multipart/form-data' >\
									<input type='hidden' name='csrf' value='' />\
									<label for='upload'>" + getLabel('js-label-arhive-from-pc') + "</label>\
									<input type='file' id='upload' name='zip_arhive' style='width:100%' />\
									<label for='path'>" + getLabel('js-label-arhive-from-src') + "</label>\
									<input type='text' id='path' name='zip_arhive_src' style='width:100%' class='default'/>\
									<div class='checkbox'>\
										<input type='checkbox' class='checkbox' id='watermark' name='watermark'/>\
									</div>\
									<span>" + getLabel('js-label-add-watermark') + "</span>\
									<input type='hidden' name='parent_id' value='" + parentId + "' />\
								</form>",
							confirmText : getLabel("js-label-add-arhive-upload"),
							confirmCallback: function () {
									jQuery('form#zipform input[name="csrf"]').val(csrfProtection.getToken());
									jQuery('#zipform').submit();
									return false;
							},
							openCallback: function() {
								$('.checkbox').click(function () {
									$(this).toggleClass('checked');
								});
							}
						});
					}
					]]>
					</xsl:otherwise>
				</xsl:choose>
				<![CDATA[
					function fs_add_to_upload(oFileInput) {
						var oReadyUpload = document.getElementById('fs_ready_upload');
						var oNextUploadDiv = document.getElementById('fs_next_upload');
						if (oFileInput && oReadyUpload && oNextUploadDiv) {
							oFileInput.style.visibility = "hidden";
							oFileInput.style.display = "none";
							var oNextFile = document.createElement('li');
							var li = oNextFile;
							var span = document.createElement('span');
							span.title = oFileInput.value;
							span.appendChild(document.createTextNode(oFileInput.value));
							li.appendChild(span);

							var a = document.createElement('a');
							a.href = "#";
							a.onclick = function() {
								oFileInput.parentNode.removeChild(oFileInput);
								li.parentNode.removeChild(li);
								return false;
							};

							a.style.marginLeft = "15px";
							a.appendChild(document.createTextNode(getLabel('js-delete')));
							a.className = "btn color-blue";

							li.appendChild(a);

							oReadyUpload.appendChild(oNextFile);

							var oNextUpload = document.createElement('INPUT');
							oNextUpload.type = 'file';
							oNextUpload.name = 'fs_upl_files[]';
							oNextUpload.className = 'std';
							oNextUpload.size = 50;
							oNextUpload.onchange = function() {
								fs_add_to_upload(oNextUpload);
							};

							oNextUploadDiv.appendChild(oNextUpload);
						}
					}
				]]>
				</script>
				<div class="hidden_file_upload">
					<form name="fs_upload_frm" action="#" enctype="multipart/form-data" method="post">
						<input name="fs_upl_files[]" multiple="multiple" id="quick_upload_field" type="file" />
						<input type="hidden" name="csrf" value="" />
					</form>
				</div>
				<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
					<div class="imgButtonWrapper" xmlns:umi="http://www.umi-cms.ru/TR/umi">
						<a id="addAlbum" href="{$lang-prefix}/admin/&sys-module;/&sys-method-add;/{$param0}/&sys-type-list;/" class="btn color-blue loc-left" umi:type="photoalbum::album">
							<xsl:text>&label-add-album;</xsl:text>
						</a>

						<a id="addPhoto" href="{$lang-prefix}/admin/&sys-module;/&sys-method-add;/{$param0}/&sys-type-item;/" class="btn color-blue loc-left" umi:type="photoalbum::photo">
							<xsl:text>&label-add-photo;</xsl:text>
						</a>

						<a id="uploadZip" href="{$lang-prefix}/admin/&sys-module;/&sys-method-list;/{$param0}/"
							onclick="photoalbumShowForm(this['param0']); return false;" class="btn color-blue loc-left">
							<xsl:text>&label-add-arhive;</xsl:text>
						</a>
						<a id="quickUpload" href="/admin/&sys-module;/uploadImages/{$param0}/" class="btn color-blue loc-left">
							<xsl:text>&module-quick-upload;</xsl:text>
						</a>
					</div>
					<xsl:call-template name="entities.help.button" />
				</div>


				<div class="layout">
					<div class="column">
						<xsl:call-template name="ui-smc-table">
							<xsl:with-param name="js-add-buttons">
								createAddButton($('#addAlbum')[0], oTable, '{$pre_lang}/admin/&sys-module;/&sys-method-add;/{$param0}/&sys-type-list;/', ['album', true]);
								createAddButton($('#addPhoto')[0], oTable, '{$pre_lang}/admin/&sys-module;/&sys-method-add;/{$param0}/&sys-type-item;/', ['album']);
								createAddButton($('#uploadZip')[0], oTable, '{$pre_lang}/admin/&sys-module;/&sys-method-list;/{$param0}/', ['album']);
								createAddButton($('#quickUpload')[0], oTable, '/admin/&sys-module;/uploadImages/{$param0}/', ['album']);
							</xsl:with-param>
							<xsl:with-param name="allow-drag">1</xsl:with-param>
						</xsl:call-template>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>

				<script type="text/javascript">
					$(function() {
						var $uploadField = $('#quick_upload_field');
						var $imagesUploadButton = $('#quickUpload');

						$uploadField.bind('change', function() {
							var $form = null;
							if ($(this).val()) {
								$form = $(this).closest('form');
								$form.attr('action', $imagesUploadButton.attr('href'));
								$('input[name="csrf"]', $form).val(csrfProtection.getToken());
								$form.submit();
							}
						});

						$imagesUploadButton.click(function(event) {
							event.preventDefault();
							var $quickImagesUpload = $(this);
							$uploadField.trigger('click');
						});
					});
				</script>
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>
