=== Plugin Name ===
Contributors: Fabrizio Balliano
Donate link: http://fabrizioballiano.net/fb-linkedin-resume/
Tags: linkedIn, resume, CV, curriculum vitae, curriculum, vitae
Requires at least: 2.9.0
Tested up to: 3.1.2
Stable tag: 1.1

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
* fb_linkedin_resume_languages: prints the "languages" section.
* fb_linkedin_resume_education: prints the "education" section.
* fb_linkedin_resume_additional: prints the "additional" section.

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

== Installation ==

1. Download
2. Install
3. Edit plugin options providing your LinkedIn profile URL
4. Insert all the shortcode you want in your pages/posts
5. Done

== Upgrade Notice ==

Due to the implementation, there should be no problems during future upgrades.

== Frequently Asked Questions ==

= How the LinkedIn DOM parsing is done? =

Thanks to the great simplehtmldom library:
http://sourceforge.net/projects/simplehtmldom/
released under the MIT license.

== Screenshots ==

1. WordPress backend, inserting LinkedIn profile parts in your pages.
2. A part of the resulting page

== Changelog ==

= 1.1 =
* lang param was added to all shortcodes.

= 1.0 =
* First public release.

== Copyright ==

Copyright 2011 Fabrizio Balliano (email: fabrizio@fabrizioballiano.it)
