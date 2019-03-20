<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/vote">

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	
    <xsl:template match="field[@name = 'answers' and @type = 'relation']" mode="form-modify"/>

	<xsl:template match="page|object" mode="form-modify">
		<xsl:apply-templates select="properties/group" mode="form-modify" />
		<xsl:if test="$data-action = 'modify'">
			<xsl:call-template name="answers-list-group"/>
		</xsl:if>
	</xsl:template>	

	<!-- Шаблон контрола вариантов ответов на опрос -->
	<xsl:template name="answers-list-group">
		<xsl:variable name="groupIsHidden" select="contains($hiddenGroupNameList, 'answers')"/>

		<div class="panel-settings properties-group">
			<summary class="group-tip">
				<xsl:text>Управление вариантами ответов на опрос.</xsl:text>
			</summary>
			<a data-name="answers" data-label="answers"/>
			<div class="title">
				<div class="field-group-toggle">
					<div>
						<xsl:attribute name="class">
							<xsl:choose>
								<xsl:when test="$groupIsHidden">
									<xsl:text>round-toggle switch</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<xsl:text>round-toggle</xsl:text>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
					</div>
					<h3>
						<xsl:text>&label-group-answers;</xsl:text>
					</h3>
				</div>
				<xsl:call-template name="group-tip">
					<xsl:with-param name="group" select="'answers'" />
					<xsl:with-param name="force-show" select="1" />
					<xsl:with-param name="isHidden" select="$groupIsHidden" />
				</xsl:call-template>
			</div>
			<div class="content">
				<xsl:if test="$groupIsHidden">
					<xsl:attribute name="style">
						<xsl:text>display: none;</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<div class="layout">
					<div class="column">
						<div class="row">
							<xsl:call-template name="answers_list_form"/>
						</div>
					</div>
					<div class="column">
						<xsl:call-template name="entities.tip.content" />
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="item">
		<xsl:param name="title" select="."/>
		<xsl:param name="id" select="@id"/>

		<tr>
			<td>
				<input type="text" name="answer[{$id}]" value="{$title}"/>
			</td>
			<td style="text-align:center;">&nbsp;</td>
		</tr>
	</xsl:template>


	<xsl:template name="answers_list_form">
		<xsl:attribute name="id">answers_list</xsl:attribute>
		<table border="0" width="100%">
			<tbody>
				<tr>
					<td style="text-align:center;">
						<br/>
						<img src="/images/cms/loading.gif"/>
						<br/>&label-wait;
					</td>
				</tr>
			</tbody>
		</table>
		<script type="text/javascript">
			var listDrawCallback = function(xml) {
			<![CDATA[
				if(!xml) return;
				var poll_id = $("data", xml).get(0).getAttribute('object_id');
				var objects = $("object", xml).get();
				var html = "<p><table width=\"100%\" ><tbody>";
				html += "<tr><td width=\"100%\" >]]>&label-text;<![CDATA[</td><td style=\"width:45px;\" >]]>&label-count;<![CDATA[</td><td style=\"width:45px;\" >]]>&label-delete;<![CDATA[</td></tr>]]>";
				<![CDATA[
				for(i=0; i < objects.length; i++) {
					var id = objects[i].getAttribute('id');
					var count = 0;
					var props = objects[i].getElementsByTagName('property');
					for(j=0; j < props.length; j++) {
						if(props[j].getAttribute('name') == 'count') {
							var countNode = props[j].getElementsByTagName('value').item(0).firstChild;
							//Bugfix #0002677: Expecting firstChild in value to be #textNode containing needle value
							count = (props[j].getElementsByTagName('value')).item(0).firstChild.nodeValue;
						}
					}
					html += "<tr><td><input style=\"width:100%;\" type=\"text\" class=\"default\" name=\"data["+id+"][name]\" value=\"" +
							objects[i].getAttribute('name') + "\" onkeydown=\"return onKeyDownCallback(event)\" />\n</td>"+
							"<td><input style=\"width:40px;\" type=\"text\" class=\"default\" name=\"data["+id+"][count]\" value=\"" +
							count + "\" onkeydown=\"return onKeyDownCallback(event)\" />"+
							"<input type=\"hidden\" name=\"data["+id+"][poll_rel]\" value=\""+poll_id+"\" />" +
							"</td>\n<td style=\"text-align:center;\" > <div class=\"checkbox\"> <input type=\"checkbox\" name=\"dels[]\" value=\""+id+"\" class=\"checkbox\" /> </div> </td>"+
							"</tr>";
				}
				html += "<tr><td><input style=\"width:100%;\" type=\"text\" class=\"default\" name=\"data[new][name]\" value=\"\" onkeydown=\"return onKeyDownCallback(event)\" /></td>"+
						"<td> "+
						"<input type=\"hidden\" name=\"data[new][poll_rel]\" value=\""+poll_id+"\" />" +
						"</td><td> </td>"+
						"</tr>";
				html += "</tbody></table></p>";
				html += "<div class=\"tbutton\"><input type=\"button\" class=\"btn color-blue\" value=\"]]>&label-save;<![CDATA[\" onClick=\"javascript:saveAnswersList();\" /><span class=\"l\"></span><span class=\"r\"></span></div>";
				var FormContent = $('#answers_list');
				FormContent.get(0).innerHTML = html;

				$('#answers_list').find('input.checkbox').click(function (e) {
					var $input = $(e.target);
					$input.toggleClass('checked');
					$input.parent().toggleClass('checked');
				});
			}
			var _poll_id = parseInt("]]><xsl:value-of select="$param0"/><![CDATA[");
			if(_poll_id != 0) {
				$.get("]]><xsl:value-of select="$lang-prefix"/><![CDATA[/admin/vote/answers_list/]]><xsl:value-of select="$param0"/><![CDATA[/.xml?viewMode=full",
						{},
						listDrawCallback);
			} else {
				$('#answers_list').html("<table border=\"0\" width=\"100%\" ><tbody><tr><td align=\"center\" valign=\"middle\">]]>&error_save_page_first;<![CDATA[</td></tr></tbody></table>");
			}
			function onKeyDownCallback(e) {
				var keynum;
				if(window.event) // IE
				{
					keynum = e.keyCode;
				}
				else if(e.which) // Netscape/Firefox/Opera
				{
					keynum = e.which;
				}
				if(keynum == 13) {
					saveAnswersList();
					return false;
				}
			}
			function saveAnswersList() {
				var data = new Object();
				data['csrf'] = csrfProtection.getToken();
				$('#answers_list input').each(function() {
					var type = this.getAttribute('type');
					if(type == 'checkbox') {
						if(this.checked)
							data['dels['+this.value+']'] = this.value;
					} else {
						data[this.getAttribute('name')] = this.value;
					}
				});
				$('#answers_list').html('<table border=\"0\" width=\"100%\" ><tbody><tr><td style=\"text-align:center;\" ><br /><img src=\"/images/cms/loading.gif\" /><br />]]>&label-wait;<![CDATA[</td></tr></tbody></table>');
				$.post("]]><xsl:value-of select="$lang-prefix"/><![CDATA[/admin/vote/answers_list/]]><xsl:value-of select="$param0"/><![CDATA[/do/.xml?viewMode=full",
					   data,
					   listDrawCallback);
			}]]>
		</script>
	</xsl:template>

</xsl:stylesheet>
