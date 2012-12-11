# The configuration table.
CREATE TABLE `pdeploy_deployment_config` (
  `key` VARCHAR(255) NOT NULL,
  `value` VARCHAR(255) NOT NULL,
  `created` DATETIME NOT NULL,
  `modified` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) DEFAULT CHARSET=utf8;

# The log table.
CREATE TABLE `pdeploy_deployment_log` (
  `when` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `message` text NOT NULL
) DEFAULT CHARSET=utf8;

# Seed data.
INSERT INTO `pdeploy_deployment_config` (`key`, `value`, `created`, `modified`) VALUES ('version', '0', NOW(), CURRENT_TIMESTAMP);
INSERT INTO `pdeploy_deployment_config` (`key`, `value`, `created`, `modified`) VALUES ('created', NOW(), NOW(), CURRENT_TIMESTAMP);
INSERT INTO `pdeploy_deployment_config` (`key`, `value`, `created`, `modified`) VALUES ('modified', NOW(), NOW(), CURRENT_TIMESTAMP);
