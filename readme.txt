﻿=== Plugin Name ===
Contributors: Fabrizio Balliano
Donate link: http://fabrizioballiano.net/fb-linkedin-resume/
Tags: linkedIn, resume, CV, curriculum vitae, curriculum, vitae
Requires at least: 2.9.0
Tested up to: 4.1
Stable tag: 3.0.0

Publish all your LinkedIn public profile (or just some selected parts)
on your blog.
With custom language and translations support.

== Description ==

This plugin uses a new way of processing your LinkedIn profile to extract all
the information you need to publish on your blog.
Using HTML DOM parsing we avoid all the possible bugs and malfunctions due to
regular expression parsing, this way we're way more sure that our plugin is
absolutely resistant to all the LinkedIn HTML changes that could occur in
the future.

It also outputs the LinkedIn HTML parts instead of creating new HTML, this way
we're sure that it supports and will support all possible LinkedIn options
for every part of your profile. 

It features two ways of usage:

1. Just print out the whole profile without customizations.
2. Extract single parts of your profile and print each one where you want.

Supported shortcodes:

* fb_linkedin_resume_full: prints all your LinkedIn public profile.
* fb_linkedin_resume_header: prints the upper badge.
* fb_linkedin_resume_summary: prints the "summary" section.
* fb_linkedin_resume_experience: prints the "experience" section.
* fb_linkedin_resume_certifications: prints the "certifications" section.
* fb_linkedin_resume_skills: prints the "skills" section.
* fb_linkedin_resume_publications: prints the "publications" section.
* fb_linkedin_resume_languages: prints the "languages" section.
* fb_linkedin_resume_education: prints the "education" section.
* fb_linkedin_resume_courses: prints the "courses" section.
* fb_linkedin_resume_organizations: prints the "organizations" section.
* fb_linkedin_resume_projects: prints the "projects" section.
* fb_linkedin_resume_additional: prints the "additional" section (deprecated, only works with LinkedIn's old profiles).
* fb_linkedin_resume_honors: prints the "honors" section.

Every shortcode accept a "user" parameter that will allow you to override
default profile username (also if set in the plugin options) thus you can
output multiple profiles on a single wordpress installation or a single page.

Every shortcode accept a "lang" parameter that will allow you to override
default profile language (also if set in the plugin options).

Every shortcode (except the "full" one) accept a "title" parameter that will
allow you to customize the section title (translating it or changing it the
way you want).
Eg: [fb_linkedin_resume_experience title="Esperienze lavorative"].

The "additional" shortcode supports more parameters (title, title_interests,
title_honors), eg:
[fb_linkedin_resume_additional title="Informazioni aggiuntive"
title_interests="Interessi" title_honors="Premi e riconoscimenti"].

Notes:
* when using a full profile URL instead of a profile username, the "lang"
  param won't work!

== Installation ==

1. Download
2. Install
3. Edit plugin options providing your LinkedIn profile URL
4. Insert all the shortcode you want in your pages/posts
5. Done

== Upgrade Notice ==

= 2.* to 3.0.0 =

If you've the new LinkedIn profile active then be careful when updating, all the CSS are different and you
will need to rework your CSS at least a bit. I've tried to make everything as general as possible but you'll
need to check it.

== Frequently Asked Questions ==

= Where is the LinkedIn's HTML saved in cache =

In the wp-content/cache/fb_linkedin_resume folder.

= How is the LinkedIn's HTML saved in cache =

Every profile's HTML is saved as a different cache file.
The file name is the md5 of the profile url (always use http and not https).
If you want to manually save a cache file be sure to browse
your LinkedIn profile in an anonymous browser window (or
anyway not being logged in).

= Error downloading your LinkedIn profile =

Since 2014 LinkedIn introduces a lot of restrictions so it's really difficult
to download public profiles without troubles, we've an internal cache to try
to workaround the limitations but if you cannot download the profile than
the only way is to generate the cache file by yourself (check the other FAQs
to know how) or to use the "single profile super easy cache" (option available
in the plugin' settings page.

= How the LinkedIn DOM parsing is done? =

Thanks to the great simplehtmldom library:
http://sourceforge.net/projects/simplehtmldom/
released under the MIT license.

== Screenshots ==

1. WordPress backend, inserting LinkedIn profile parts in your pages.
2. A part of the resulting page

== Changelog ==

= 3.0.0 =
* major rewrite
* support for new LinkedIn profiles
* added shortcode for "honors"

= 2.9.3 =
* Small tweaks and refinements.

= 2.9.2 =
* connection problems with LinkedIn should be finally solved.

= 2.9.1 =
* some notices/warnings ere removed.
* cache directory detection was changed to avoid problems with symlinked filesystems.
* some caching functions were rewritten.
* a cache dir creation warning was added to the admin config panel.

= 2.9.0 =
* A new cache system was implemented, saving LinkedIn's HTML in the wordpress' cache
  directory, thus it should avoid temporary slow-downs/problems between your server
  and LinkedIn's ones.

= 2.8.1 =
* A bug with "projects" section was solved (links to other project members' profile).

= 2.8.0 =
* If the plugin has problems downloading your LinkedIn profile now you can
  go to the plugin' settings page and paste the HTML source of your LinkedIn
  profile. It will also act as a cache.
* DOM parsing library was updated to version 1.5

= 2.7.5 =
* LinkedIn profile download routine updated, trying to solve the problem
  between some hosts and some LinkedIn profiles

= 2.7.4 =
* Support for "projects" section was added.
* a little bugfix about double slash was fixed (thanks to "gav")

= 2.7.3 =
* A desktop user-agent was forced when downloading LinkedIn profile.

= 2.7.2 =
* LinkedIn links were removed from "header" section

= 2.7.1 =
* error message is now more verbose.

= 2.7 =
* accept-language header is automatically sent to linkedin, according to the
  "lang" parameter
* fb_linkedin_resume_courses shortcode was added.
* fb_linkedin_resume_organizations shortcode was added.

= 2.6 =
* skills are now printed with the proficiency (you've to mouse over the skill
  to see it)

= 2.5 =
* simplehtmldom library is now included only if the str_get_html function
  does not exist (avoiding conflicts with other plugins).
* a bug with the new skill style was fixed.

= 2.4 =
* complete profile URL is not supported instead of the username, thus it should solve all
  problems with people with strange profile urls.
* LinkedIn gzdeflated content support was added and double error checked.
* skills are now rendered as a sort of "tag cloud" like the new linkedn design.
* profile name replacement routines were rewritten cause some users have double spaces
  between name and surname.

= 2.3 =
* WordPress' internal function "wp_remote_get" is now used to download LinkedIn's page, this should
  support a lot of connection methods, not just only the old file_get_contents

= 2.2 =
* redirects to external websites were fixed.

= 2.1 =
* fb_linkedin_resume_publications shortcode was added.

= 2.0 =
* "user" param was added to all shortcodes to let you parse multiple profiles
* "skills" shortcode now removes links to single skill page (which has links to other users and anyway was wrong)
* fb_linkedin_resume_full() function was completely rewrote
* some error checks were added

= 1.3 =
* plugin url was fixed.

= 1.2 =
* fb_linkedin_resume_skills shortcode was added.

= 1.1 =
* lang param was added to all shortcodes.

= 1.0 =
* First public release.

== Copyright ==

Copyright Fabrizio Balliano (email: fabrizio@fabrizioballiano.it)
