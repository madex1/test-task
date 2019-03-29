<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:include href="settings-sphinx.xsl" />

    <xsl:template match="data[@type = 'settings' and @action = 'view']">
        <xsl:apply-templates select="group" mode="settings.modify"/>
    </xsl:template>

    <xsl:template match="group[@name = 'info']" mode="settings.modify">
        <script type="text/javascript">
            function rebuildSearchIndex() {
            var partialQuery = function (lastId) {
            if(window.session) {
            window.session.startAutoActions();
            }

            $.get('/admin/search/partialReindex.xml?lastId='+lastId, null, function (data) {

            var current = $('index-status', data).attr('current');
            var total = $('index-status', data).attr('total');
            var lastId = $('index-status', data).attr('lastId');

            if(current != total) {
            $('#search-reindex-log').html(getLabel('js-search-reindex-pages-updated') + current);
            partialQuery(lastId);
            } else {
            window.location.reload();
            }
            });
            };

            partialQuery(0);

            openDialog({
            'title': getLabel('js-search-reindex-header'),
            'text': getLabel('js-search-reindex') + '<p id="search-reindex-log" />',
            'stdButtons': false
            });
            return false;
            }
        </script>


        <div class="panel">
            <div class="header" onclick="panelSwitcher(this);">
                <span>
                    <xsl:value-of select="@label" />
                </span>
                <div class="l" /><div class="r" />
            </div>
            <div class="content">
                <table class="tableContent">
                    <tbody>
                        <xsl:apply-templates select="option" mode="settings.modify" />
                    </tbody>
                </table>

                <div class="buttons">
                    <div>
                        <input type="submit" value="&label-search-reindex;"
                               onclick="return rebuildSearchIndex();"
                        />
                        <span class="l" /><span class="r" />
                    </div>

                    <div>
                        <input type="submit" value="&label-search-empty;"
                               onclick="window.location = '{$lang-prefix}/admin/search/truncate/'; return false;"
                        />
                        <span class="l" /><span class="r" />
                    </div>
                </div>
            </div>
        </div>
    </xsl:template>

    <xsl:template match="option" mode="settings.modify">
        <tr>
            <td class="eq-col">
                <label for="{@name}">
                    <xsl:value-of select="@label" />
                </label>
            </td>
            <td>
                <xsl:value-of select="value" />
            </td>
        </tr>
    </xsl:template>
</xsl:stylesheet>