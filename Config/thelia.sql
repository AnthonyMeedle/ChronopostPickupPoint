
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- address_chronopost_pickup_point
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `address_chronopost_pickup_point`;

CREATE TABLE `address_chronopost_pickup_point`
(
    `id` INTEGER NOT NULL,
    `title_id` INTEGER NOT NULL,
    `company` VARCHAR(255),
    `firstname` VARCHAR(255) NOT NULL,
    `lastname` VARCHAR(255) NOT NULL,
    `address1` VARCHAR(255) NOT NULL,
    `address2` VARCHAR(255) NOT NULL,
    `address3` VARCHAR(255) NOT NULL,
    `zipcode` VARCHAR(10) NOT NULL,
    `city` VARCHAR(255) NOT NULL,
    `country_id` INTEGER NOT NULL,
    `code` VARCHAR(10) NOT NULL,
    `type` VARCHAR(10) NOT NULL,
    `cellphone` VARCHAR(20),
    PRIMARY KEY (`id`),
    INDEX `fi_address_chronopost_pickup_point_customer_title_id` (`title_id`),
    INDEX `fi_address_chronopost_pickup_point_country_id` (`country_id`),
    CONSTRAINT `fk_address_chronopost_pickup_point_customer_title_id`
        FOREIGN KEY (`title_id`)
        REFERENCES `customer_title` (`id`)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,
    CONSTRAINT `fk_address_chronopost_pickup_point_country_id`
        FOREIGN KEY (`country_id`)
        REFERENCES `country` (`id`)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- chronopost_pickup_point_order
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `chronopost_pickup_point_order`;

CREATE TABLE `chronopost_pickup_point_order`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `order_id` INTEGER NOT NULL,
    `delivery_type` TEXT,
    `delivery_code` TEXT,
    `label_directory` TEXT,
    `label_number` TEXT,
    PRIMARY KEY (`id`),
    INDEX `fi_chronopost_pickup_point_order_order_id` (`order_id`),
    CONSTRAINT `fk_chronopost_pickup_point_order_order_id`
        FOREIGN KEY (`order_id`)
        REFERENCES `order` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- chronopost_pickup_point_delivery_mode
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `chronopost_pickup_point_delivery_mode`;

CREATE TABLE `chronopost_pickup_point_delivery_mode`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255),
    `code` VARCHAR(55) NOT NULL,
    `freeshipping_active` TINYINT(1),
    `freeshipping_from` FLOAT,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- chronopost_pickup_point_price
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `chronopost_pickup_point_price`;

CREATE TABLE `chronopost_pickup_point_price`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `area_id` INTEGER NOT NULL,
    `delivery_mode_id` INTEGER NOT NULL,
    `weight_max` FLOAT,
    `price_max` FLOAT,
    `franco_min_price` FLOAT,
    `price` FLOAT NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `fi_chronopost_pickup_point_price_area_id` (`area_id`),
    INDEX `fi_chronopost_pickup_point_price_delivery_mode_id` (`delivery_mode_id`),
    CONSTRAINT `fk_chronopost_pickup_point_price_area_id`
        FOREIGN KEY (`area_id`)
        REFERENCES `area` (`id`)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,
    CONSTRAINT `fk_chronopost_pickup_point_price_delivery_mode_id`
        FOREIGN KEY (`delivery_mode_id`)
        REFERENCES `chronopost_pickup_point_delivery_mode` (`id`)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- chronopost_pickup_point_area_freeshipping
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `chronopost_pickup_point_area_freeshipping`;

CREATE TABLE `chronopost_pickup_point_area_freeshipping`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `area_id` INTEGER NOT NULL,
    `delivery_mode_id` INTEGER NOT NULL,
    `cart_amount` DECIMAL(16,6) DEFAULT 0.000000,
    PRIMARY KEY (`id`),
    INDEX `fi_chronopost_pickup_point_area_freeshipping_area_id` (`area_id`),
    INDEX `fi_chronopost_pickup_point_area_freeshipping_delivery_mode_id` (`delivery_mode_id`),
    CONSTRAINT `fk_chronopost_pickup_point_area_freeshipping_area_id`
        FOREIGN KEY (`area_id`)
        REFERENCES `area` (`id`)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,
    CONSTRAINT `fk_chronopost_pickup_point_area_freeshipping_delivery_mode_id`
        FOREIGN KEY (`delivery_mode_id`)
        REFERENCES `chronopost_pickup_point_delivery_mode` (`id`)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
