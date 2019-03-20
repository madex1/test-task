<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:php="http://php.net/xsl"
				extension-element-prefixes="php"
				xmlns:umi="http://www.w3.org/1999/xhtml">

	<xsl:template match="/result[@method = 'serviceWorkingTime']/data[@type = 'settings' and @action = 'modify']">
		<script type="text/javascript" src="/styles/skins/modern/data/modules/appointment/time.ranges.selections.js"/>
		<div class="tabs-content module {$module}-module">
			<div class="section selected">
				<form class="form_modify"  method="post" action="do/" enctype="multipart/form-data">
					<div class="panel-settings">
						<div class="title">
							<h3>
								<xsl:text>&label-service-work-times;</xsl:text>
							</h3>
						</div>
						<div class="content">
							<div class="layout">
								<div class="column">
									<xsl:variable name="work.times" select="document('udata://appointment/getScheduleWorkTimes/')/udata" />
									<xsl:apply-templates select="./group[@name = 'appointment-working-time']/option" mode="work.times">
										<xsl:with-param name="work.times" select="$work.times" />
									</xsl:apply-templates>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<xsl:call-template name="std-form-buttons-settings"/>
					</div>
				</form>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="group/option" mode="work.times">
		<xsl:param name="work.times" />
		<div class="row work_time_range" data-from="&label-from;" data-to="&label-to;">
			<div class="col-md-1 work_time_label">
				<div class="title-edit">
					<acronym>
						<xsl:value-of select="./value/name" />
					</acronym>
				</div>
			</div>
			<div class="col-md-1">
				<select class="work_time_from" name="{concat('data[schedules][', ./value/number, '][from]')}">
					<xsl:apply-templates select="$work.times" >
						<xsl:with-param name="selected">
							<xsl:value-of select="./value/from" />
						</xsl:with-param>
						<xsl:with-param name="default">
							<xsl:text>&label-from;</xsl:text>
						</xsl:with-param>
					</xsl:apply-templates>
				</select>
			</div>
			<div class="time_range_separator">
				<xsl:text>&nbsp; – &nbsp;</xsl:text>
			</div>
			<div class="col-md-1">
				<select class="work_time_to" name="{concat('data[schedules][', ./value/number, '][to]')}">
					<xsl:apply-templates select="$work.times" >
						<xsl:with-param name="selected">
							<xsl:value-of select="./value/to" />
						</xsl:with-param>
						<xsl:with-param name="default">
							<xsl:text>&label-to;</xsl:text>
						</xsl:with-param>
					</xsl:apply-templates>
				</select>
			</div>
			<div class="time_range_separator">
				<div class="work_time_clear">×</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="udata[@module = 'appointment' and @method = 'getScheduleWorkTimes']">
		<xsl:param name="selected"/>
		<xsl:param name="default"/>
		<xsl:if test="$selected = ''">
			<option value="-1">
				<xsl:value-of select="$default"/>
			</option>
		</xsl:if>
		<xsl:for-each select="items/item">
			<option value="{./@value}" data-number="{./@number}">
				<xsl:if test="$selected = ./@value">
					<xsl:attribute name="selected">
						<xsl:text>selected</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="./@value"/>
			</option>
		</xsl:for-each>
	</xsl:template>

</xsl:stylesheet>
