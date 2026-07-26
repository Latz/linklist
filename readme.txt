=== LinkList ===
Contributors: Lutz Schroeer
Tags: links
Requires at least: 3.7
Tested up to: 4.7.4
Stable tag: trunk
Author: Lutz Schroeer
Version: 0.5
Author URI: http://elektroelch.de/blog/
License: GPL

== Description ==

LinkList adds a list of mentioned links at the end of the post, page or feed.

== Installation ==

1. Download the plugin and unzip it.
2. Upload the folder lonklist/ to your /wp-content/plugins/ folder.
3. Activate the plugin from your WordPress admin panel.
4. Installation finished.

== Usage ==
Linklist automatically puts a list of all link at the end of the post or page. If you want to exclude an
individual page/post from displaying the list you can de-select the "Display Linklist" checkbox on the
left side of the edit screen.

If you want to change the display of the link list for an already existing post/page you can use
the Quick Edit or Bulk Edit option.

== Settings ==
LinkList provides a varietey of settings to tweak the list to your needs. The settings are
divided into three parts (posts, pages and feeds).

= General settings =
Here you can define if the linklist should be display on posts, pages and/or feeds at all.


= Posts settings =
* Content to put in front of list
Text to be displayed in front of the linklist

* Style of list
You can choose between three different styles:
- Ordered list:
    1. link
    2. link
    3. link
    4. ...
   
- Unordered list
    * link
    * link
    * link
    * ...
   
- Char separated list
    link, link, link, link
   
  The separating character is defined in "Separator character(s)"
  
* Separating character(s)
Character(s) used to separate the links if "char separated list" is chosen above.

* Minimum links
Minimum number of links mentioned in the post for the list to be displayed.

* Sorting
Sort the links alphabetically. This function has an issue with international characters (e.g.
German umlauts).

* More tag
Prevents the display of the link list on the main blog page.

* Single post
Prevents the display of the link list on the main blog page.

* Last page only
If you have devided your post into several parts using <!--nextpage--> the link list is only displayed on the last page.
This list will contain all links of the post. If the list is displayed at the end of every part only the links of that
part are displayed. The settings of "Minimum links" applies to every part separately.

* Exceptions
You can except divs from being harvested for links. Enter a comma separated kist of divs to be excluded.

* Priority
There are many other plugins messing around with the post content. By altering the priority of the LinkList you can  change the position where the list appearts. 1 means high priority, 20 means low priority, default is 10.

= Pages settings =

* Content to put in front of list
Text to be displayed in front of the linklist

* Style of list
You can choose between three different styles:
- Ordered list:
    1. link
    2. link
    3. link
    4. ...
   
- Unordered list
    * link
    * link
    * link
    * ...
   
- Char separated list
    link, link, link, link
   
  The separating character is defined in "Separator character(s)"
  
* Separating character(s)
Character(s) used to separate the links if "char separated list" is chosen above.

* Minimum links
Minimum number of links mentioned in the post for the list to be displayed.

* Sorting
Sort the links alphabetically. This function has an issue with international characters (e.g.
German umlauts).

* Last page only
If you have devided your post into several parts using <!--nextpage--> the link list is only displayed on the last page.
This list will contain all links of the post. If the list is displayed at the end of every part only the links of that
part are displayed. The settings of "Minimum links" applies to every part separately.


= Feed settings =
* Content to put in front of list
Text to be displayed in front of the linklist

* Style of list
You can choose between three different styles:
- Ordered list:
    1. link
    2. link
    3. link
    4. ...
   
- Unordered list
    * link
    * link
    * link
    * ...
   
- Char separated list
    link, link, link, link
   
  The separating character is defined in "Separator character(s)"
  
* Separating character(s)
Character(s) used to separate the links if "char separated list" is chosen above.

* Minimum links
Minimum number of links mentioned in the post for the list to be displayed.

* Sorting
Sort the links alphabetically. This function has an issue with international characters (e.g.
German umlauts).

= Styling LinkList =
You can style the link list with CSS:

<div class="linklist">
  <span class="linklistheader">
    Content to put in front of list
  </span>
</div>

= Filter =
You can programmatically change the content of the linklist by adding a filter:

<?php
  add_filter('linklist', 'my_linklist');

  function my_linklist($list) {
    [...]
  }


== Credits ==
* Joost de Valk (Yoast) for his plugin tools (yst_plugin_tools.php). http://yoast.com/
* Tami Mize for assuming display option for individual posts or pages

      
== Changelog ==
= v0.5 =
+ Added display option for individual posts (inl. Quick and Bulk edit)


= v0.4 =
+ Added possibility to except divs in content from link harvesting
+ Added option to seet priority of LinkList

= v0.3 =
* Fixed "Strict standards" notice in PHP 5.5
* Checked for 3.7 compatibility

= v0.2 =
+ Added: filter for link list (11 SEP 2012)

= v0.1 =
Initial release (15 AUG 2009)
