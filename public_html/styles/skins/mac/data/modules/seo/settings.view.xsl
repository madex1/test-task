<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/seo">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:template match="/result[@module = 'seo' and @method = 'seo']/data[@type = 'settings' and @action = 'view']">

		<xsl:variable name="domains-list" select="document('udata://core/getDomainsList')/udata/domains/domain" />
		<xsl:variable name="http_host" select="//option[@name='http_host']/value" />

		<script type="text/javascript"><![CDATA[
			var seo = new function(){
				var sort = 'word';
				var order = 'asc';
				var sortData = [];
				
				this.getData = function( host ) {
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
									"<form action=\"\" method=\"get\"><input type=\"hidden\" name=\"host\" value=\"" + host + "\"><div class=\"buttons\" style=\"padding-top:5px;\"><div class=\"button\" style=\"float:left;\"><input type=\"submit\" value=\"" +
									getLabel('js-panel-repeat') +
									"\" /><span class=\"l\" /><span class=\"r\" /></div></div></form></div>";
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
				seo.getData( document.getElementById('host').value );
			});
			
			]]></script>

		<div id="webo_in">
			<div class="panel">
				<div class="header">
					<span><xsl:text>&label-site-analysis;</xsl:text></span>
					<div class="l"></div>
					<div class="r"></div>
				</div>
				<div class="content">

					<form action="" method="get">
						<div class="field">
							<label for="host">
								<span class="label">
									<acronym>
										&label-site-address;
									</acronym>
								</span>
									<input type="text" name="host" value="{$http_host}" id="host" style="position: absolute;
	width: 80%; border-right:none; outline:none;" />
							</label>
							<select onchange="getElementById('host').value = this.options[this.selectedIndex].innerHTML; this.selectedIndex=0" id="domain-selector">
								<option selected="selected"></option>
								<xsl:apply-templates select="$domains-list" mode="domain-selector" />
							</select>
						</div>
						<div class="buttons" style="padding-top:5px;">
							<div class="button">
								<input type="submit" value="&label-button;" /><span class="l" /><span class="r" />
							</div>
						</div>
					</form>
				</div>
			</div>

			<div class="panel">
				<div class="header">
					<span><xsl:text>&label-results;</xsl:text></span>
					<div class="l"></div>
					<div class="r"></div>
				</div>
				<div class="content" id="result">
					<table class="tableContent">
						<thead>
							<tr>
								<th class="sort">
									<span id="word"><xsl:text>&label-query;</xsl:text></span>
								</th>
								<th class="sort">
									<span id="pos_y"><xsl:text>&label-yandex;</xsl:text></span>
								</th>
								<th class="sort">
									<span id="pos_g"><xsl:text>&label-google;</xsl:text></span>
								</th>
								<th class="sort" style="width:200px;">
									<span id="show_month" style="width:200px;"><xsl:text>&label-count;</xsl:text></span>
								</th>
								<th class="sort">
									<span id="wordstat"><xsl:text>&label-wordstat;</xsl:text></span>
								</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
					<div style="margin-top:20px; font-size:10px;">Основано на данных <a href="http://www.megaindex.ru?from=89477" target="_blank">megaindex.ru</a></div>
				</div>
			</div>

		</div>
    </xsl:template>

	<xsl:template match="domain" mode="domain-selector">
		<option value="{@id}"><xsl:value-of select="@host"/></option>
	</xsl:template>

	<xsl:template match="/result[@module = 'seo' and @method = 'links']/data[@type = 'settings' and @action = 'view']">
		<xsl:variable name="domains-list" select="document('udata://core/getDomainsList')/udata/domains/domain" />
		<xsl:variable name="http_host" select="//option[@name='http_host']/value" />

		<div id="webo_in">
			<div class="panel">
				<div class="header">
					<span><xsl:text>&label-site-analysis;</xsl:text></span>
					<div class="l"></div>
					<div class="r"></div>
				</div>
				<div class="content">

					<form action="" method="get">
						<div class="field">
							<label for="host">
								<span class="label">
									<acronym>
										&label-site-address;
									</acronym>
								</span>
									<input type="text" name="host" value="{$http_host}" id="host" style="position: absolute;
	width: 80%; border-right:none; outline:none;" />
							</label>
							<select onchange="getElementById('host').value = this.options[this.selectedIndex].innerHTML; this.selectedIndex=0" id="domain-selector">
								<option selected="selected"></option>
								<xsl:apply-templates select="$domains-list" mode="domain-selector" />
							</select>
						</div>
						<div class="buttons" style="padding-top:5px;">
							<div class="button">
								<input type="submit" value="&label-button;" /><span class="l" /><span class="r" />
							</div>
						</div>
					</form>
				</div>
			</div>

			<div class="panel">
				<div class="header">
					<span><xsl:text>&label-results;</xsl:text></span>
					<div class="l"></div>
					<div class="r"></div>
				</div>
				<div class="content" id="result">

					<xsl:choose>
						<xsl:when test="count(errors/error)">
							<xsl:apply-templates select="errors/error" mode="backlinks-error" />
						</xsl:when>
						<xsl:otherwise>
							<table class="tableContent">
								<thead>
									<tr>
										<th>
											<span><xsl:text>&label-link-from;</xsl:text></span>
										</th>
										<th>
											<span><xsl:text>&label-link-to;</xsl:text></span>
										</th>
										<th>
											<span><xsl:text>nofollow</xsl:text></span>
										</th>
										<th>
											<span><xsl:text>noindex</xsl:text></span>
										</th>
										<th>
											<span><xsl:text>&label-tic-from;</xsl:text></span>
										</th>
										<th>
											<span><xsl:text>&label-tic-to;</xsl:text></span>
										</th>
										<th>
											<span><xsl:text>&label-link-anchor;</xsl:text></span>
										</th>
									</tr>
								</thead>
								<tbody>
									<xsl:apply-templates select="links/link" mode="backlinks" />
								</tbody>
							</table>
						</xsl:otherwise>
					</xsl:choose>


					<div style="margin-top:20px; font-size:10px;">Основано на данных <a href="http://www.megaindex.ru?from=89477" target="_blank">megaindex.ru</a></div>
				</div>
			</div>

		</div>
	</xsl:template>

	<xsl:template select="error" mode="backlinks-error">
		<div><xsl:value-of select="."/></div>
	</xsl:template>

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


	<xsl:template match="/result[@module = 'seo' and @method = 'webmaster']/data[@type = 'settings' and @action = 'view']">
		<xsl:variable name="domains-list" select="document('udata://core/getDomainsList')/udata/domains/domain" />

		<div>
			<div class="panel">
				<div class="header">
					<span><xsl:text>&label-site-analysis;</xsl:text></span>
					<div class="l"></div>
					<div class="r"></div>
				</div>
				<div class="content">

					<div class="field">
						<label for="host">
							<span class="label">
								<acronym>
									&label-site-address;
								</acronym>
							</span>
						</label>
						<select id="domain-selector" class="ylinks">
							<option selected="selected" id="total">&option-webmaster-general;</option>
							<xsl:apply-templates select="$domains-list" mode="webmaster-domain-selector">
								<xsl:with-param name="hosts" select="hosts" />
							</xsl:apply-templates>
						</select>
					</div>

					<div class="buttons" style="padding-top:5px;">
						<div class="button">
							<input type="button" value="&label-button;" id="checkDomain" /><span class="l" /><span class="r" />
						</div>
					</div>
				</div>
			</div>

			<div class="panel">
				<div class="header">
					<span><xsl:text>&label-results;</xsl:text></span>
					<div class="l"></div>
					<div class="r"></div>
				</div>
				<div id="webmaster" class="content">

					<xsl:choose>
						<xsl:when test="count(errors/error)">
							<xsl:apply-templates select="errors/error" mode="backlinks-error" />
						</xsl:when>

						<xsl:otherwise>
							<table class="tableContent">
								<thead>
									<tr>
										<th>
											<span><xsl:text>&js-webmaster-label-sitename;</xsl:text></span>
										</th>
										<th>
											<span><xsl:text>&js-webmaster-label-crawling;</xsl:text></span>
										</th>
										<th>
											<span><xsl:text>&js-webmaster-label-virused;</xsl:text></span>
										</th>
										<th>
											<span><xsl:text>&js-webmaster-label-last-access;</xsl:text></span>
										</th>
										<th>
											<span><xsl:text>&js-webmaster-label-tcy;</xsl:text></span>
										</th>
										<th>
											<span><xsl:text>&js-webmaster-label-url-count;</xsl:text></span>
										</th>
										<th>
											<span><xsl:text>&js-webmaster-label-index-count;</xsl:text></span>
										</th>
										<th>
											<span><xsl:text>&js-webmaster-label-internal-links-count;</xsl:text></span>
										</th>
										<th>
											<span><xsl:text>&js-webmaster-label-links-count;</xsl:text></span>
										</th>
									</tr>
								</thead>
								<tbody>
									<xsl:apply-templates select="hosts/host" mode="draw-table"/>
								</tbody>
							</table>
						</xsl:otherwise>
					</xsl:choose>

					<script type="text/javascript">
						var domains = new Array();
						<xsl:apply-templates select="hosts/host" mode="data"/>
						<![CDATA[
						$(document).ready(function() {
							$('#domain-selector').children().each(function(k){
								if(this.id == 'total') {
									$(this).data({'table' : $('.tableContent').clone()});
								} else {
									$(this).data(domains[this.id]);
								}
							});

							$('#checkDomain').click(function(){
								var host = $('#domain-selector').children(':selected');
								if ($(host).attr('id') == 'total') {
									$('#webmaster').empty().append($(host).data().table)
								} else {
									handleHostLinks($(host).data());
								}
								return false;
							});

							$('a.yinfo').live('click', function(){
								var current = $('#stat_links span');
								var yandexUrl = $(this).attr('href');
								var replacement = $('<a />').attr({
									'href' : $(current).attr('href'),
									'class' : $(current).attr('class'),
									'id' : $(current).attr('id')
								}).text($(current).text());
								$(current).replaceWith(replacement);
								replacement = $('<span />').attr({
									'href' : $(this).attr('href'),
									'class' : $(this).attr('class'),
									'id' : $(this).attr('id')
								}).text($(this).text());
								$(this).replaceWith(replacement);
								getData(yandexUrl, this);
								return false;
							});

							$('.verify').live('click', function(){
								$.ajax({
									type: "GET",
									url: "/admin/seo/verify_site/.xml",
									data : {
										url : $(this).attr('href')
									},
									dataType: "xml",
									success: function(doc){
										alert('reload shit');
									}
								});
								return false;
							});
						});

						function getData(url, caller) {
							$.ajax({
								type: "GET",
								url: "/admin/seo/handle_url/.xml",
								data : {
									url : url
								},
								dataType: "xml",
								success: function(doc){
									if ($(doc).find('error').size()) {
										alert('Произошла ошибка. Попробуйте повторить запрос позже.');
										return false;
									}
									switch (caller.id) {
										case 'indexed' :
											handleLinksList(doc, 'index');
											break;

										case 'links' :
											handleLinksList(doc, 'links');
											break;

										case 'tops' :
											handleTops(doc);
											break;

										case 'excluded' :
											handleExcluded(doc);
											break;

										case 'verify' :
											handleVerify(doc, url);
											break;
									}
								}
							});
						};

						function handleHostLinks(data) {
							if (data.addLink) {
								var error = $('<p />').html(getLabel('js-webmaster-label-addhost')).append('<br /><br />');
								var errorLink = $('<a href="#">' + getLabel('js-webmaster-link-addhost') + '</a>');
								$(errorLink).click(function() {
									addHost(false, data.addLink, data.name);
									return false;
								});
								$(error).append(errorLink);
								showError(error);
								return false;
							}
							switch (data.verification.state) {
								case 'VERIFIED' :
									var linkBlock = $('<div id="stat_links" />').prependTo($('#webmaster').empty());
									for (type in data.links) {
										$(linkBlock).append('<a class="yinfo" id="' + type + '" href="' + data.links[type] + '">' + getLabel('js-webmaster-link-' + type) + '</a>');
									}
									$(linkBlock).children(':first').click();
									$('#webmaster').append('<table class="tableContent" />');
									break;
								case 'WAITING' :
								case 'IN_PROGRESS' :
									setTimeout(function() {
										refreshHost(false, data.link);
									}, 1000);
									var error = $('<p />').html(getLabel('js-webmaster-verification-state-' + data.verification.state)).append('<br /><br />');
									$(error).append(errorLink);
									showError(error);
									break;
								default :
									var error = $('<p />').html(getLabel('js-webmaster-verification-state-' + data.verification.state)).append('<br /><br />')
										.append(getLabel('js-webmaster-label-verfyhost')).append('<br /><br />')
									var errorLink = $('<a href="#">' + getLabel('js-webmaster-link-verifyhost') + '</a>');
									$(errorLink).click(function() {
										verifyHost(false, data.verification.link, data.link);
										return false;
									});
									$(error).append(errorLink);
									showError(error);
							}
						};

						function handleLinksList(data, type) {
							var table = $('.tableContent').empty();
							var total = data.getElementsByTagName(type + '-count')[0].textContent;
							var items = data.getElementsByTagName('url');
							var header = getLabel('js-webmaster-' + type + '-label');
							$(table).append('<tr><th style="text-align: left; padding-left: 14px;">' + header + '</th></tr>');
							for (var i = 0; i < items.length; i++) {
								var item = items[i];
								$(table).append('<tr><td><a class="yinfo" href="' + item.textContent + '">' + item.textContent + '</a></td></tr>');
							}
							if (!items.length) {
								$(table).append('<tr><td>' + getLabel('js-webmaster-' + type + '-nothing-label') + '</td></tr>');
							}
							$(table).append('<tr><td>' + getLabel('js-webmaster-' + type + '-total-label') + total + '</td></tr>');
						};

						function handleTops(data) {
							var table = $('.tableContent').empty();
							var tbody = $('<tbody />').appendTo(table);
							var shows = $(data).find('top-shows').children();
							var clicks = $(data).find('top-clicks').children();
							var queries = new Array();
							var row = $('<tr />');
							$('<th />').appendTo(row).text(getLabel('js-webmaster-label-tops-query'));
							$('<th />').appendTo(row).text(getLabel('js-webmaster-label-tops-shows'));
							$('<th />').appendTo(row).text(getLabel('js-webmaster-label-tops-clicks'));
							$('<th />').appendTo(row).text(getLabel('js-webmaster-label-tops-position'));
							$('<thead />').append(row).prependTo(table);
							shows.each(function(k) {
								var query = $(this).children('query').text();
								var row = $('<tr />').attr('id', 'shows' + k);
								$('<td />').appendTo(row).text(query);
								$('<td />').appendTo(row).text($(this).children('count').text());
								$('<td />').appendTo(row).text(0).addClass('clicks');
								$('<td />').appendTo(row).text($(this).children('position').text());
								$(tbody).append(row);
								queries[$(this).children('query').text()] = 'shows' + k;
							});
							clicks.each(function(k) {
								var query = $(this).children('query').text();
								if (queries[query]) {
									var cell = $('#' + queries[query] + ' .clicks');
									$(cell).text($(this).children('count').text());
								} else {
									var row = $('<tr />').attr('id', 'clicks' + k);
									$('<td />').appendTo(row).text(query);
									$('<td />').appendTo(row).text(0);
									$('<td />').appendTo(row).text($(this).children('count').text());
									$('<td />').appendTo(row).text($(this).children('position').text());
									$(tbody).append(row);
								}
							});
						};

						function handleExcluded(data) {
							var table = $('.tableContent').empty();
							var tbody = $('<tbody />').appendTo(table);
							var errors = $(data).find('url-errors').children();
							var row = $('<tr />');
							$('<th />').appendTo(row).text(getLabel('js-webmaster-excluded-code-label'));
							$('<th />').appendTo(row).text(getLabel('js-webmaster-excluded-count-label'));
							$('<th />').appendTo(row).text(getLabel('js-webmaster-excluded-severity-label'));
							$('<thead />').append(row).prependTo(table);
							errors.each(function(k) {
								var row = $('<tr />').attr('id', 'shows' + k);
								$('<td />').appendTo(row).text(getLabel('js-webmaster-excluded-code-' + $(this).attr('code')));
								$('<td />').appendTo(row).text($(this).children('count').text());
								$('<td />').appendTo(row).text(getLabel('js-webmaster-excluded-severity-' + $(this).children('severity').text()));
								$(tbody).append(row);
							});
							$(tbody).append('<tr><td colspan="3">' + getLabel('js-webmaster-excluded-total-label') + $(data).find('url-errors').attr('count') + '</td></tr>');
						};

						function verifyHost(handler, verifyLink, hostLink){
							$.ajax({
								type: "GET",
								url: "/admin/seo/verify_site/.json",
								data : {
									verifyLink : verifyLink,
									hostLink : hostLink
								},
								dataType: "json",
								success: function(doc){
									if (doc.data.error) {
										alert('Произошла ошибка. Попробуйте повторить запрос позже.');
										return false;
									}
									handleHost(doc.data.hosts.host, handler);
								}
							});
							return false;
						};

						function addHost(handler, addLink, hostName){
							$.ajax({
								type: "GET",
								url: "/admin/seo/add_site/.json",
								data : {
									addLink : addLink,
									hostName : hostName
								},
								dataType: "json",
								success: function(doc){
									if (doc.data.error) {
										alert(doc.data.error);
										return false;
									}
									handleHost(doc.data.hosts.host, handler);
								}
							});
							return false;
						};

						function refreshHost(handler, hostLink){
							$.ajax({
								type: "GET",
								url: "/admin/seo/refresh_site/.json",
								data : {
									hostLink : hostLink
								},
								dataType: "json",
								success: function(doc){
									if (doc.data.error) {
										alert('Произошла ошибка. Попробуйте повторить запрос позже.');
										return false;
									}
									handleHost(doc.data.hosts.host, handler);
								}
							});
							return false;
						};

						function handleHost(hostData, container) {
							var hostId = 'host_' + hostData.id;
							$('#' + hostId).removeData().data(hostData);
							if (!container) {
								$('#checkDomain').click();
								var row = $($('#domain-selector #total').data().table).find('#row_' + hostId).empty()
							} else {
								var row = $('#row_' + hostId).empty();
							}
							$('<td />').appendTo(row).text(hostData.name);
							switch (hostData.verification.state) {
								case 'VERIFIED' :
									$('<td />').appendTo(row).text(hostData.crawling.state);
									$('<td />').appendTo(row).text(hostData.virused);
									$('<td />').appendTo(row).text(hostData['last-access']);
									$('<td />').appendTo(row).text(hostData.tcy);
									$('<td />').appendTo(row).text(hostData['url-count']);
									$('<td />').appendTo(row).text(hostData['index-count']);
									$('<td />').appendTo(row).text(hostData['internal-links-count']);
									$('<td />').appendTo(row).text(hostData['links-count']);
									break;
								case 'WAITING' :
								case 'IN_PROGRESS' :
									$('<td collspan="8"/>').appendTo(row).text(getLabel('js-webmaster-verification-state-' + hostData.verification.state));
									setTimeout(function() {
										refreshHost(container, hostData.link);
									}, 1000);
									break;
								default :
									var verify = $('<a href="#">' + getLabel('js-webmaster-link-verifyhost') + '</a>');
									$(verify).click(function() {
										verifyHost(false, hostData.verification.link, hostData.link);
										return false;
									});
									$('<td collspan="8"/>').appendTo(row).append(getLabel('js-webmaster-verification-state-' + hostData.verification.state) + ' ' + verify);
								}
						};

						function showError(error) {
							var errorBlock = $('<div id="errorList" />')
								.append('<p class="error" style="margin-top:0px;"><strong>' + getLabel('js-webmaster-errors-header') + '</strong></p>')
								.append('<ol class="error" />');
							$('<li />').appendTo($(errorBlock).children('ol.error')).append(error);
							$('#webmaster').empty().append(errorBlock);
						};

					]]></script>

					<div style="margin-top:20px; font-size:10px;">&footer-webmaster-text;<a href="http://webmaster.yandex.ru/" target="_blank">&footer-webmaster-link;</a></div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="hosts/host" mode="draw-table">
		<tr id="row_host_{@id}">
			<td>
				<xsl:value-of select="name" />
			</td>
			<xsl:choose>
				<xsl:when test="verification/link">
					<td colspan="8">
						<xsl:variable name="verifyLink" select="verification/link" />
						<a onclick="return verifyHost(this, '{$verifyLink}', '{@link}');" href="#">&js-webmaster-link-verifyhost;</a>
					</td>
				</xsl:when>
				<xsl:when test="addLink">
					<td colspan="8">
						<a onclick="return addHost(this, '{addLink}', '{name}');" href="#">&js-webmaster-link-addhost;</a>
					</td>
				</xsl:when>
				<xsl:otherwise>
					<td>
						<xsl:value-of select="crawling/state" />
					</td>
					<td>
						<xsl:value-of select="virused" />
					</td>
					<td>
						<xsl:value-of select="last-access" />
					</td>
					<td>
						<xsl:value-of select="tcy" />
					</td>
					<td>
						<xsl:value-of select="url-count" />
					</td>
					<td>
						<xsl:value-of select="index-count" />
					</td>
					<td>
						<xsl:value-of select="internal-links-count" />
					</td>
					<td>
						<xsl:value-of select="links-count" />
					</td>
				</xsl:otherwise>
			</xsl:choose>
		</tr>
	</xsl:template>

	<xsl:template match="hosts/host" mode="data">
		<xsl:text>domains['host_</xsl:text><xsl:value-of select="@id"/><xsl:text>'] = {</xsl:text>
			'name' : <xsl:text>'</xsl:text><xsl:value-of select="name"/><xsl:text>'</xsl:text>,
			'link' : <xsl:text>'</xsl:text><xsl:value-of select="@link"/><xsl:text>'</xsl:text>,
			'links' : {
				<xsl:for-each select="links/*">
					<xsl:text>'</xsl:text><xsl:value-of select="name()"/><xsl:text>'</xsl:text>
					<xsl:text> : </xsl:text>
					<xsl:text>'</xsl:text><xsl:value-of select="."/><xsl:text>'</xsl:text>
					<xsl:if test="position() != last()">
						<xsl:text>,</xsl:text>
					</xsl:if>
				</xsl:for-each>
				},
			'addLink' : <xsl:text>'</xsl:text><xsl:value-of select="addLink"/><xsl:text>',</xsl:text>
			'verification' : {
				'state' : <xsl:text>'</xsl:text><xsl:value-of select="verification/state"/><xsl:text>',</xsl:text>
				'link' : <xsl:text>'</xsl:text><xsl:value-of select="verification/link"/><xsl:text>'</xsl:text>
				}
			}
	</xsl:template>

	<xsl:template match="domain" mode="webmaster-domain-selector">
		<xsl:param name="hosts" />
		<xsl:variable name="hostname" select="./@host" />
		<xsl:choose>
			<xsl:when test="$hosts/host[contains(name, $hostname)]">
				<option class="req" value="{$hosts/host[contains(name, $hostname)]/@href}" id="host_{$hosts/host[contains(name, $hostname)]/@id}"><xsl:value-of select="$hostname" /></option>
			</xsl:when>
			<xsl:otherwise>
				<option class="add"><xsl:value-of select="$hostname" /></option>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

</xsl:stylesheet>