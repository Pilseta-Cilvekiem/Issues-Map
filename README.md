# Issues Map

## Overview
Issues Map is a Wordpress plugin for managing urban infrastructure issues.
It was created by Tim Brogden as a volunteer project for the activist group 
Pilsēta cilvēkiem [City For People] who are based in Rīga, Latvia.
Users can submit issues, upload images, set locations for them, and 
view them in a list or on a map. Issue locations can be determined
automatically from the GPS data of the images. The list and map views can be
filtered by issue category and status. Users can create formal issue reports
and send them by email (for example, to the relevant authorities).
The plugin supports both registered and anonymous users and provides settings
to control what each is authorised to do. A list of moderators can be provided. 
The essential privacy model is that issues are publicly visible whereas 
issue reports are only visible to their authors and to moderators.

## Getting started
To install the plugin on your Wordpress site:
- Download and unzip the latest version of the plugin.
- Copy the issues-map directory into your wp-content/plugins directory.
- Activate the Issues Map plugin in the Wordpress admin area.

When you have installed the plugin:
- Go to the Issues Map -> Settings page and enter a valid Google Maps API key.
- If you wish to allow issue reports to be emailed, enter a valid moderator email
address. Otherwise, leave the moderator email field blank.
- Adjust any other plugin settings as you wish.

You will notice that the plugin has created three pages for you: Issues List, 
Issues Map and Submit Issue. You can browse to these and start using the plugin's
functionality. You can add links to these pages in your site's navigation menus
as you wish.

## Issue categories
Issues can be assigned to an issue category which has a name, icon and colour.
By default, a single issue category 'Uncategorized' is provided. You can create 
further issue categories using the Issues Map -> Issue categories page. Categories 
are hierarchical with support for two levels, e.g. 
```
Category 1
  Subcategory 1.1
  Subcategory 1.2
  Subcategory 1.3
Category 2
Category 3
  Subcategory 3.1
```

## Issues statuses
By default, issues can have one of three statuses: Unreported, Report Created or 
Report Sent. A basic workflow is automatically built in to the plugin so that
new issues are given the status 'Unreported' (which is not displayed, for stylistic
reasons). When the first issue report is created for an issue, the issue status 
transitions to 'Report created'. When a report is sent by email, the status
transitions to 'Report sent'. You can also change the status of an issue manually
using the 'Edit details' link for an issue. You can add additional issue statuses
using the Issues Map -> Issue statuses page in the Wordpress admin area. You can
edit or delete the three default issue statuses if you wish, however changing their
slug values or deleting them will prevent the automatic workflow described above 
from working so users will need to set issue statuses manually.

## Report templates
The Issues Map -> Report templates page allows authorised users to create 
templates for different issue categories. These allow standard report content 
to be specified that will be used to pre-populate issue reports, thereby
saving the user from having to enter repetitive information.
Placeholder fields such as {issue_title} and {user_full_name} 
can be included in report templates to dynamically pre-populate 
reports with issue or user-specific information. Note that only one template
per issue category is supported. (If more than one is created, the first one
found will be used.)

## Shortcode options
By default, the content of the Issues List, Issues Map and Submit Issue pages
created by the plugin is generated automatically for you, so you don't need 
to add a shortcode to any pages or posts. However, if you would like 
more control over the content of these pages, untick the 
'Automatically generate content' option in the Issues Map -> Settings page.
You can now edit the three plugin pages as you wish, or delete them
and use your own pages instead.
To include the issues list in a page or post, use the shortcode:
```
[issues-map view="list"]
```
To include the issues map in a page or post, use the shortcode:
```
[issues-map view="map"]
```
To include the Submit Issue form in a page or post, use the shortcode:
```
[issues-map view="add-issue"]
```
If you use pages of your own, remember to specify them in the Page settings 
of the Issues Map -> Settings page.
To add an individual issue to a page or post, use the shortcode:
```
      [issues-map id="<issue_id>" view="issue"]
E.g., [issues-map id="42" view="issue"]
```
where <issue_id> is the ID of the issue you wish to display.
To add an individual issue report to a page or post, use the shortcode:
```
      [issues-map id="<report_id>" view="report"]
E.g., [issues-map id="43" view="report"]
```
where <report_id> is the ID of the issue report you wish to display.
Bear in mind that the content of issue reports is only visible to their
authors and to moderators.

## Uninstallation
The plugin can be deactivated and deleted in the normal way within the Wordpress 
admin area. Note that deleting the plugin will delete all associated content
(including issues, issue images, issue reports, issue report PDFs, issue categories and statuses,
report templates and settings for the plugin). The three default pages created by 
the plugin will also be deleted if they have not been changed and have not been 
set to be the site's home page or blog posts page.

## Troubleshooting

In the event of any problems with the Google maps displayed by the plugin:
- Check that you have entered a valid Google Maps API key in the 
Issues Map -> Settings page, ensure that Maps Javascript API and Maps Static API
are enabled for your key, and ensure that any access restriction or quota settings for it are appropriate.

In the event that the image uploading functionality does not work:
- Check that the plugin successfully created the directory wp-content/uploads/issues-map
and that the user account your site runs under has permissions to write to that directory.
- Check that the maximum file upload size of your PHP installation is sufficiently high.
You may have to edit the post_max_size and upload_max_filesize settings in php.ini, e.g.:
```
post_max_size = 10M
upload_max_filesize = 10M
```

In the event that the URLs of any of the plugin's pages do not work:
- Try refreshing the permalinks by clicking Save Changes in the 
Settings -> Permalinks page of the Wordpress admin area.

In the event that issue reports cannot be downloaded as PDF files:
- Delete the four cache files of the form *.mtx.php from the wp-content\plugins\issues-map\tfpdf\font\unifont directory. 
(These store absolute file paths which will be invalid if uploaded to a different server.)
- Ensure that the wp-content\plugins\issues-map\tfpdf\font\unifont directory has Read-Write permissions.
- Check that the mbstring php extension is installed in your PHP installation (look in phpMyAdmin or PHPInfo).

In the event that the issue report sending (emailing) functionality does not work:
- Check that sendmail is correctly configured on your web server.
For example, you may need to specify valid SMTP settings in your sendmail.ini 
and php.ini files or use an appropriate SMTP plugin such as WP Mail SMTP.
In order to send issue reports, a valid moderator email address must also be specified 
in the plugin settings as reports are emailed using that address as the sender.
If you do not require the report sending functionality, you can leave the 
moderator email address blank.

## Future improvements
Some possible future improvements to this plugin are:
- Electronic signature of generated PDF files.
- Upvoting of issues.
- Further moderation functionality (e.g., anonymous submissions require approval).
- Further privacy handling (e.g., issue reports periodically deleted or archived).
- Display some issue status statistics.
- Integrate address lookup service, e.g., the Google Maps Geolocation API or Places API.

Best wishes,
Tim
