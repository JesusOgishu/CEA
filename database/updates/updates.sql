-- creacion de la tabla at_users
CREATE TABLE IF NOT EXISTS `bd_cea_dev`.`at_users` (
  `uid` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(75) NOT NULL,
  `email` VARCHAR(75) NOT NULL,
  `password` VARCHAR(75) NOT NULL,
  `status` VARCHAR(25) NOT NULL DEFAULT 'ACTIVO',
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `date_update` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  `asana_id` VARCHAR(155) NULL DEFAULT NULL,
  `acces_token` VARCHAR(155) NULL DEFAULT NULL,
  `refresh_token` VARCHAR(155) NULL DEFAULT NULL,
  PRIMARY KEY (`uid`),
  UNIQUE INDEX `asana_id_UNIQUE` (`asana_id` ASC))

ALTER TABLE `bd_cea_dev`.`at_users` 
DROP COLUMN `password`;