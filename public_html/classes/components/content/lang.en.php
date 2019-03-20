<?php

	/** Языковые константы для английской версии */
	$C_LANG = [];
	$C_LANG['module_name'] = 'Structure';
	$C_LANG['module_title'] = 'Site structure management';
	$C_LANG['module_description'] =
		'The module is designed to control text and graphics site materials, working with partitions, adding new material.';
	$C_LANG['sitetree'] = 'Control module content site';
	$C_LANG['content'] = 'Page not found';
	$C_LANG['config'] = 'Module settings';
	$C_LANG['templates_do'] = 'Saving settings ...';
	$C_LANG['sitemap'] = 'Sitemap';
	$C_LANG['pagesByDomainTags'] = 'Pages tagged';
	$C_LANG['tpl_edit'] = 'Edit template';
	$C_LANG['getRecentPages'] = 'Recently viewed';

	$LANG_EXPORT = [];
	$LANG_EXPORT['content_mainpage'] = 'Home';
	$LANG_EXPORT['content_module'] = 'Module';
	$LANG_EXPORT['content_error'] = 'Error';
	$LANG_EXPORT['content_cifi_upload_text'] = 'Upload';
	$LANG_EXPORT['content_page_permission'] = 'You do not have access to this page.';
	$LANG_EXPORT['content_error_404'] = 'Document not found.';
	$LANG_EXPORT['content_error_404_header'] = '404 - Document not found.';
	$LANG_EXPORT['content_error_unhandled'] = 'Macro "content" is not treated for an unknown reason.';
	$LANG_EXPORT['content_sitemap'] = 'Sitemap';
	$LANG_EXPORT['content_sitetree'] = 'Structure';
	$LANG_EXPORT['content_newpage'] = 'New page';
	$LANG_EXPORT['content_error_insert_null'] =
		'Macro Error (content insert): You did not specify which page you want to insert.';
	$LANG_EXPORT['content_error_insert_nopage'] = 'Macro Error (content insert): The specified page does not exist.';
	$LANG_EXPORT['content_error_insert_recursy'] = 'Macro Error (content insert): Possible recursion.';
	$LANG_EXPORT['content_hiddenpage'] = '(hidden)';
	$LANG_EXPORT['content_usesitemap'] = <<<END
		<p>The page you requested could not be found. Maybe we deleted or moved it. Perhaps you have come to an outdated link or typed the address incorrectly. Use the search engine or site map.</p>

		<h2 class="orange">Sitemap</h2>
		%content sitemap()%
END;
	$LANG_EXPORT['tempform_fname'] = "First Name, Last Name <span style='color:red'>*</span>";
	$LANG_EXPORT['tempform_cname'] = 'Company name';
	$LANG_EXPORT['tempform_email'] = 'E-mail';
	$LANG_EXPORT['tempform_adress'] = 'Address';
	$LANG_EXPORT['tempform_phone'] = 'Phone';
	$LANG_EXPORT['tempform_fax'] = 'Fax';
	$LANG_EXPORT['tempform_message'] = "Your message <span style='color:red'>*</span>";
	$LANG_EXPORT['tepmform_ok'] = 'Your message was successfully sent.';
	$LANG_EXPORT['tempform_failed'] = 'An error occurred while sending messages. Sorry for the inconvenience.';
	$LANG_EXPORT['tempform_header'] = 'ONLINE support';
	$LANG_EXPORT['error_free_max_pages'] = 'The limit of 10 pages for the Free-version UMI.CMS';

