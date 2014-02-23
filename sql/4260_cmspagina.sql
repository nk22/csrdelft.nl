ALTER TABLE `pagina` ENGINE = InnoDB;
ALTER TABLE `pagina` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `pagina` CHANGE `naam` `naam` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE `pagina` CHANGE `titel` `titel` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE `pagina` CHANGE `inhoud` `inhoud` LONGTEXT NOT NULL ;
ALTER TABLE `pagina` ADD `laatst_gewijzigd` DATETIME NOT NULL AFTER `menu` ;
ALTER TABLE `pagina` DROP `menu` ;
ALTER TABLE `pagina` CHANGE `rechten_bekijken` `rechten_bekijken` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE `pagina` CHANGE `rechten_bewerken` `rechten_bewerken` VARCHAR( 255 ) NOT NULL ;
UPDATE `csrdelft`.`pagina` SET `rechten_bewerken` = 'P_ADMIN,!groep:bestuur' WHERE `pagina`.`naam` = 'thuis';