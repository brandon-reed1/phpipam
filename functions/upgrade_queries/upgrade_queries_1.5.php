<?php

#
# Subversion 1.5 queries
#

// fix for postcode
$upgrade_queries["1.5.26"][] = "ALTER TABLE `customers` CHANGE `postcode` `postcode` VARCHAR(32)  NULL  DEFAULT NULL;";
$upgrade_queries["1.5.26"][] = "-- Database version bump";
$upgrade_queries["1.5.26"][] = "UPDATE `settings` set `dbversion` = '26';";