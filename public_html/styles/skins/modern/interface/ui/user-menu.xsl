<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:template name="user-menu">
        <xsl:variable name="user-login" select="document(concat('uobject://', $current-user-id, '.login'))//value/." />

        <div class="user menu">
            <div class="selected">
                <xsl:value-of select="$user-login" />
            </div>
            <ul>
                <li><a href="/admin/users/edit/{$current-user-id}/">&profile;</a></li>
                <li><a href="/admin/users/logout/?redirect_url=/admin">&switch-user;</a></li>
                <li><a id="exit" href="/admin/users/logout/">&js-panel-exit;</a></li>
            </ul>
        </div>
    </xsl:template>
</xsl:stylesheet>