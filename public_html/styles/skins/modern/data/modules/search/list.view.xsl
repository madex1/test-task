<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="/result[@method = 'search_replace']/data[@type = 'list' and @action = 'view']">
		<div class="tabs-content module">
			<div class="tabs-content module">
				<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
					<xsl:call-template name="entities.help.button" />
				</div>	
				<div class="layout">
					<div class="column">
						<form>
							<div class="panel-settings">
								<div class="title"></div>
								<div class="content">
									<table class="btable btable-bordered btable-striped">
									<tbody>
										<tr>
											<td width="250">
												<div class="title-edit">&find;: </div>
											</td>
											<td width="250">
												<input class ="fcStringInput default" type="text" name="searchString" id="searchString" value="{@searchString}" required="required"/>
											</td>
										</tr>
										<tr>
											<td width="250">
												<div class="title-edit">&replace-to;: </div>
											</td>
											<td width="250">
												<input class ="fcStringInput default" type="text" name="replaceString" id="replaceString" value="{@replaceString}" required="required"/>
											</td>
										</tr>
										<tr>
											<td width="250">
												<div class="title-edit">&where-to-search;:</div>
											</td>
											<td width="250">
												<div title="&snr-content-pages;">
													<div class="checkbox">
														<input type="checkbox" class="row_selector" name="contentType[content]" id="contentTypeContent">
															<xsl:if test="contentTypes/contentType = 'content'">
																<xsl:attribute name="checked" />
															</xsl:if>
														</input>
													</div>
													<label for="contentTypeContent" class="name_col">&snr-content-pages;</label>
												</div>
												<div title="&snr-blog;">
													<div class="checkbox">
														<input type="checkbox" class="row_selector" name="contentType[blog]" id="contentTypeBlog">
															<xsl:if test="contentTypes/contentType = 'blog'">
																<xsl:attribute name="checked" />
															</xsl:if>
														</input>
													</div>
													<label for="contentTypeBlog" class="name_col">&snr-blog;</label>
												</div>
												<div title="&snr-news;">
													<div class="checkbox">
														<input type="checkbox" class="row_selector" name="contentType[news]" id="contentTypeNews">
															<xsl:if test="contentTypes/contentType = 'news'">
																<xsl:attribute name="checked" />
															</xsl:if>
														</input>
													</div>
													<label for="contentTypeNews" class="name_col">&snr-news;</label>
												</div>
												<div title="&snr-catalog;">
													<div class="checkbox">
														<input type="checkbox" class="row_selector" name="contentType[catalog]" id="contentTypeCatalog">
															<xsl:if test="contentTypes/contentType = 'catalog'">
																<xsl:attribute name="checked" />
															</xsl:if>
														</input>
													</div>
													<label for="contentTypeCatalog" class="name_col">&snr-catalog;</label>
												</div>
												<div title="&snr-comments;">
													<div class="checkbox">
														<input type="checkbox" class="row_selector" name="contentType[comments]" id="contentTypeComments">
															<xsl:if test="contentTypes/contentType = 'comments'">
																<xsl:attribute name="checked" />
															</xsl:if>
														</input>
													</div>
													<label for="contentTypeComments" class="name_col">&snr-comments;</label>
												</div>
												<div title="&snr-faq;">
													<div class="checkbox">
														<input type="checkbox" class="row_selector" name="contentType[faq]" id="contentTypeFaq">
															<xsl:if test="contentTypes/contentType = 'faq'">
																<xsl:attribute name="checked" />
															</xsl:if>
														</input>
													</div>
													<label for="contentTypeFaq" class="name_col">&snr-faq;</label>
												</div>
												<div title="&snr-forum;">
													<div class="checkbox">
														<input type="checkbox" class="row_selector" name="contentType[forum]" id="contentTypeForum">
															<xsl:if test="contentTypes/contentType = 'forum'">
																<xsl:attribute name="checked" />
															</xsl:if>
														</input>
													</div>
													<label for="contentTypeForum" class="name_col">&snr-forum;</label>
												</div>
												<div title="&snr-photoalbum;">
													<div class="checkbox">
														<input type="checkbox" class="row_selector" name="contentType[photoalbum]" id="contentTypePhotoalbum">
															<xsl:if test="contentTypes/contentType = 'photoalbum'">
																<xsl:attribute name="checked" />
															</xsl:if>
														</input>
													</div>
													<label for="contentTypePhotoalbum" class="name_col">&snr-photoalbum;</label>
												</div>
												<div title="&snr-all;">
													<div class="checkbox">
														<input type="checkbox" class="row_selector" name="contentType[all]" id="contentTypeAll">
															<xsl:if test="contentTypes/contentType = 'all'">
																<xsl:attribute name="checked" />
															</xsl:if>
														</input>
													</div>
													<label for="contentTypeAll" class="name_col">&snr-all;</label>
												</div>
											</td>
										</tr>
										<xsl:if test="count(domains) > 1">
											<tr>
												<td width="250">
													<div class="title-edit">&domain;: </div>
												</td>
												<td width="250">
													<select name="domain_id">
														<option value="0">&all-domains;</option>
														<xsl:apply-templates select="domains" />
													</select>
												</td>
											</tr>
										</xsl:if>
										</tbody>
									</table>
									<div class="pull-right">
										<input type="hidden" name="action" value="search" />
										<input class="btn color-blue" type="submit" value="&search;"/>
									</div>
								</div>
							</div>
						</form>
					</div>
					<div class="column">
						<xsl:call-template name="entities.help.content" />
					</div>
				</div>
			</div>
		</div>
		<xsl:apply-templates select="." mode="postAction"/>
	</xsl:template>

	<xsl:template match="data[@postAction = 'search']" mode="postAction">
		<div class="">
			<p> &snr-founded-matches;: <strong><xsl:value-of select="@totalCount" /></strong> </p>
			<br/>
		</div>
		<xsl:if test="count(page) > 0">
			<form method="post">
				<table class="btable btable-bordered btable-striped" style="font-size:12px;">
					<thead>
						<tr>
							<th>
								<xsl:text>ID</xsl:text>
							</th>
							<th>
								<xsl:text>&domain;</xsl:text>
							</th>
							<th>
								<xsl:text>&snr-field;</xsl:text>
							</th>
							<th>
								<xsl:text>&snr-page-name;</xsl:text>
							</th>
							<th>
								<xsl:text>&snr-content;</xsl:text>
							</th>
							<th>
								<xsl:text>&snr-edit;</xsl:text>
							</th>
							<th>
								&snr-in-text; <input type="checkbox" id="select_all_text" class="check"/>
							</th>
							<th>
								&snr-in-link; <input type="checkbox" id="select_all_link" class="check"/>
							</th>
						</tr>
					</thead>
					<tbody>
						<xsl:apply-templates select="page" mode="list-modify"/>
					</tbody>
				</table>
				<div class="pages-bar">
					<span class="pagesLabel">&snr-pages;:</span>
					<xsl:apply-templates select="pagination/pageNum" />
				</div>
				<div class="pull-right" style="padding-left:20px;">
					<input type="hidden" name="action" value="replace" />
					<input type="hidden" name="searchString" value="{@searchString}" />
					<input type="hidden" name="replaceString" value="{@replaceString}" />
					<input type="hidden" name="searchType" value="{@searchType}" />
					<input type="submit" value="&replace;" class="btn color-blue" />
				</div>
				<div class="pull-right">
					<a href="/admin/search/search_replace/"  class="btn color-blue">&cancel;</a>
				</div>
				<script type="text/javascript"><![CDATA[
 						$('#select_all_text').change(function() {
 							var checkboxes = $('.checkbox_text_only');
 							if($(this).is(':checked')) {
 								checkboxes.attr('checked', true);
 							} else {
 								checkboxes.attr('checked', false);
 							}
 						});
 						$('#select_all_link').change(function() {
 							var checkboxes = $('.checkbox_link_only');
 							if($(this).is(':checked')) {
 								checkboxes.attr('checked', true);
 							} else {
 								checkboxes.attr('checked', false);
 							}
 						});
			]]></script>
			</form>
		</xsl:if>
	</xsl:template>

	<xsl:template match="domains">
		<option value="{@id}"><xsl:value-of select="@host"/></option>
	</xsl:template>

	<xsl:template match="data[@postAction = 'replace']" mode="postAction">
		&snr-replace-count; <xsl:value-of select="count(reports)" />
		<ul>
			<xsl:apply-templates select="reports" />
		</ul>
	</xsl:template>

	<xsl:template match="reports">
		<li>
			<xsl:value-of select="report" />
		</li>
	</xsl:template>

	<xsl:template match="page" mode="list-modify">
		<tr>
			<td>
				<xsl:value-of select="id"/>
			</td>
			<td>
				<xsl:value-of select="host"/>
			</td>
			<td>
				<xsl:value-of select="title"/> (<em><xsl:value-of select="name" /></em>)
			</td>
			<td>
				<xsl:value-of select="page_name"/>
			</td>
			<td>
				<xsl:value-of select="link/content" disable-output-escaping="yes"/>
				<xsl:value-of select="text/content" disable-output-escaping="yes"/>
			</td>
			<td align="center">
				<a target="_blank">
					<xsl:attribute name="href">
						<xsl:value-of select="document(concat('udata://system/getEditLink/', id))/udata"/>
					</xsl:attribute>
					<img src="/images/cms/admin/mac/ico_edit.gif" title="&label-edit;" alt="&label-edit;" />
				</a>
			</td>
			<td align="center">
				<xsl:if test="text/content != ''">
					<input type="checkbox" name="replaceIds[{id}][text][{name}]" value="1" class="check checkbox_text_only"/>
				</xsl:if>
			</td>
			<td align="center">
				<xsl:if test="link/content != ''">
					<input type="checkbox" name="replaceIds[{id}][link][{name}]" value="1" class="check checkbox_link_only"/>
				</xsl:if>
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="pagination/pageNum">
		<xsl:choose>
			<xsl:when test="@current = 1">
				<span style="padding: 0 3px;"><xsl:value-of select="pageNum" /></span>
			</xsl:when>
			<xsl:otherwise>
				<span style="padding: 0 3px;">
					<a href="{@link}">
						<xsl:value-of select="pageNum" />
					</a>
				</span>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
</xsl:stylesheet>