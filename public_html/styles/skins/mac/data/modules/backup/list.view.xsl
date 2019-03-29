<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="/result[@method = 'snapshots']/data[@type = 'list' and @action = 'view']">
		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
		<xsl:apply-templates select="document('udata://backup/backup_panel_all')/udata" />
	</xsl:template>

	<xsl:template match="udata[count(file) = 0]" mode="snapshots">
		<tr>
			<td colspan="3">
				<p align="center">
					<xsl:text>&label-snapshots-empty;</xsl:text>
				</p>
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="file" mode="snapshots">
		<xsl:variable name="date" select="document(concat('udata://system/convertDate/', @create-time, '/Y-m-d%20H:m:s'))" />

		<tr>
			<td>
				<a href="{$lang-prefix}/admin/backup/downloadSnapshot/?filename={@name}" title="&label-download;" style="margin-right:20px;">
					<strong><xsl:value-of select="@name"/></strong>
				</a>
				<xsl:text>&label-create-time;: </xsl:text>
				<xsl:value-of select="$date/udata"/>
			</td>

			<td style="padding:0 14px;">
				<a href="" class="restore" onclick="restoreSnapshot('{@name}'); return false;">
					<span><xsl:text>&label-snapshots-unpack-do;</xsl:text></span>
				</a>
			</td>

			<td>
				<a href="{$lang-prefix}/admin/backup/deleteSnapshot/?filename={@name}" class="delete unrestorable">
					<span><xsl:text>&label-delete;</xsl:text></span>
				</a>
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="udata[@module = 'backup' and @method = 'backup_panel_all']">
		<div class="panel properties-group">
			<div class="header">
				<span class="c">
					<xsl:text>&backup-changelog;</xsl:text>
				</span>
				<div class="l"></div>
				<div class="r"></div>
			</div>

			<div class="content">
				<table class="tableContent">
					<thead>
						<tr>
							<th class="left">
								<xsl:text>&backup-change-page;</xsl:text>
							</th>
							<th>
							</th>
							<th>
								<xsl:text>&backup-change-time;</xsl:text>
							</th>
							<th>
								<xsl:text>&backup-change-author;</xsl:text>
							</th>
							<th>
								<xsl:text>&backup-change-rollback;</xsl:text>
							</th>
						</tr>
					</thead>
					<tbody>
						<xsl:choose>
							<xsl:when test="count(revision) &gt; 0">
								<xsl:apply-templates select="revision" />
							</xsl:when>

							<xsl:otherwise>
								<tr>
									<td colspan="4" align="center">
										<xsl:text>&backup-no-changes-found;</xsl:text>
									</td>
								</tr>
							</xsl:otherwise>
						</xsl:choose>
					</tbody>
				</table>
				<br />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="revision">
		<xsl:variable name="editor-info" select="document(concat('uobject://',@user-id))/udata" />
		<tr>
			<td>
				<a href="{page/@link}"><xsl:value-of select="page/@name" /></a>
			</td>
			<td>
				<a href="{page/@edit-link}" title="Редактировать страницу"><img src="/images/cms/admin/mac/tree/ico_edit.png"/></a>
			</td>

			<td style="text-align: center;">
				<xsl:value-of select="document(concat('udata://system/convertDate/',@changetime,'/Y-m-d%20%7C%20H:i'))/udata" />
			</td>

			<td style="text-align: center;">
				<xsl:value-of select="$editor-info//property[@name = 'lname']/value" />
				<xsl:text>&nbsp;</xsl:text>
				<xsl:value-of select="$editor-info//property[@name = 'fname']/value" />
			</td>

			<td class="center">
				<xsl:apply-templates select="." mode="button" />
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="revision" mode="button">
		<div class="button">
			<input name="backup" type="button"
				   value="&backup-change-rollback;"
				   onclick="window.location = '{link}?referer=' + window.location.pathname;"
					/>
			<span class="l" />
			<span class="r" />
		</div>
	</xsl:template>

	<xsl:template match="revision[@is-void = 1]" mode="button">
		<xsl:text>&backup-entry-is-void;</xsl:text>
	</xsl:template>

	<xsl:template match="revision[@active = 'active']" mode="button" />

</xsl:stylesheet>