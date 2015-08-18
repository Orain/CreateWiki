CreateWiki
==========

A MediaWiki extension for creating wikis.

Developed by Southparkfan, Kudu & Addshore.
Based on the CheckUser extension by Tim Starling and Aaron Schulz.

#### Configuration Example
    $wgCreateWikiPublicDbListLocation = "/srv/foo/dblist.public";
    $wgCreateWikiPrivateDbListLocation = "/srv/foo/dblist.private";
    $wgCreateWikiBaseDomain = 'orain.org'
    $wgCreateWikiUseCloudFlare = true;
    $wgCloudFlareUser = 'foo';
    $wgCloudFlareKey = 'bar';

#### Notes
 - This extension requires php to be loacted at */usr/bin/php*.
 - This extension requires the mediawiki DB user to have *CREATE DATABASE* permissions.

 - This extension requires the CentralAuth extension to be installed.
 - This extension requires the AntiSpoof extension to be installed.
 - This extension requires the AbuseFilter extension to be installed.
 - This extension requires the CheckUser extension to be installed.

 #### TODOS
  - Optionally update a local version / onwiki version of the dblist
  - Re add cloudflare support