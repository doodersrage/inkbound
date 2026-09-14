WordPress.org plugin directory assets
=====================================

Upload the files in this folder to the plugin SVN `assets/` directory
(not inside the plugin zip / trunk tag):

  banner-772x250.png
  banner-1544x500.png
  icon-128x128.png
  icon-256x256.png
  screenshot-1.png … screenshot-4.png

After approval, example:

  svn co https://plugins.svn.wordpress.org/inkbound inkbound-svn
  cp .wordpress-org/* inkbound-svn/assets/
  svn add inkbound-svn/assets/*
  svn ci -m "Add directory assets"

Submission checklist (owner account)
------------------------------------
1. Enable 2FA on your WordPress.org account.
2. Confirm Contributors in readme.txt matches your .org username.
3. Run Plugin Check (Plugin Repo category) and fix blockers.
4. Build the zip with ./bin/build-zip.sh (excludes bin/, .git, .wordpress-org).
5. Submit at https://wordpress.org/plugins/developers/add/
