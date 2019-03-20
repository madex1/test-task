<?php

$i18n = Array(

	"header-seo-seo"			=> "Position analysis",
	"header-seo-megaindex"		=> "MegaIndex settings",
	"perms-seo-seo" 			=> "SEO functions",

	"header-seo-config"			=> "SEO settings",
	"header-seo-links"			=> "Links analysis",
	"label-seo-domain"			=> "Domains",
	"option-seo-title"			=> "TITLE prefix",
	"option-seo-default-title" => "TITLE (default)",
	"option-seo-keywords"		=> "Keywords (default)",
	"option-seo-description"	=> "Description (default)",
	"header-seo-domains"		=> "SEO настройки доменов",

	"label-site-address"		=> "Site address",
	"label-site-analysis"		=> "Site analysis",
	"label-button"				=> "Get results",
	"label-repeat"				=> "Repeat",
	"label-results"				=> "Results",

	"label-query"				=> "Query",
	"label-yandex"				=> "Yandex",
	"label-google"				=> "Google",
	"label-count"				=> "Queries pro month",
	"label-wordstat"			=> "Wordstat",
	"label-price"				=> "Price",

	"label-link-from"			=> "Source",
	"label-link-to"				=> "Target",
	"label-tic-from"			=> "Source Thematic Citation Index",
	"label-tic-to"				=> "Target Thematic Citation Index of donor",
	"label-link-anchor"			=> "Link anchor",

	"label-seo-noindex"			=> "No information about %s is available in MegaIndex database. Please, register and add your website for index.",

	"option-megaindex-login"	=> "MegaIndex login",
	"option-megaindex-password"	=> "MegaIndex password",

	"error-invalid_answer"		=> "No valid answer from Megaindex. Try again later.",
	"error-authorization-failed"=> "Invalid login or password",
    "error"                     => "Error: ",
    "error-data"                => "Error: Invalid data",


	"header-seo-webmaster"		=> 'Yandex.Webmaster',
	"header-seo-yandex"			=> 'Yandex.Webmaster settings',

	"footer-webmaster-text"		=> 'Based on ',
	"footer-webmaster-link"		=> 'Yandex.Webmaster',

	'label-error-no-token'		=> '<div style="margin-bottom:20px;">Get the authorisation token and save it in the <a href="%s/admin/seo/yandex/">module settings</a> to work with Yandex.Webmaster.
	<br /> Do not worry, It is easy and takes just a few minutes.</div><a class="gettoken" href="https://oauth.yandex.ru/authorize?response_type=code&amp;client_id=47fc30ca18e045cdb75f17c9779cfc36" target="_blank">Get code</a>',
	'label-error-service-down'	=> 'Yandex.Webmaster service is temporary unavailable.',
	'label-error-host-is-a-mirror-of'	=> 'Host is a mirror of %s',
	'label-error-host-is-not-responding'	=> 'Host %s is not responding',

	'label-error-no-curl'		=> '<div style="margin-bottom:20px;">Unfortunately, Yandex.Webmaster working is impossible on this server because of <b>cURL library</b> absence.<br />
	To fix this problem contact hosting technical support or system administrator of the server.</div>',

	'option-token'			=> 'Your current token: ',
	'option-code'			=> 'Enter validation code',
	'link-code'				=> 'Get code',
	'webmaster-wrong-code'	=> 'Incorrect validation code',

	'option-webmaster-general'	=> 'General information',

	'js-webmaster-errors-header'	=> 'Following errors occurred: ',

	'js-webmaster-label-addhost'	=> 'To receive information about this site add it into Yandex.Webmaster and <br/>confirm your management right.',
	'js-webmaster-link-addhost'		=> 'Add and confirm management right',

	'js-webmaster-label-verfyhost'	=> 'To receive information about this site confirm your management right',
	'js-webmaster-link-verifyhost'	=> 'Confirm managment right',

	'js-webmaster-link-excluded'	=> 'Excluded pages',
	'js-webmaster-link-indexed'		=> 'Indexed pages',
	'js-webmaster-link-tops'		=> 'Popular search requests',
	'js-webmaster-link-links'		=> 'External links to the site',

	'js-webmaster-label-sitename'				=> 'Site',
	'js-webmaster-label-crawling'				=> 'Indexing',
	'js-webmaster-label-virused'				=> 'Viruses',
	'js-webmaster-label-last-access'			=> 'Last checking',
	'js-webmaster-label-tcy'					=> 'tCI',
	'js-webmaster-label-url-count'				=> 'Loaded by robot',
	'js-webmaster-label-url-errors'				=> 'Excluded from indexing',
	'js-webmaster-label-index-count'			=> 'Indexed',
	'js-webmaster-label-internal-links-count'	=> 'Internal links',
	'js-webmaster-label-links-count'			=> 'External links',

	'js-webmaster-index-label'			=> 'Indexed for the last week',
	'js-webmaster-index-total-label'	=> 'Total of indexed : ',
	'js-webmaster-index-nothing-label'	=> 'No added pages for the last week',
	'js-webmaster-links-label'			=> 'Links found for the last week',
	'js-webmaster-links-total-label'	=> 'Total of found links : ',
	'js-webmaster-links-nothing-label'	=> 'No added links for the last week',

	'js-webmaster-label-tops-query'		=> 'Search request',
	'js-webmaster-label-tops-shows'		=> 'Hits',
	'js-webmaster-label-tops-clicks'	=> 'Clicks',
	'js-webmaster-label-tops-position'	=> 'Position',

	'js-webmaster-verification-state-IN_PROGRESS'			=> 'Verification is in progress.',
	'js-webmaster-verification-state-NEVER_VERIFIED'		=> 'Verification has never been carried out.',
	'js-webmaster-verification-state-VERIFICATION_FAILED'	=> 'Verification error.',
	'js-webmaster-verification-state-VERIFIED'				=> 'Verified.',
	'js-webmaster-verification-state-WAITING'				=> 'Waiting in verification queue.',

	'js-webmaster-crawling-state-INDEXED'		=> 'Site is indexed',
	'js-webmaster-crawling-state-NOT_INDEXED'	=> 'Site is not indexed',
	'js-webmaster-crawling-state-WAITING'		=> 'Site is waiting for indexing',

	'js-webmaster-excluded-code-label' => 'Reason of pages exclusion',
	'js-webmaster-excluded-code-400' => 'HTTP-status: Invalid request (400)',
	'js-webmaster-excluded-code-401' => 'HTTP-status: Unauthorized request (401)',
	'js-webmaster-excluded-code-402' => 'HTTP-status: Payment required for the request (402)',
	'js-webmaster-excluded-code-403' => 'HTTP-status: Access to resource forbidden (403)',
	'js-webmaster-excluded-code-404' => 'HTTP-status: Resource not found (404)',
	'js-webmaster-excluded-code-405' => 'HTTP-status: Method not allowed (405)',
	'js-webmaster-excluded-code-406' => 'HTTP-status: Unacceptable resource type (406)',
	'js-webmaster-excluded-code-407' => 'HTTP-status: Firewall, proxy authentication required (407)',
	'js-webmaster-excluded-code-408' => 'HTTP-status: Request timeout (408)',
	'js-webmaster-excluded-code-409' => 'HTTP-status: Conflict (409)',
	'js-webmaster-excluded-code-410' => 'HTTP-status: Unavailable resource (410)',
	'js-webmaster-excluded-code-411' => 'HTTP-status: Length must be specified (411)',
	'js-webmaster-excluded-code-412' => 'HTTP-status: Precondition processing failure (412)',
	'js-webmaster-excluded-code-413' => 'HTTP-status: The body of the request is too long (413)',
	'js-webmaster-excluded-code-414' => 'HTTP-status: Unacceptable length of URI request (414)',
	'js-webmaster-excluded-code-415' => 'HTTP-status: Unsupported MIME type (415)',
	'js-webmaster-excluded-code-416' => 'HTTP-status: The range cannot be processed (416)',
	'js-webmaster-excluded-code-417' => 'HTTP-status: Expectation failure (417)',
	'js-webmaster-excluded-code-422' => 'HTTP-status: Unprocessable entity (422)',
	'js-webmaster-excluded-code-423' => 'HTTP-status: Locked (423)',
	'js-webmaster-excluded-code-424' => 'HTTP-status: Invalid dependency (424)',
	'js-webmaster-excluded-code-426' => 'HTTP-status: Upgrade required (426)',

	'js-webmaster-excluded-code-500' => 'HTTP-status: Internal server error (500)',
	'js-webmaster-excluded-code-501' => 'HTTP-status: Method not supported (501)',
	'js-webmaster-excluded-code-502' => 'HTTP-status: Gateway error (502)',
	'js-webmaster-excluded-code-503' => 'HTTP-status: Service unavailable (503)',
	'js-webmaster-excluded-code-504' => 'HTTP-status: Gateway timeout (504)',
	'js-webmaster-excluded-code-505' => 'HTTP-status: HTTP version is not supported (505)',
	'js-webmaster-excluded-code-507' => 'HTTP-status: Insufficient space (507)',
	'js-webmaster-excluded-code-510' => 'HTTP-status: Extensions are missing (510)',

	'js-webmaster-excluded-code-1001' => 'Connection failure',
	'js-webmaster-excluded-code-1002' => 'Document is too long',
	'js-webmaster-excluded-code-1003' => 'Document access restricted in robots.txt file',
	'js-webmaster-excluded-code-1004' => 'Document address does not comply with HTTP standard',
	'js-webmaster-excluded-code-1005' => 'Document format is not supported',
	'js-webmaster-excluded-code-1006' => 'DNS error',
	'js-webmaster-excluded-code-1007' => 'Invalid HTTP-status code',
	'js-webmaster-excluded-code-1008' => 'Invalid HTTP-header',
	'js-webmaster-excluded-code-1010' => 'Unable to connect to web server',
	'js-webmaster-excluded-code-1013' => 'Wrong message length',
	'js-webmaster-excluded-code-1014' => 'Wrong encoding',
	'js-webmaster-excluded-code-1019' => 'Wrong amount of data transferred',
	'js-webmaster-excluded-code-1020' => 'HTTP-headers length over limit',
	'js-webmaster-excluded-code-1021' => 'URL length over limit',

	'js-webmaster-excluded-code-2004' => 'Document contains a refresh metatag',
	'js-webmaster-excluded-code-2005' => 'Document contains a noindex metatag',
	'js-webmaster-excluded-code-2006' => 'Wrong encoding',
	'js-webmaster-excluded-code-2007' => 'A document is a server log',
	'js-webmaster-excluded-code-2010' => 'Wrong document format',
	'js-webmaster-excluded-code-2011' => 'Encoding not determined',
	'js-webmaster-excluded-code-2012' => 'Language not supported',
	'js-webmaster-excluded-code-2014' => 'Document does not contain text',
	'js-webmaster-excluded-code-2016' => 'Too many links',
	'js-webmaster-excluded-code-2020' => 'Decompression error',
	'js-webmaster-excluded-code-2024' => 'Document size is 0 bite',
	'js-webmaster-excluded-code-2025' => 'Document is not canonical',

	'js-webmaster-excluded-count-label' => 'Number of pages',

	'js-webmaster-excluded-severity-label'					=> 'Error type',
	'js-webmaster-excluded-severity-SITE_ERROR'				=> 'Site error.',
	'js-webmaster-excluded-severity-UNSUPPORTED_BY_ROBOT'	=> 'Unsupported by robot.',
	'js-webmaster-excluded-severity-DISALLOWED_BY_USER'		=> 'Prohibited by user.',
	'js-webmaster-excluded-severity-OK'						=> 'No error.',

	'js-webmaster-excluded-total-label' => 'Total of excluded pages: ',

	// Яндекс.Острова
	'header-seo-islands'				=> 'Yandex.Islands',
	'header-seo-island_edit'			=> 'Edit Yandex.Island',
	'header-seo-island_get'				=> 'Get Yandex.Island',
	'label-add-island'					=> 'Add',
	'label-create-island-xml'			=> 'Create',
	'object-new-seo-island'				=> 'New Yandex.Island',

	'label-island-user-fields-group'	=> "Island fields",

	'js-island-edit-add_field'		=> "Add field",
	'js-island-edit-title'			=> 'Name',
	'js-island-edit-name'			=> 'Id',
	'js-island-edit-type'			=> 'Type',
	'js-island-edit-restriction'	=> 'Value format',
	'js-island-edit-guide'			=> 'Guide',
	'js-island-edit-visible'		=> 'Show in island',
	'js-island-save-edit-field'		=> 'Save',
	'js-island-confirm-cancel'		=> 'Cancel',
	'js-island-edit-new_field'		=> 'New field',
	'js-seo-island'					=> 'Create island file',
	'js-seo-island-getlink'			=> 'Get result link',
	'js-seo-island-getfile'			=> 'Download island file',
	'js-seo-edit-confirm_title'		=> "Confirm deleting",
	'js-seo-edit-confirm_text'		=> "Press \"Delete\", if you are sure (irreversible action).",
	'js-seo-edit-saving'			=> "Saving",
	'js-island-edit-edit'			=> 'Edit field',
	'js-island-edit-remove'			=> 'Delete field',

	'js-island-change-symlink-warning'	=> 'Fields from dominant type will be loaded. Fields from the previous dominant type will be deleted. Page will be reloaded.',
);

?>
