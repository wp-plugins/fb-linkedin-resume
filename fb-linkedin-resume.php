<?php
/*
Plugin Name: FB LinkedIn Resume
Plugin URI: http://fabrizioballiano.net
Description: Publish all your LinkedIn public profile (or just some selected parts) on your blog.
Version: 1.0
Author: Fabrizio Balliano
Author URI: http://fabrizioballiano.net
*/


/*  Copyright 2011 Fabrizio Balliano (email: fabrizio@fabrizioballiano.it)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


define("fb_linkedin_resume_path", WP_PLUGIN_URL . "/" . str_replace(basename( __FILE__), "", plugin_basename(__FILE__)));
define("fb_linkedin_resume_version", "1.0");
$plugin_dir = basename(dirname(__FILE__));

define("fb_linkedin_resume_admin_options_name", "fb_linkedin_resume_admin_options");
add_shortcode("fb_linkedin_resume_full", "fb_linkedin_resume_full");
add_shortcode("fb_linkedin_resume_header", "fb_linkedin_resume_header");
add_shortcode("fb_linkedin_resume_summary", "fb_linkedin_resume_summary");
add_shortcode("fb_linkedin_resume_experience", "fb_linkedin_resume_experience");
add_shortcode("fb_linkedin_resume_certifications", "fb_linkedin_resume_certifications");
add_shortcode("fb_linkedin_resume_languages", "fb_linkedin_resume_languages");
add_shortcode("fb_linkedin_resume_education", "fb_linkedin_resume_education");
add_shortcode("fb_linkedin_resume_additional", "fb_linkedin_resume_additional");

function fb_linkedin_resume_get_admin_options()
{
	return get_option(fb_linkedin_resume_admin_options_name);
}

function fb_linkedin_resume_get_resume()
{
	if (isset($GLOBALS["__fb_linkedin_resume_cache"])) {
		return $GLOBALS["__fb_linkedin_resume_cache"];
	}

	$options = fb_linkedin_resume_get_admin_options();
	require_once dirname(__FILE__) . "/simple_html_dom.php";
	$dom = file_get_html("http://www.linkedin.com/in/{$options["fb_linkedin_resume_url"]}");

	// removing groups
	foreach ($dom->find("#profile-additional .pubgroups") as $tmp) {
		$tmp->outertext = "";
	}

	// removing links
	foreach ($dom->find(".showhide-link") as $tmp) {
		$tmp->outertext = "";
	}
	foreach ($dom->find(".company-profile-public") as $tmp) {
		$tmp->outertext = $tmp->innertext;
	}

	// removing connections
	foreach ($dom->find(".overview-connections") as $tmp) {
		$tmp->outertext = "";
	}
	foreach ($dom->find(".profile-header dt") as $tmp) {
		if (strtolower(trim($tmp->innertext)) == "connections") {
			$tmp->outertext = "";
		}
	}

	// removing "overview" title
	foreach ($dom->find(".profile-header .section-title") as $tmp) {
		$tmp->outertext = "";
	}


	$name = $dom->find(".given-name");
	$surname = $dom->find(".family-name");
	$fullname = "{$name[0]->innertext} {$surname[0]->innertext}";

	// removing all "fullname's "
	foreach ($dom->find("h2") as $tmp) {
		$tmp->innertext = str_replace("{$fullname}'s ", "", $tmp->innertext);
	}

	$GLOBALS["__fb_linkedin_resume_cache"] = $dom;
	return $dom;
}

function fb_linkedin_resume_full($params) {
	wp_register_style("fb_linkedin_resume", fb_linkedin_resume_path . "style.css", false, fb_linkedin_resume_version, "all");
	wp_print_styles("fb_linkedin_resume");
	
	$resume = fb_linkedin_resume_get_resume($params);

	$header = $resume->find(".profile-header");
	$summary = $resume->find("#profile-summary");
	$experience = $resume->find("#profile-experience");
	$education = $resume->find("#profile-education");
	$certifications = $resume->find("#profile-certifications");
	$languages = $resume->find("#profile-languages");
	$additional = $resume->find("#profile-additional");
	$skills = $resume->find("#profile-skills");

	return $header[0] . $summary[0] . $experience[0] . $certifications[0] .
		 $languages[0] . $skills[0] . $education[0] . $additional[0];
}

function fb_linkedin_resume_header($params) {
	wp_register_style("fb_linkedin_resume", fb_linkedin_resume_path . "style.css", false, fb_linkedin_resume_version, "all");
	wp_print_styles("fb_linkedin_resume");
	
	$resume = fb_linkedin_resume_get_resume($params);
	$header = $resume->find(".profile-header");
	return $header[0];
}

function fb_linkedin_resume_summary($params) {
	wp_register_style("fb_linkedin_resume", fb_linkedin_resume_path . "style.css", false, fb_linkedin_resume_version, "all");
	wp_print_styles("fb_linkedin_resume");
	
	$resume = fb_linkedin_resume_get_resume($params);
	$summary = $resume->find("#profile-summary");
	$summary = $summary[0];
	if (isset($params["title"])) {
		$h2 = $summary->find("h2");
		$h2[0]->innertext = $params["title"];
	}
	return $summary;
}

function fb_linkedin_resume_experience($params) {
	wp_register_style("fb_linkedin_resume", fb_linkedin_resume_path . "style.css", false, fb_linkedin_resume_version, "all");
	wp_print_styles("fb_linkedin_resume");
	
	$resume = fb_linkedin_resume_get_resume($params);
	$experience = $resume->find("#profile-experience");
	$experience = $experience[0];
	if (isset($params["title"])) {
		$h2 = $experience->find("h2");
		$h2[0]->innertext = $params["title"];
	}
	return $experience;
}

function fb_linkedin_resume_education($params) {
	wp_register_style("fb_linkedin_resume", fb_linkedin_resume_path . "style.css", false, fb_linkedin_resume_version, "all");
	wp_print_styles("fb_linkedin_resume");
	
	$resume = fb_linkedin_resume_get_resume($params);
	$education = $resume->find("#profile-education");
	$education = $education[0];
	if (isset($params["title"])) {
		$h2 = $education->find("h2");
		$h2[0]->innertext = $params["title"];
	}
	return $education;
}

function fb_linkedin_resume_certifications($params) {
	wp_register_style("fb_linkedin_resume", fb_linkedin_resume_path . "style.css", false, fb_linkedin_resume_version, "all");
	wp_print_styles("fb_linkedin_resume");
	
	$resume = fb_linkedin_resume_get_resume($params);
	$certifications = $resume->find("#profile-certifications");
	$certifications = $certifications[0];
	if (isset($params["title"])) {
		$h2 = $certifications->find("h2");
		$h2[0]->innertext = $params["title"];
	}
	return $certifications;
}

function fb_linkedin_resume_languages($params) {
	wp_register_style("fb_linkedin_resume", fb_linkedin_resume_path . "style.css", false, fb_linkedin_resume_version, "all");
	wp_print_styles("fb_linkedin_resume");
	
	$resume = fb_linkedin_resume_get_resume($params);
	$languages = $resume->find("#profile-languages");
	$languages = $languages[0];
	if (isset($params["title"])) {
		$h2 = $languages->find("h2");
		$h2[0]->innertext = $params["title"];
	}
	return $languages;
}

function fb_linkedin_resume_additional($params) {
	wp_register_style("fb_linkedin_resume", fb_linkedin_resume_path . "style.css", false, fb_linkedin_resume_version, "all");
	wp_print_styles("fb_linkedin_resume");
	
	$resume = fb_linkedin_resume_get_resume($params);
	$additional = $resume->find("#profile-additional");
	$additional = $additional[0];
	if (isset($params["title"])) {
		$h2 = $additional->find("h2");
		$h2[0]->innertext = $params["title"];
	}
	if (isset($params["title_interests"])) {
		$dt = $additional->find("dt.interests");
		$dt[0]->innertext = $params["title_interests"];
	}
	if (isset($params["title_honors"])) {
		$dt = $additional->find("dt.honors");
		$dt[0]->innertext = $params["title_honors"];
	}
	return $additional;
}

function fb_linkedin_resume_display_admin_page()
{
	if (isset($_REQUEST['fb_linkedin_resume_url'])) {
		$options = array();
		$options["fb_linkedin_resume_url"] = $_REQUEST["fb_linkedin_resume_url"];
		update_option(fb_linkedin_resume_admin_options_name, $options);
	}
	$options = fb_linkedin_resume_get_admin_options();

	echo <<<EOF
	<div class="wrap">
		<h2>FB LinkedIn Resume options</h2>
		<form method="post" action="{$_SERVER["REQUEST_URI"]}">
			Just complete your LinkedIn profile URL,<br />if you want you can make it language aware using username/language syntax<br /><br />
			http://www.linkedin.com/in/<input type="text" name="fb_linkedin_resume_url" value="{$options["fb_linkedin_resume_url"]}"/><br /><br />
			<input type="submit" value="Save" />
		</form>
	</div>
EOF;
}

function fb_linkedin_resume_admin()
{
	add_options_page("FB LinkedIn Resume", "FB LinkedIn Resume", "manage_options", "fb-linkedin-resume", "fb_linkedin_resume_display_admin_page");
}

add_action("admin_menu", "fb_linkedin_resume_admin");
