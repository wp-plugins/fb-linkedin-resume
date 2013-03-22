<?php
/*
Plugin Name: FB LinkedIn Resume
Plugin URI: http://fabrizioballiano.net/fb-linkedin-resume
Description: Publish all your LinkedIn public profile (or just some selected parts) on your blog.
Version: 2.7.4
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
define("fb_linkedin_resume_version", "2.7.4");
$plugin_dir = basename(dirname(__FILE__));

define("fb_linkedin_resume_admin_options_name", "fb_linkedin_resume_admin_options");
add_shortcode("fb_linkedin_resume_full", "fb_linkedin_resume_full");
add_shortcode("fb_linkedin_resume_header", "fb_linkedin_resume_header");
add_shortcode("fb_linkedin_resume_summary", "fb_linkedin_resume_summary");
add_shortcode("fb_linkedin_resume_experience", "fb_linkedin_resume_experience");
add_shortcode("fb_linkedin_resume_certifications", "fb_linkedin_resume_certifications");
add_shortcode("fb_linkedin_resume_skills", "fb_linkedin_resume_skills");
add_shortcode("fb_linkedin_resume_publications", "fb_linkedin_resume_publications");
add_shortcode("fb_linkedin_resume_languages", "fb_linkedin_resume_languages");
add_shortcode("fb_linkedin_resume_education", "fb_linkedin_resume_education");
add_shortcode("fb_linkedin_resume_courses", "fb_linkedin_resume_courses");
add_shortcode("fb_linkedin_resume_organizations", "fb_linkedin_resume_organizations");
add_shortcode("fb_linkedin_resume_additional", "fb_linkedin_resume_additional");
add_shortcode("fb_linkedin_resume_projects", "fb_linkedin_resume_projects");

function fb_linkedin_resume_get_admin_options()
{
	return get_option(fb_linkedin_resume_admin_options_name);
}

function fb_linkedin_resume_get_resume($params)
{
	$options = fb_linkedin_resume_get_admin_options();
	$wp_remote_get_args = array(
		"user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.89 Safari/537.1"
	);

	if (isset($params["user"])) {
		$tmp_lang = explode("/", $options["fb_linkedin_resume_url"]);
		if (isset($tmp_lang[1])) {
			$tmp_lang = $tmp_lang[1];
		} else {
			unset($tmp_lang);
		}
		
		$options["fb_linkedin_resume_url"] = $params["user"];
		if (isset($tmp_lang)) {
			$options["fb_linkedin_resume_url"] .= "/$tmp_lang";
		}
	}

	if (isset($params["lang"])) {
		$wp_remote_get_args["headers"] = array("accept-language" => $params["lang"]);
		$options["fb_linkedin_resume_url"] = explode("/", $options["fb_linkedin_resume_url"]);
		$options["fb_linkedin_resume_url"] = "{$options["fb_linkedin_resume_url"][0]}/{$params["lang"]}";
	}
	
	if (strtolower(substr($options["fb_linkedin_resume_url"], 0, 4)) != "http") {
		$options["fb_linkedin_resume_url"] = "http://www.linkedin.com/in/{$options["fb_linkedin_resume_url"]}";
	}

	if (isset($GLOBALS["__fb_linkedin_resume_cache"]) and isset($GLOBALS["__fb_linkedin_resume_cache"][$options["fb_linkedin_resume_url"]])) {
		return $GLOBALS["__fb_linkedin_resume_cache"][$options["fb_linkedin_resume_url"]];
	}
	
	if (!function_exists("str_get_html")) {
		require_once dirname(__FILE__) . "/simple_html_dom.php";
	}

	$linkedin_html = wp_remote_get($options["fb_linkedin_resume_url"], $wp_remote_get_args);
	if (is_wp_error($linkedin_html)) {
		$errors = $linkedin_html->get_error_messages();
		$message = "FB Linkedin Resume: unable to connect to LinkedIn website<br />";
		switch (count($errors)) {
			case 1 :
				$message .= "{$errors[0]}";
				break;
			default :
				$message .= "<ul>\n\t\t<li>" . join( "</li>\n\t\t<li>", $errors ) . "</li>\n\t</ul>";
				break;
		}
		wp_die($message);
	}
	$dom = str_get_html($linkedin_html["body"]);
	
	$body_check = $dom->find("body");
	if (empty($body_check)) {
		// we've a weirdly compressed HTML, let's try gzinflate
		$linkedin_html["body"] = gzinflate($linkedin_html["body"]);
		$dom = str_get_html($linkedin_html["body"]);
	}
	
	$body_check = $dom->find("body");
	if (empty($body_check)) {
		wp_die("FB Linkedin Resume: we're sorry but we can't correctly download your LinkedIn profile.");
	}

	// removing linkedin links from header
	foreach ($dom->find(".profile-header .join-linkedin") as $tmp) {
		$tmp->outertext = "";
	}

	// removing groups
	foreach ($dom->find("#profile-additional .pubgroups") as $tmp) {
		$tmp->outertext = "";
	}

	// removing links
	foreach ($dom->find(".showhide-link") as $tmp) {
		$tmp->outertext = "";
	}
	foreach ($dom->find(".see-more-less") as $tmp) {
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
	$name = $name[0]->innertext;
	$surname = $dom->find(".family-name");
	$surname = $surname[0]->innertext;

	// removing all "fullname's " (only works with english)
	foreach ($dom->find("h2") as $tmp) {
		$tmp->innertext = preg_replace("/[ ]*{$name}[ ]+{$surname}'s[ ]*/", "", $tmp->innertext);
	}
	
	// pruning some spaces
	foreach ($dom->find("ul.specifics li") as $tmp) {
		$tmp->innertext = trim($tmp->innertext);
	}

	$GLOBALS["__fb_linkedin_resume_cache"][$options["fb_linkedin_resume_url"]] = $dom;
	return $dom;
}

function fb_linkedin_resume_full($params) {
	return fb_linkedin_resume_header($params) .
		fb_linkedin_resume_summary($params) .
		fb_linkedin_resume_experience($params) .
		fb_linkedin_resume_certifications($params) .
		fb_linkedin_resume_skills($params) .
		fb_linkedin_resume_publications($params) .
		fb_linkedin_resume_languages($params) .
		fb_linkedin_resume_education($params) .
		fb_linkedin_resume_courses($params) .
		fb_linkedin_resume_organizations($params) .
		fb_linkedin_resume_projects($params) .
		fb_linkedin_resume_additional($params);
}

function fb_linkedin_resume_header($params) {
	wp_register_style("fb_linkedin_resume", fb_linkedin_resume_path . "style.css", false, fb_linkedin_resume_version, "all");
	wp_print_styles("fb_linkedin_resume");
	
	$resume = fb_linkedin_resume_get_resume($params);
	$header = $resume->find(".profile-header");
	$header = $header[0];
	
	foreach ($header->find("dd.websites a") as $link) {
		$link->href = "http://www.linkedin.com{$link->href}";
	}
	
	return $header;
}

function fb_linkedin_resume_summary($params) {
	wp_register_style("fb_linkedin_resume", fb_linkedin_resume_path . "style.css", false, fb_linkedin_resume_version, "all");
	wp_print_styles("fb_linkedin_resume");
	
	$resume = fb_linkedin_resume_get_resume($params);
	$summary = $resume->find("#profile-summary");
	if (empty($summary)) return "";

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
	if (empty($experience)) return "";

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
	if (empty($education)) return "";

	$education = $education[0];
	if (isset($params["title"])) {
		$h2 = $education->find("h2");
		$h2[0]->innertext = $params["title"];
	}
	return $education;
}

function fb_linkedin_resume_courses($params) {
	wp_register_style("fb_linkedin_resume", fb_linkedin_resume_path . "style.css", false, fb_linkedin_resume_version, "all");
	wp_print_styles("fb_linkedin_resume");

	$resume = fb_linkedin_resume_get_resume($params);
	$education = $resume->find("#profile-courses");
	if (empty($education)) return "";

	$education = $education[0];
	if (isset($params["title"])) {
		$h2 = $education->find("h2");
		$h2[0]->innertext = $params["title"];
	}
	return $education;
}

function fb_linkedin_resume_organizations($params) {
	wp_register_style("fb_linkedin_resume", fb_linkedin_resume_path . "style.css", false, fb_linkedin_resume_version, "all");
	wp_print_styles("fb_linkedin_resume");

	$resume = fb_linkedin_resume_get_resume($params);
	$education = $resume->find("#profile-organizations");
	if (empty($education)) return "";

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
	if (empty($certifications)) return "";

	$certifications = $certifications[0];
	if (isset($params["title"])) {
		$h2 = $certifications->find("h2");
		$h2[0]->innertext = $params["title"];
	}
	return $certifications;
}

function fb_linkedin_resume_skills($params) {
	wp_register_style("fb_linkedin_resume", fb_linkedin_resume_path . "style.css", false, fb_linkedin_resume_version, "all");
	wp_print_styles("fb_linkedin_resume");
	
	$resume = fb_linkedin_resume_get_resume($params);
	$skills = $resume->find("#profile-skills");
	if (empty($skills)) return "";

	$skills = $skills[0];
	if (isset($params["title"])) {
		$h2 = $skills->find("h2");
		$h2[0]->innertext = $params["title"];
	}

	foreach ($skills->find("li span") as $link) {
		$classes = explode(" ", (string)$link->class);
		foreach ($classes as $class) {
			$matches = array();
			if (preg_match("/proficiency=(.*)$/", $class, $matches)) {
				$proficiency = urldecode($matches[1]);
				$proficiency = trim(preg_replace("/^.*:/", "", $proficiency));
			}
		}
		
		$link->class = null;
		$link->title = $proficiency;
		$link->innertext = $link->plaintext;
	}

	return $skills;
}

function fb_linkedin_resume_publications($params) {
	wp_register_style("fb_linkedin_resume", fb_linkedin_resume_path . "style.css", false, fb_linkedin_resume_version, "all");
	wp_print_styles("fb_linkedin_resume");
	
	$resume = fb_linkedin_resume_get_resume($params);
	$publications = $resume->find("#profile-publications");
	if (empty($publications)) return "";

	$publications = $publications[0];
	if (isset($params["title"])) {
		$h2 = $publications->find("h2");
		$h2[0]->innertext = $params["title"];
	}

	foreach ($publications->find("li.publication a") as $link) {
		$link->href = "http://www.linkedin.com/{$link->href}";
	}

	foreach ($publications->find("div.attribution a") as $link) {
		$link->outertext = $link->plaintext;
	}

	foreach ($publications->find("div.summary script") as $script) {
		$script->outertext = "";
	}

	return $publications;
}

function fb_linkedin_resume_languages($params) {
	wp_register_style("fb_linkedin_resume", fb_linkedin_resume_path . "style.css", false, fb_linkedin_resume_version, "all");
	wp_print_styles("fb_linkedin_resume");
	
	$resume = fb_linkedin_resume_get_resume($params);
	$languages = $resume->find("#profile-languages");
	if (empty($languages)) return "";

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
	if (empty($additional)) return "";

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
	foreach ($additional->find("dd.websites a") as $link) {
		$link->href = "http://www.linkedin.com/{$link->href}";
	}
	
	return $additional;
}

function fb_linkedin_resume_projects($params) {
	wp_register_style("fb_linkedin_resume", fb_linkedin_resume_path . "style.css", false, fb_linkedin_resume_version, "all");
	wp_print_styles("fb_linkedin_resume");

	$resume = fb_linkedin_resume_get_resume($params);
	$projects = $resume->find("#profile-projects");
	if (empty($projects)) return "";

	$projects = $projects[0];
	if (isset($params["title"])) {
		$h2 = $projects->find("h2");
		$h2[0]->innertext = $params["title"];
	}

	return $projects;
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
			Just complete your LinkedIn profile URL,<br />if you want you can make it language aware using username/language syntax.<br />
			If you want you can also type your full profile URL.<br /><br />
			http://www.linkedin.com/in/<input type="text" name="fb_linkedin_resume_url" value="{$options["fb_linkedin_resume_url"]}" style="width:300px" /><br /><br />
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