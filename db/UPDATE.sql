/* VERSION 1.4.0 */
UPDATE `settings` set `version` = '1.4';
UPDATE `settings` set `dbversion` = '0';

/* VERSION 1.4.1 */
UPDATE `settings` set `version` = '1.4';
UPDATE `settings` set `dbversion` = '1';
UPDATE `settings` set `dbverified` = 0;

-- Add password policy
ALTER TABLE `settings` ADD `passwordPolicy` TEXT default '{"minLength":8,"maxLength":0,"minNumbers":0,"minLetters":0,"minLowerCase":0,"minUpperCase":0,"minSymbols":0,"maxSymbols":0,"allowedSymbols":"#,_,-,!,[,],=,~"}';
