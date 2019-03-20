<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/seo">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:param name="end_date" />
	<xsl:param name="start_date" />
	<xsl:param name="filter" />


	<xsl:template match="/result[@module = 'events' and @method = 'feed']/data[@type = 'settings' and @action = 'view']">
		<script type="text/javascript" language="javascript" src="/styles/skins/mac/design/js/events.js"/>

		<script type="text/javascript"><![CDATA[
			jQuery(document).ready(function() {
				var total =]]><xsl:value-of select="@total" /><![CDATA[;
				var limit =]]><xsl:value-of select="@limit" /><![CDATA[;
				var offset =]]><xsl:value-of select="@offset" /><![CDATA[;
				var filter =']]><xsl:value-of select="$filter" /><![CDATA[';

				updatePaging(total, limit, offset, filter);

			});
			]]>
		</script>

		<div class="dashboard">

			<div class="events_settings">
				<div class="settings_header">&dashboard-filters;:</div>
				<xsl:apply-templates select="document('udata://events/getUserSettings/')/udata" mode="settings" />
			</div>
			<div class="tableDashboard">
				<div class="events_header">
					<input type="checkbox" id="select_all" onclick="javascript:selectAllEvents(this);"/> <label for="select_all">&js-dashboard-select-all;</label>
					<div class="buttons">
						<div>
							<input type="button" id="mark_unread" value="&js-dashboard-mark-unread;" disabled="disabled"/>
							<span class="l"></span>
							<span class="r"></span>
						</div>
						<div>
							<input type="button" id="mark_read" value="&js-dashboard-mark-read;" disabled="disabled"/>
							<span class="l"></span>
							<span class="r"></span>
						</div>
					</div>
				</div>
				<table>
					<tbody>
						<xsl:choose>
							<xsl:when test="@total != 0">
								<xsl:apply-templates select="events/event" mode="feedlist" />
							</xsl:when>
							<xsl:otherwise>
								<tr><td style="text-align:center;">&js-dashboard-nothing-new;</td></tr>
							</xsl:otherwise>
						</xsl:choose>
					</tbody>
				</table>
				<div class="cont_pages_bar">
					<div class="pages-bar" />
				</div>
			</div>
		</div>
	
	</xsl:template>
	
	<xsl:template match="/result[@module = 'events' and @method = 'last']/data[@type = 'settings' and @action = 'view']">
		<xsl:attribute name="class">content dash</xsl:attribute>

		<script type="text/javascript" language="javascript" src="/styles/skins/mac/design/js/events.js"/>

		<div class="dashboard">
			<xsl:choose>
				<xsl:when test="count(new-events/new-event)">
					<xsl:apply-templates select="new-events/new-event" mode="new-events" />
				</xsl:when>
				<xsl:otherwise>
					<div class="nothing">&js-dashboard-nothing-new-events;</div>
				</xsl:otherwise>
			</xsl:choose>
		</div>

	</xsl:template>

	<xsl:template match="new-event" mode="new-events">
		<div class="new_events" id="{@type-id}">
			<div class="events_header" >
				<img src="{@img}" alt="{@name}" />
				<span class="amount"><xsl:value-of select="@count" /></span>
				<div><xsl:value-of select="@name" /></div>
			</div>
			<table>
				<xsl:apply-templates select="events/event" mode="new-feedlist" />
			</table>
			<div class="events_link">
				<a href="?filter={@type-id}" onclick="javascript:filterType('{@type-id}'); return false;">&dashboard-link-title;</a>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="event" mode="new-feedlist">
		<tr>
			<td class="date"><xsl:value-of select="@date" /></td>
			<td><xsl:value-of select="." disable-output-escaping="yes" /></td>
		</tr>
	</xsl:template>

	<xsl:template match="event" mode="feedlist">
		<tr>
			<xsl:attribute name="class">
				<xsl:choose>
					<xsl:when test="@read = 1">
						<xsl:text>read</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>unread</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<td>
				<input type="checkbox" name="events[]" value="{@id}" onclick="javascript:changeReadEvents(this);">
					<xsl:attribute name="class">
						<xsl:choose>
							<xsl:when test="@read = 1">
								<xsl:text>read</xsl:text>
							</xsl:when>
							<xsl:otherwise>
								<xsl:text>unread</xsl:text>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:attribute>
				</input>
			</td>
			<td><div style="white-space:nowrap;"><xsl:value-of select="@date" /></div></td>
			<td><xsl:value-of select="." disable-output-escaping="yes" /></td>
		</tr>
	</xsl:template>
	
	<xsl:template match="udata" mode="settings">
		<div style="padding: 16px 10px 10px 14px;">
			&dashboard-no-settings;
		</div>
	</xsl:template>
	
	<xsl:template match="udata[count(type) > 0]" mode="settings">
		<form method="post" action="/admin/events/saveSettings/" id="dashboard_settings" onsubmit="javascript:saveSettings(); return false;">
		
			<div class="datePicker">
				<label for="start_date">
					<acronym>&dashboard-from;</acronym>
					<input id="start_date" type="text" value="{$start_date}" name="start_date" />
				</label>
			</div>
			<div class="datePicker">
				<label for="end_date">
					<acronym>&dashboard-to;</acronym>
					<input id="end_date" type="text" value="{$end_date}" name="end_date" />
				</label>
			</div>

		
			<fieldset>			
				<xsl:apply-templates select="document('udata://events/getUserSettings/')/udata/type" mode="settings" />
			</fieldset>
			<div class="buttons">
				<div>
					<input type="submit" value="&dashboard-filter;"/>
					<span class="l"></span>
					<span class="r"></span>
				</div>
			</div>
		</form>
	</xsl:template>

	<xsl:template match="type" mode="settings">
		<div>
			<input name="settings[]" value="{@id}" id="{@id}" type="checkbox" title="{@name}">
				<xsl:if test="@checked = 1">
					<xsl:attribute name="checked">
						checked
					</xsl:attribute>
				</xsl:if>
			</input>
			<label for="{@id}"><xsl:value-of select="@name" /></label>
		</div>
	</xsl:template>
	


</xsl:stylesheet>
