<?php
$this->db->query("
    CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "c8UMbuNcJ4_product` (
      `product_id` INT(11) NOT NULL,
      `display` CHAR(7) NOT NULL,
      `plans` text,
      PRIMARY KEY (`product_id`)
    ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");

$this->db->query("
    CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "c8UMbuNcJ4_lookup` (
      `order_id` INT(7) NOT NULL,
      `salt` CHAR(64) NOT NULL,
      PRIMARY KEY (`order_id`)
    ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
