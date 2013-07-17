Readme file for the MUSICAL INTERVAL question type
===============================================

A plugin for the learning management system moodle to provide population of a
table with localist event data.

- @package    qtype
- @subpackage musicinterval
- @copyright  2013 Jay Huber <jhuber@colum.edu> for Moodle 2.x
- @copyright  2013 Eric Brisson <ebrisson@winona.edu> for Moodle 1.x and Flash Component
- @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


PLEASE NOTE - A NEW VERSION OF THIS PLUGIN WILL BE RELEASED VERY SOON.
              IT WILL USE VEXFLOW (http://vexflow.com/) HTML5/CANVAS/JQUERY 
              AND REMOVE THE FLASH PLUGIN


Description
-----------
This question type deals with musical scales. The respondent is given a note and is prompted to enter the degrees of the scale. Major scales as well as all types of minor scales (natural, harmonic and melodic) are supported, with or without a key signature. Answers are entered in a graphical user interface.

Import/Export to Moodle XMl format is supported, and a question bank is provided, including all major and minor scales, with and without a key signature.

This plugin is released under the GNU General Public License V3. 

Maintainer: Eric Brisson (ebrisson at winona.edu), Moodle 1.x & Flash Component
Maintainer: Jay Huber (jhuber@colum.edu), Moodle 2.x


Installation
------------
Requirements:

1) 	Moodle 2.3.x
	The plug-in might work with previous versions of 2, but has only been tested with this version.

2) 	PHP 5: the plug-in was coded with version 5.2.9. It hasn't been tested with ealier versions of PHP.

3)	Javascript is used to for communication with the Flash input component, and must be enabled for the question type to work.

How to install:

1) Copy the "musicinterval" folder into the following folder: moodle/question/type. 
2) Load the "Notifications" page on the Moodle home page - this will create database tables used by the question type.



Code Location
-------------
You can always find the latest version at: https://github.com/jayhuber/musicinterval
Moodle plugins will notify you as I update the code on Moodle.org


Bug Reports
-----------
Report all bugs on https://github.com/jayhuber/musicinterval/issues


Changelog
---------
v2013071700 - release v1.2 Stable
- Updated this readme file
- Removed import/export overrides which should fix the issue of importing/exporting data
