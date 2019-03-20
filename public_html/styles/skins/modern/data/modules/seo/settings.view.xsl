<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/seo">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<!-- Шаблон вкладки "Анализ позиций" -->
    <xsl:template match="/result[@module = 'seo' and @method = 'seo']/data[@type = 'settings' and @action = 'view']">
		<xsl:variable name="http_host" select="//option[@name='http_host']/value" />
		<script type="text/javascript"><![CDATA[
			var domainSelectize = null;
			var seo = new function(){
				var sort = 'word';
				var order = 'asc';
				var sortData = [];
				
				this.getData = function( host ) {
					if (host == '') return false;
					var that = this;
					jQuery.ajax({
						type: "GET",
						url: "/admin/seo/seo/.xml?" + "host=" + host,
						dataType: "xml",
						success: function(doc){

							var errors = doc.getElementsByTagName('error');
							if (errors.length) {

								var error = "<div>" +
									errors[0].firstChild.nodeValue.replace('&lt;', '<').replace('&gt;', '>') +
									"<form action=\"\" method=\"get\"><input type=\"hidden\" name=\"host\" value=\"" + host + "\"><div class=\"buttons\" style=\"padding-top:5px;\"><div class=\"button\" class=\"btn color-blue\" style=\"float:left;\"><input type=\"submit\" value=\"" +
									getLabel('js-panel-repeat') +
									"\" /></div></div></form></div>";
									jQuery('#result').html(error);
									return;

							} else {

								var items =  doc.getElementsByTagName('item');
								sortData = {
									'word':[],
									'pos_y':[],
									'pos_g':[],
									'show_month':[],
									'wordstat':[]
								};
								for (var i = 0; i < items.length; i++) {
									var item = items[i];

									var tr = document.createElement('tr')
									
									var show_month = '';
									if( item.hasAttribute('show_month') )
										show_month = item.getAttribute('show_month');
										
									tr.innerHTML = "<td>" +
										item.getAttribute('word') +
										"</td><td class=\"center\"><a href=\"http://yandex.ru/yandsearch?text=" +
										item.getAttribute('word') +
										"&amp;lr=2'\" title=\"\" target=\"_blank\">"+
										item.getAttribute('pos_y') +
										"</a></td><td class=\"center\"><a href=\"http://www.google.ru/#sclient=psy&amp;hl=ru&amp;newwindow=1&amp;site=&amp;source=hp&amp;q="+
										item.getAttribute('word') +
										"\" title=\"\" target=\"_blank\">"+
										item.getAttribute('pos_g') +
										"</a></td><td class=\"center\">" +
										show_month +
										"</td><td class=\"center\">"+
										item.getAttribute('wordstat') +
										"</td>";
									
									sortData.word.push({key:item.getAttribute('word'),tr:tr}); 
									sortData.pos_y.push({key:item.getAttribute('pos_y'),tr:tr});
									sortData.pos_g.push({key:item.getAttribute('pos_g'),tr:tr});
									sortData.show_month.push({key:item.getAttribute('show_month'),tr:tr});
									sortData.wordstat.push({key:item.getAttribute('wordstat'),tr:tr});
								}
								
								var sortAsString = function( a, b ) {
									var aa = a.key.toString();
									var bb = b.key.toString();
									if( aa > bb ) {
										return 1
									}
									if( aa < bb ) {
										return -1;
									}
									return 0;
								}
								
								var sortAsNumber = function( a, b ) {
									var aa = a.key * 1;
									var bb = b.key * 1;
									if( isNaN( aa ) ) aa = 0;
									if( isNaN( bb ) ) bb = 0;
									if( aa > bb ) {
										return 1
									}
									if( aa < bb ) {
										return -1;
									}
									return 0;
								}
								
								
								for( var k in sortData ) {
									if( k == 'word') continue;
									sortData[k].sort( sortAsNumber );
								}
								sortData['word'].sort( sortAsString );
								
								console.log( sortData );
								that.sortData( sort, order )
							}
						}
					});
				}
				
				this.sortData = function( newSort, newOrder ) {
					sort = newSort;
					order = newOrder;
					if( sortData[ sort ] === undefined ) return;
					
					if( order == 'desc' ) {
						sortData[sort].reverse()
					}
					var tbody = jQuery('tbody');
					tbody.html('')
					for (var i = 0; i < sortData[sort].length; i++) {
						tbody.append( sortData[sort][i].tr );
					}
					console.log( sortData[sort].length )
					if( order == 'desc' ) {
						sortData[sort].reverse()
					}
				}
			}
			
			$(document).ready(function() {
				jQuery('.sort span').click(function(){
					var sort = jQuery(this).attr('id');
					var host = jQuery('#host').attr('value');

					var order = jQuery(this).attr('class') == "asc" ? "desc" : "asc";
					seo.sortData( sort, order);

					jQuery('.sort span').removeAttr('class');
					jQuery(this).attr('class', order);
				});

				seo.getData( $('#host').val() );
			});
			
			]]></script>
		<div class="location">
			<xsl:call-template name="entities.help.button" />
		</div>
		<div class="layout">
			<div class="column">
				<div id="webo_in">
					<div class="panel-settings">
						<div class="title">
							<h3>
								<xsl:text>&label-site-analysis;</xsl:text>
							</h3>
						</div>
						<div class="content">
							<form action="" method="get">
								<div class="field">
									<label for="host" style="position:relative; display: block;  width: 100%;">
										<span class="label">
											<acronym>
												&label-site-address;
											</acronym>
										</span>
										<input type="text" autocomplete="off" name="host" value="{$http_host}" id="host"
											   style="position: absolute; width: 90%; background-color: #fff; border-right: medium none; outline: medium none; z-index: 400; bottom: -26px; left: 10px;"/>
									</label>
									<select autocomplete="off" class="default newselect"
											onchange="getElementById('host').value = this.options[this.selectedIndex].innerHTML; this.selectedIndex=0"
											id="domain-selector">
										<option selected="selected"/>
										<xsl:apply-templates select="$domains-list" mode="domain-selector"/>
									</select>
								</div>
								<div class="buttons" style="padding-top:5px;">
									<div class="button">
										<input type="submit" value="&label-button;" class="btn color-blue btn-small"/>
									</div>
								</div>
							</form>
						</div>
					</div>
					<div class="panel-settings">
						<div class="title field-group-toggle">
							<div class="round-toggle"/>
							<h3>
								<xsl:text>&label-results;</xsl:text>
							</h3>
						</div>
						<div class="content" id="result">
							<table class="btable btable-bordered btable-striped">
								<thead>
									<tr>
										<th class="sort">
											<span id="word">
												<xsl:text>&label-query;</xsl:text>
											</span>
										</th>
										<th class="sort">
											<span id="pos_y">
												<xsl:text>&label-yandex;</xsl:text>
											</span>
										</th>
										<th class="sort">
											<span id="pos_g">
												<xsl:text>&label-google;</xsl:text>
											</span>
										</th>
										<th class="sort" style="width:200px;">
											<span id="show_month" style="width:200px;">
												<xsl:text>&label-count;</xsl:text>
											</span>
										</th>
										<th class="sort">
											<span id="wordstat">
												<xsl:text>&label-wordstat;</xsl:text>
											</span>
										</th>
									</tr>
								</thead>
								<tbody>
								</tbody>
							</table>
							<div style="margin-top:20px; font-size:10px;">Основано на данных
								<a href="http://www.megaindex.ru?from=89477" target="_blank">megaindex.ru</a>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон домена контроле выбора домена -->
	<xsl:template match="domain" mode="domain-selector">
		<option value="{@id}"><xsl:value-of select="@decoded-host"/></option>
	</xsl:template>

	<!-- Шаблон вкладки "Анализ ссылок" -->
	<xsl:template match="/result[@module = 'seo' and @method = 'links']/data[@type = 'settings' and @action = 'view']">
		<xsl:variable name="http_host" select="//option[@name='http_host']/value" />
		<div class="location">
			<xsl:call-template name="entities.help.button" />
		</div>
		<div id="webo_in" class="layout">
			<div class="column">
				<div class="panel-settings">
					<div class="title">
						<h3>
							<xsl:text>&label-site-analysis;</xsl:text>
						</h3>
					</div>
					<div class="content">
						<form action="" method="get">
							<div class="field">
								<label for="host" style="position:relative; display: block;  width: 100%;">
									<span class="label">
										<acronym>
											&label-site-address;
										</acronym>
									</span>
									<input type="text" name="host" value="{$http_host}" id="host" style="position: absolute; width: 90%; background-color: #fff; border-right: medium none; outline: medium none; z-index: 400; bottom: -26px; left: 10px;"/>
								</label>
								<select class="default newselect" onchange="getElementById('host').value = this.options[this.selectedIndex].innerHTML; this.selectedIndex=0"
										id="domain-selector">
									<option selected="selected"/>
									<xsl:apply-templates select="$domains-list" mode="domain-selector"/>
								</select>
							</div>
							<div class="buttons" style="padding-top:5px;">
								<div class="button">
									<input type="submit" value="&label-button;" class="btn color-blue btn-small"/>
								</div>
							</div>
						</form>
					</div>
				</div>
				<div class="panel-settings">
					<div class="title field-group-toggle">
						<div class="round-toggle"/>
						<h3>
							<xsl:text>&label-results;</xsl:text>
						</h3>
					</div>
					<div class="content" id="result">
						<xsl:choose>
							<xsl:when test="errors/error">
								<xsl:apply-templates select="errors/error" mode="backlinks-error"/>
							</xsl:when>
							<xsl:otherwise>
								<table class="btable btable-bordered btable-striped">
									<thead>
										<tr>
											<th>
												<span>
													<xsl:text>&label-link-from;</xsl:text>
												</span>
											</th>
											<th>
												<span>
													<xsl:text>&label-link-to;</xsl:text>
												</span>
											</th>
											<th>
												<span>
													<xsl:text>nofollow</xsl:text>
												</span>
											</th>
											<th>
												<span>
													<xsl:text>noindex</xsl:text>
												</span>
											</th>
											<th>
												<span>
													<xsl:text>&label-tic-from;</xsl:text>
												</span>
											</th>
											<th>
												<span>
													<xsl:text>&label-tic-to;</xsl:text>
												</span>
											</th>
											<th>
												<span>
													<xsl:text>&label-link-anchor;</xsl:text>
												</span>
											</th>
										</tr>
									</thead>
									<tbody>
										<xsl:apply-templates select="links/link" mode="backlinks"/>
									</tbody>
								</table>
							</xsl:otherwise>
						</xsl:choose>
						<div style="margin-top:20px; font-size:10px;">Основано на данных
							<a href="http://www.megaindex.ru?from=89477" target="_blank">megaindex.ru</a>
						</div>
					</div>
				</div>
			</div>
			<div class="column">
				<xsl:call-template name="entities.help.content" />
			</div>
		</div>
	</xsl:template>

	<!-- Шаблон ошибки при формирования списка ссылок -->
	<xsl:template select="error" mode="backlinks-error">
		<div><xsl:value-of select="."/></div>
	</xsl:template>

	<!-- Шаблон ссылки в списке ссылок -->
	<xsl:template match="link" mode="backlinks">
		<tr>
			<td>
				<span><a href="{@vs_from}"><xsl:value-of select="@vs_from"/></a></span>
			</td>
			<td class="center" style="width:auto;">
				<span><a href="{@vs_from}"><xsl:value-of select="@vs_to"/></a></span>
			</td>
			<td class="center">
				<span><xsl:value-of select="@nof"/></span>
			</td>
			<td class="center">
				<span><xsl:value-of select="@noi"/></span>
			</td>
			<td class="center">
				<span><xsl:value-of select="@tic_from"/></span>
			</td>
			<td class="center">
				<span><xsl:value-of select="@tic_to"/></span>
			</td>
			<td class="center" style="width:auto;">
				<span><xsl:value-of select="@text"/></span>
			</td>
		</tr>
	</xsl:template>

</xsl:stylesheet>
