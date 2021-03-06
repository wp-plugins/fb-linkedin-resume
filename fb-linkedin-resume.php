<?php
/*
Plugin Name: FB LinkedIn Resume
Plugin URI: http://fabrizioballiano.net/fb-linkedin-resume
Description: Publish all your LinkedIn public profile (or just some selected parts) on your blog.
Version: 3.0.0
Author: Fabrizio Balliano
Author URI: http://fabrizioballiano.net
*/


/*  Copyright 2011-2015 Fabrizio Balliano (email: fabrizio@fabrizioballiano.it)

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

define("fb_linkedin_resume_path", WP_PLUGIN_URL . "/" . str_replace(basename(__FILE__), "", plugin_basename(__FILE__)));
define("fb_linkedin_resume_version", "3.0.0");
define("fb_linkedin_resume_cache_dir", dirname(
        dirname(dirname(realpath(__FILE__)))
    ) . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR . "fb_linkedin_resume");
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
add_shortcode("fb_linkedin_resume_honors", "fb_linkedin_resume_honors");

add_action("admin_menu", "fb_linkedin_resume_admin");

function fb_linkedin_resume_get_admin_options()
{
    return get_option(fb_linkedin_resume_admin_options_name);
}

function fb_linkedin_resume_get_resume($params)
{
    $options = fb_linkedin_resume_get_admin_options();
    if (!function_exists("str_get_html")) {
        require_once dirname(__FILE__) . "/simple_html_dom.php";
    }

    if (@$options["fb_linkedin_resume_source"]) {
        $dom = str_get_html($options["fb_linkedin_resume_source"]);

        // check if it's the right page
        $header = $dom->find(".profile-header,.profile-card", 0);
        if (!$header) {
            wp_die("FB LinkedIn resume has been configured to use the cached LinkedIn HTML (configured via plugin settings) but the provided HTML is not valid (it has to be copied without being logged in).");
        }
    } else {
        $wp_remote_get_args = array(
            "user-agent" => "Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0",
			"sslverify" => false,
            "headers" => array(
                "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
                "Accept-Language" => "en"
            )
        );

        if (isset($params["user"])) $options["fb_linkedin_resume_url"] = $params["user"];
        if (isset($params["lang"])) {
            $wp_remote_get_args["headers"]["Accept-Language"] = $params["lang"];
            $options["fb_linkedin_resume_url"] .= "/{$params["lang"]}";
        }

        if (strtolower(substr($options["fb_linkedin_resume_url"], 0, 4)) != "http") {
            $options["fb_linkedin_resume_url"] = "http://www.linkedin.com/in/{$options["fb_linkedin_resume_url"]}";
        }

        $options["fb_linkedin_resume_url"] = trim($options["fb_linkedin_resume_url"]);
        $options["fb_linkedin_resume_url"] = trim($options["fb_linkedin_resume_url"], "/");
        $options["fb_linkedin_resume_url"] = preg_replace("/^https:/i", "http:", $options["fb_linkedin_resume_url"]);
        if (isset($GLOBALS["__fb_linkedin_resume_cache"]) and isset($GLOBALS["__fb_linkedin_resume_cache"][$options["fb_linkedin_resume_url"]])) {
            return $GLOBALS["__fb_linkedin_resume_cache"][$options["fb_linkedin_resume_url"]];
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
                    $message .= "<ul>\n\t\t<li>" . join("</li>\n\t\t<li>", $errors) . "</li>\n\t</ul>";
                    break;
            }
            $linkedin_html["body"] = fb_wp_die($message, $options["fb_linkedin_resume_url"]);
        }
        $dom = str_get_html($linkedin_html["body"]);

        $body_check = $dom->find("body");
        if (empty($body_check)) {
            // we've a weirdly compressed HTML, let's try gzinflate
            $linkedin_html["body"] = fb_linkedin_resume_decompress($linkedin_html["body"]);
            $dom = str_get_html($linkedin_html["body"]);
        }

        $body_check = $dom->find("body");
        if (empty($body_check)) {
            $linkedin_html["body"] = fb_wp_die(
                "FB Linkedin Resume: we're sorry but we can't correctly download your LinkedIn profile.",
                $options["fb_linkedin_resume_url"]
            );
            $dom = str_get_html($linkedin_html["body"]);
        }
    }

    // cleaning old profile html
    foreach ($dom->find(".profile-header .join-linkedin,#profile-additional .pubgroups,.showhide-link,.see-more-less,.company-profile-public,.overview-connections,.profile-header .section-title,script") as $tmp) {
        $tmp->outertext = "";
    }

    foreach ($dom->find(".profile-header dt") as $tmp) {
        if (strtolower(trim($tmp->innertext)) == "connections") {
            $tmp->outertext = "";
        }
    }

    // cleaning 2015 profile html
    foreach ($dom->find(".profile-card .profile-aux,#signup-link-tile-bg,#signup-link-tile,#overview-recommendation-count,.external-link-indicator,h5.experience-logo,h5.certification-logo,dl.associated-list") as $tmp) {
        $tmp->outertext = "";
    }

    $name = $dom->find(".given-name", 0);
    $name = $name->innertext;
    $surname = $dom->find(".family-name", 0);
    $surname = $surname->innertext;

    // removing all "fullname's " (only works with english)
    foreach ($dom->find("h2") as $tmp) {
        $tmp->innertext = preg_replace("/[ ]*{$name}[ ]+{$surname}'s[ ]*/", "", $tmp->innertext);
    }

    // pruning some spaces
    foreach ($dom->find("ul.specifics li") as $tmp) {
        $tmp->innertext = trim($tmp->innertext);
    }

    $GLOBALS["__fb_linkedin_resume_profile_version"] = "old";
    if ($dom->find(".profile-card", 0)) $GLOBALS["__fb_linkedin_resume_profile_version"] = "2015";

    $GLOBALS["__fb_linkedin_resume_cache"][$options["fb_linkedin_resume_url"]] = $dom;
    fb_linkedin_save_cache($options, $linkedin_html);
    return $dom;
}

function fb_linkedin_resume_full($params)
{
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

function fb_linkedin_resume_header($params)
{
    wp_register_style(
        "fb_linkedin_resume",
        fb_linkedin_resume_path . "style.css",
        false,
        fb_linkedin_resume_version,
        "all"
    );
    wp_print_styles("fb_linkedin_resume");

    $resume = fb_linkedin_resume_get_resume($params);
    $header = $resume->find(".profile-header,.profile-card", 0);

    foreach ($header->find("dd.websites a") as $link) {
        $link->href = "http://www.linkedin.com{$link->href}";
    }

    foreach ($header->find("th") as $th) {
        $th->innertext = $th->plaintext;
    }

    if ($GLOBALS["__fb_linkedin_resume_profile_version"] == "2015") return "<div class='public-profile'><div id='background'>$header</div></div>";
    return $header;
}

function fb_linkedin_resume_summary($params)
{
    wp_register_style(
        "fb_linkedin_resume",
        fb_linkedin_resume_path . "style.css",
        false,
        fb_linkedin_resume_version,
        "all"
    );
    wp_print_styles("fb_linkedin_resume");

    $resume = fb_linkedin_resume_get_resume($params);
    $summary = $resume->find("#profile-summary,#background-summary", 0);
    if (empty($summary)) {
        return "";
    }

    if (isset($params["title"])) {
        $h2 = $summary->find("h2,h3", 0);
        $h2->innertext = $params["title"];
    }

    if ($GLOBALS["__fb_linkedin_resume_profile_version"] == "2015") return "<div class='public-profile'><div id='background'>$summary</div></div>";
    return $summary;
}

function fb_linkedin_resume_experience($params)
{
    wp_register_style(
        "fb_linkedin_resume",
        fb_linkedin_resume_path . "style.css",
        false,
        fb_linkedin_resume_version,
        "all"
    );
    wp_print_styles("fb_linkedin_resume");

    $resume = fb_linkedin_resume_get_resume($params);
    $experience = $resume->find("#profile-experience,#background-experience", 0);
    if (empty($experience)) {
        return "";
    }

    if (isset($params["title"])) {
        $h2 = $experience->find("h2,h3", 0);
        $h2->innertext = $params["title"];
    }

    if ($GLOBALS["__fb_linkedin_resume_profile_version"] == "2015") return "<div class='public-profile'><div id='background'>$experience</div></div>";
    return $experience;
}

function fb_linkedin_resume_education($params)
{
    wp_register_style(
        "fb_linkedin_resume",
        fb_linkedin_resume_path . "style.css",
        false,
        fb_linkedin_resume_version,
        "all"
    );
    wp_print_styles("fb_linkedin_resume");

    $resume = fb_linkedin_resume_get_resume($params);
    $education = $resume->find("#background-education,#profile-education", 0);
    if (empty($education)) {
        return "";
    }

    if (isset($params["title"])) {
        $h2 = $education->find("h2,h3", 0);
        $h2->innertext = $params["title"];
    }

    if ($GLOBALS["__fb_linkedin_resume_profile_version"] == "2015") return "<div class='public-profile'><div id='background'>$education</div></div>";
    return $education;
}

function fb_linkedin_resume_courses($params)
{
    wp_register_style(
        "fb_linkedin_resume",
        fb_linkedin_resume_path . "style.css",
        false,
        fb_linkedin_resume_version,
        "all"
    );
    wp_print_styles("fb_linkedin_resume");

    $resume = fb_linkedin_resume_get_resume($params);
    $education = $resume->find("#background-courses,#profile-courses", 0);
    if (empty($education)) {
        return "";
    }

    if (isset($params["title"])) {
        $h2 = $education->find("h2,h3", 0);
        $h2->innertext = $params["title"];
    }

    if ($GLOBALS["__fb_linkedin_resume_profile_version"] == "2015") return "<div class='public-profile'><div id='background'>$education</div></div>";
    return $education;
}

function fb_linkedin_resume_organizations($params)
{
    wp_register_style(
        "fb_linkedin_resume",
        fb_linkedin_resume_path . "style.css",
        false,
        fb_linkedin_resume_version,
        "all"
    );
    wp_print_styles("fb_linkedin_resume");

    $resume = fb_linkedin_resume_get_resume($params);
    $education = $resume->find("#background-organizations,#profile-organizations", 0);
    if (empty($education)) {
        return "";
    }

    if (isset($params["title"])) {
        $h2 = $education->find("h2,h3", 0);
        $h2->innertext = $params["title"];
    }

    if ($GLOBALS["__fb_linkedin_resume_profile_version"] == "2015") return "<div class='public-profile'><div id='background'>$education</div></div>";
    return $education;
}

function fb_linkedin_resume_certifications($params)
{
    wp_register_style(
        "fb_linkedin_resume",
        fb_linkedin_resume_path . "style.css",
        false,
        fb_linkedin_resume_version,
        "all"
    );
    wp_print_styles("fb_linkedin_resume");

    $resume = fb_linkedin_resume_get_resume($params);
    $certifications = $resume->find("#profile-certifications,#background-certifications", 0);
    if (empty($certifications)) {
        return "";
    }

    if (isset($params["title"])) {
        $h2 = $certifications->find("h2,h3", 0);
        $h2->innertext = $params["title"];
    }

    if ($GLOBALS["__fb_linkedin_resume_profile_version"] == "2015") return "<div class='public-profile'><div id='background'>$certifications</div></div>";
    return $certifications;
}

function fb_linkedin_resume_skills($params)
{
    wp_register_style(
        "fb_linkedin_resume",
        fb_linkedin_resume_path . "style.css",
        false,
        fb_linkedin_resume_version,
        "all"
    );
    wp_print_styles("fb_linkedin_resume");

    $resume = fb_linkedin_resume_get_resume($params);
    $skills = $resume->find("#background-skills,#profile-skills", 0);
    if (empty($skills)) {
        return "";
    }

    $resume->find("#see-more-less-skill", 0)->outertext = "";

    if (isset($params["title"])) {
        $h2 = $skills->find("h2,h3", 0);
        $h2->innertext = $params["title"];
    }

    foreach ($skills->find("li.extra-skill") as $link) {
        $link->class = str_replace("extra-skill", "", $link->class);
    }

    if ($GLOBALS["__fb_linkedin_resume_profile_version"] == "old") {
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
    }

    if ($GLOBALS["__fb_linkedin_resume_profile_version"] == "2015") return "<div class='public-profile'><div id='background'>$skills</div></div>";
    return $skills;
}

function fb_linkedin_resume_publications($params)
{
    wp_register_style(
        "fb_linkedin_resume",
        fb_linkedin_resume_path . "style.css",
        false,
        fb_linkedin_resume_version,
        "all"
    );
    wp_print_styles("fb_linkedin_resume");

    $resume = fb_linkedin_resume_get_resume($params);
    $publications = $resume->find("#background-publications,#profile-publications", 0);
    if (empty($publications)) {
        return "";
    }

    if (isset($params["title"])) {
        $h2 = $publications->find("h2,h3", 0);
        $h2->innertext = $params["title"];
    }

    foreach ($publications->find("li.publication a") as $link) {
        $link->href = "http://www.linkedin.com/{$link->href}";
    }

    foreach ($publications->find("div.attribution a") as $link) {
        $link->outertext = $link->plaintext;
    }

    if ($GLOBALS["__fb_linkedin_resume_profile_version"] == "2015") return "<div class='public-profile'><div id='background'>$publications</div></div>";
    return $publications;
}

function fb_linkedin_resume_languages($params)
{
    wp_register_style(
        "fb_linkedin_resume",
        fb_linkedin_resume_path . "style.css",
        false,
        fb_linkedin_resume_version,
        "all"
    );
    wp_print_styles("fb_linkedin_resume");

    $resume = fb_linkedin_resume_get_resume($params);
    $languages = $resume->find("#background-languages,#profile-languages", 0);
    if (empty($languages)) {
        return "";
    }

    if (isset($params["title"])) {
        $h2 = $languages->find("h2,h3", 0);
        $h2->innertext = $params["title"];
    }

    if ($GLOBALS["__fb_linkedin_resume_profile_version"] == "2015") return "<div class='public-profile'><div id='background'>$languages</div></div>";
    return $languages;
}

function fb_linkedin_resume_honors($params)
{
    wp_register_style(
        "fb_linkedin_resume",
        fb_linkedin_resume_path . "style.css",
        false,
        fb_linkedin_resume_version,
        "all"
    );
    wp_print_styles("fb_linkedin_resume");

    $resume = fb_linkedin_resume_get_resume($params);
    $honors = $resume->find("#background-honors", 0);
    if (empty($honors)) {
        return "";
    }

    if (isset($params["title"])) {
        $h2 = $honors->find("h2,h3", 0);
        $h2->innertext = $params["title"];
    }

    if ($GLOBALS["__fb_linkedin_resume_profile_version"] == "2015") return "<div class='public-profile'><div id='background'>$honors</div></div>";
    return $honors;
}

function fb_linkedin_resume_additional($params)
{
    wp_register_style(
        "fb_linkedin_resume",
        fb_linkedin_resume_path . "style.css",
        false,
        fb_linkedin_resume_version,
        "all"
    );
    wp_print_styles("fb_linkedin_resume");

    $resume = fb_linkedin_resume_get_resume($params);
    $additional = $resume->find("#profile-additional", 0);
    if (empty($additional)) {
        return "";
    }

    if (isset($params["title"])) {
        $h2 = $additional->find("h2,h3", 0);
        $h2->innertext = $params["title"];
    }
    if (isset($params["title_interests"])) {
        $dt = $additional->find("dt.interests", 0);
        $dt->innertext = $params["title_interests"];
    }
    if (isset($params["title_honors"])) {
        $dt = $additional->find("dt.honors", 0);
        $dt->innertext = $params["title_honors"];
    }
    foreach ($additional->find("dd.websites a") as $link) {
        $link->href = "http://www.linkedin.com/{$link->href}";
    }

    if ($GLOBALS["__fb_linkedin_resume_profile_version"] == "2015") return "<div class='public-profile'><div id='background'>$additional</div></div>";
    return $additional;
}

function fb_linkedin_resume_projects($params)
{
    wp_register_style(
        "fb_linkedin_resume",
        fb_linkedin_resume_path . "style.css",
        false,
        fb_linkedin_resume_version,
        "all"
    );
    wp_print_styles("fb_linkedin_resume");

    $resume = fb_linkedin_resume_get_resume($params);
    $projects = $resume->find("#background-projects,#profile-projects", 0);
    if (empty($projects)) {
        return "";
    }

    if (isset($params["title"])) {
        $h2 = $projects->find("h2,h3", 0);
        $h2->innertext = $params["title"];
    }

    foreach ($resume->find("div.attribution a") as $link) {
        $link->href = "http://www.linkedin.com{$link->href}";
    }

    if ($GLOBALS["__fb_linkedin_resume_profile_version"] == "2015") return "<div class='public-profile'><div id='background'>$projects</div></div>";
    return $projects;
}

function fb_linkedin_resume_display_admin_page()
{
    if (isset($_REQUEST['fb_linkedin_resume_url'])) {
        $options = array();
        $options["fb_linkedin_resume_url"] = trim($_REQUEST["fb_linkedin_resume_url"]);
        $options["fb_linkedin_resume_source"] = trim($_REQUEST["fb_linkedin_resume_source"]);
        $options = stripslashes_deep($options);
        update_option(fb_linkedin_resume_admin_options_name, $options);
    }
    $options = fb_linkedin_resume_get_admin_options();

    $cache_warning = null;
    if (!fb_linkedin_create_cache_dir()) {
        $cache_warning = "<p style='color:red;border:1px solid red;padding:10px'>It was not possible to automatically
        create the \"wp-content/cache/fb_linkedin_resume\" directory, please be sure to create it manually before
        continuing.</p>";
    }

    echo <<<EOF
	<div class="wrap">
		<h2>FB LinkedIn Resume options</h2>
		$cache_warning
		<form method="post" action="{$_SERVER["REQUEST_URI"]}">
			Just complete your LinkedIn profile URL,<br />if you want you can make it language aware using username/language syntax.<br />
			If you want you can also type your full profile URL.<br /><br />
			Some examples of valid imput values:<br/>
			<ul style="list-style-type: disc; list-style-position:inside;">
			    <li>yourusername</li>
			    <li>yourusername/en</li>
			    <li>http://www.linkedin.com/in/yourusername</li>
			    <li>http://www.linkedin.com/in/yourusername/en</li>
			</ul>
			http://www.linkedin.com/in/<input type="text" name="fb_linkedin_resume_url" value="{$options["fb_linkedin_resume_url"]}" style="width:300px" /><br /><br />
            <br /><br />
            <h2>Single profile super easy cache</h2>
			If you have problems with the plugin and you receive an error like "Impossible to download your LinkedIn profile"
			you should:
			<ul style="list-style-type: disc; list-style-position:inside;">
			    <li>visit your linkedin profile using an anonymous browser window (or without being logged in)</li>
			    <li>view the source code of the page (check your browser documentation in order to do that)</li>
			    <li>copy all the HTML source code of the page</li>
			    <li>paste it all in the following textarea</li>
			</ul>
			If you fill it, your profile data will be read from this textarea instead of downloaded from LinkedIn every time
			(it will solve the download problem and act as a cache system) but:
			<ul style="list-style-type: disc; list-style-position:inside;">
				<li>Remember that using this textarea will completely disable the "user" param for every shortcode.</li>
				<li>Remember that using this textarea will avoid your website to show multiple linkedin profiles.</li>
				<li>Remember that you'll have to update this textarea if you modify your LinkedIn profile page.</li>
			</ul>
			<textarea type="text" name="fb_linkedin_resume_source" class="large_text" style="min-width:300px" rows="10">{$options["fb_linkedin_resume_source"]}</textarea><br /><br />
			<input type="submit" value="Save" />
		</form>
	</div>
EOF;
}

function fb_linkedin_resume_admin()
{
    add_options_page(
        "FB LinkedIn Resume",
        "FB LinkedIn Resume",
        "manage_options",
        "fb-linkedin-resume",
        "fb_linkedin_resume_display_admin_page"
    );
}

function fb_linkedin_resume_decompress($compressed)
{
    if (false !== ($decompressed = @gzinflate($compressed))) {
        return $decompressed;
    }
    if (false !== ($decompressed = WP_Http_Encoding::compatible_gzinflate($compressed))) {
        return $decompressed;
    }
    if (false !== ($decompressed = @gzuncompress($compressed))) {
        return $decompressed;
    }
    if (function_exists('gzdecode')) {
        $decompressed = @gzdecode($compressed);
        if (false !== $decompressed) {
            return $decompressed;
        }
    }
    return $compressed;
}

function fb_wp_die($message, $resume_url = null)
{
    if (!$resume_url) {
        wp_die($message);
    }

    $html = fb_cache_get($resume_url);
    if ($html) {
        return $html;
    }

    wp_die($message);
}

function fb_cache_get($resume_url)
{
    return @file_get_contents(fb_linkedin_resume_cache_dir . DIRECTORY_SEPARATOR . md5($resume_url));
}

function fb_linkedin_create_cache_dir()
{
    if (is_dir(fb_linkedin_resume_cache_dir)) return true;

    $check = mkdir(fb_linkedin_resume_cache_dir, 0777, true);
    if ($check) return true;

    //wp_die("FB Linkedin Resume: unable to create directory " . fb_linkedin_resume_cache_dir);
    return false;
}

function fb_linkedin_save_cache($options, $linkedin_html)
{
    if (!fb_linkedin_create_cache_dir()) return false;

    $fp = @fopen(fb_linkedin_resume_cache_dir . DIRECTORY_SEPARATOR . md5($options["fb_linkedin_resume_url"]), "w");
    if (!$fp) {
        //wp_die("FB Linkedin Resume: unable to create files in the " . fb_linkedin_resume_cache_dir . " directory");
        return false;
    }
    fwrite($fp, $linkedin_html["body"]);
    fclose($fp);

    return true;
}