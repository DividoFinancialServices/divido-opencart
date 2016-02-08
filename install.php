<?php

// Install DB table to keep product specific settings
$this->db->query("
    create table if not exists " . DB_PREFIX . "product_divido ( 
        product_id int(11) not null, 
        display char(7) not null, 
        plans text, 
        primary key(product_id)
    ) engine=MyISAM default charset=utf8 collate=utf8_general_ci");
