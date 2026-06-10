SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `hotel_mgmt` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hotel_mgmt`;

DROP TABLE IF EXISTS `Cleaning_task`;
CREATE TABLE `Cleaning_task` (
  `task_id` int(11) NOT NULL COMMENT '任务ID',
  `room_id` varchar(50) NOT NULL COMMENT '房间ID',
  `customer_id` varchar(50) DEFAULT NULL COMMENT '顾客ID(发起清洁请求的顾客)',
  `cleaner_id` varchar(50) DEFAULT NULL COMMENT '清洁员ID(负责清洁的员工)',
  `task_status` enum('pending','in-progress','completed','cancelled') NOT NULL DEFAULT 'pending' COMMENT '任务状态',
  `wish_time` datetime DEFAULT NULL COMMENT '期望完成时间',
  `actual_start_time` datetime DEFAULT NULL COMMENT '实际开始时间',
  `actual_end_time` datetime DEFAULT NULL COMMENT '实际结束时间',
  `notes` text DEFAULT NULL COMMENT '备注',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='清洁任务表';

DROP TABLE IF EXISTS `comment`;
CREATE TABLE `comment` (
  `comment_id` int(11) NOT NULL COMMENT '评论ID',
  `type_id` varchar(50) NOT NULL COMMENT '房型ID',
  `user_id` varchar(50) NOT NULL COMMENT '用户ID',
  `score` int(11) NOT NULL COMMENT '评分(1-5)',
  `text` text DEFAULT NULL COMMENT '评论内容',
  `comment_time` datetime DEFAULT current_timestamp() COMMENT '评论时间'
) ;
DROP TRIGGER IF EXISTS `trg_update_roomtype_score_after_delete`;
DELIMITER $$
CREATE TRIGGER `trg_update_roomtype_score_after_delete` AFTER DELETE ON `comment` FOR EACH ROW BEGIN
    -- 更新房型评分
    UPDATE `Room_Type` 
    SET `score` = COALESCE((SELECT ROUND(AVG(`score`), 2) FROM `Comment` WHERE `type_id` = OLD.`type_id`), 0.00)
    WHERE `type_id` = OLD.`type_id`;
    
    -- 更新酒店总评分
    UPDATE `Hotel` 
    SET `score` = (SELECT ROUND(AVG(`score`), 2) FROM `Room_Type` WHERE `score` > 0);
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_update_roomtype_score_after_insert`;
DELIMITER $$
CREATE TRIGGER `trg_update_roomtype_score_after_insert` AFTER INSERT ON `comment` FOR EACH ROW BEGIN
    -- 更新房型评分
    UPDATE `Room_Type` 
    SET `score` = (SELECT ROUND(AVG(`score`), 2) FROM `Comment` WHERE `type_id` = NEW.`type_id`)
    WHERE `type_id` = NEW.`type_id`;
    
    -- 更新酒店总评分
    UPDATE `Hotel` 
    SET `score` = (SELECT ROUND(AVG(`score`), 2) FROM `Room_Type` WHERE `score` > 0);
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_update_roomtype_score_after_update`;
DELIMITER $$
CREATE TRIGGER `trg_update_roomtype_score_after_update` AFTER UPDATE ON `comment` FOR EACH ROW BEGIN
    -- 如果type_id改变，更新旧房型评分
    IF OLD.`type_id` != NEW.`type_id` THEN
        UPDATE `Room_Type` 
        SET `score` = COALESCE((SELECT ROUND(AVG(`score`), 2) FROM `Comment` WHERE `type_id` = OLD.`type_id`), 0.00)
        WHERE `type_id` = OLD.`type_id`;
    END IF;
    
    -- 更新新房型评分
    UPDATE `Room_Type` 
    SET `score` = (SELECT ROUND(AVG(`score`), 2) FROM `Comment` WHERE `type_id` = NEW.`type_id`)
    WHERE `type_id` = NEW.`type_id`;
    
    -- 更新酒店总评分
    UPDATE `Hotel` 
    SET `score` = (SELECT ROUND(AVG(`score`), 2) FROM `Room_Type` WHERE `score` > 0);
END
$$
DELIMITER ;

DROP TABLE IF EXISTS `Hotel`;
CREATE TABLE `Hotel` (
  `hotel_id` varchar(50) NOT NULL COMMENT '酒店ID',
  `hotel_name` varchar(200) NOT NULL COMMENT '酒店名称',
  `address` varchar(500) DEFAULT NULL COMMENT '酒店地址',
  `country` varchar(100) DEFAULT NULL COMMENT '国家',
  `hotel_phone` varchar(20) DEFAULT NULL COMMENT '酒店电话',
  `score` decimal(3,2) DEFAULT 0.00 COMMENT '酒店评分(0-5)',
  `description` text DEFAULT NULL COMMENT '酒店描述',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='酒店表';

DROP TABLE IF EXISTS `Inventory`;
CREATE TABLE `Inventory` (
  `inventory_id` int(11) NOT NULL COMMENT '库存ID',
  `type_id` varchar(50) NOT NULL COMMENT '房型ID',
  `total_count` int(11) NOT NULL DEFAULT 0 COMMENT '总数量',
  `available_count` int(11) NOT NULL DEFAULT 0 COMMENT '可用数量',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='库存表';

DROP TABLE IF EXISTS `Order`;
CREATE TABLE `Order` (
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `user_id` varchar(50) NOT NULL COMMENT '用户ID',
  `room_id` varchar(50) DEFAULT NULL COMMENT '房间ID',
  `actual_capacity` int(11) NOT NULL DEFAULT 1 COMMENT '实际入住人数',
  `check_in` date NOT NULL COMMENT '入住日期',
  `check_out` date NOT NULL COMMENT '退房日期',
  `order_status` enum('Pending','Checked In','Completed','Cancelled') NOT NULL DEFAULT 'Pending' COMMENT '订单状态',
  `staff_id` varchar(50) DEFAULT NULL COMMENT '办理员工ID',
  `total_amount` decimal(10,2) DEFAULT 0.00 COMMENT '订单总金额',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '创建时间',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';

DROP TABLE IF EXISTS `Room`;
CREATE TABLE `Room` (
  `room_id` varchar(50) NOT NULL COMMENT '房间ID',
  `type_id` varchar(50) NOT NULL COMMENT '房型ID',
  `floor` varchar(10) DEFAULT NULL COMMENT '楼层',
  `room_number` varchar(20) NOT NULL COMMENT '房间号',
  `room_status` enum('available','unavailable','maintenance','cleaning') NOT NULL DEFAULT 'available' COMMENT '房间状态',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='房间表';
DROP TRIGGER IF EXISTS `trg_update_inventory_after_room_delete`;
DELIMITER $$
CREATE TRIGGER `trg_update_inventory_after_room_delete` AFTER DELETE ON `Room` FOR EACH ROW BEGIN
    UPDATE `Inventory` 
    SET `total_count` = `total_count` - 1,
        `available_count` = `available_count` - IF(OLD.`room_status` = 'available', 1, 0)
    WHERE `type_id` = OLD.`type_id`;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_update_inventory_after_room_insert`;
DELIMITER $$
CREATE TRIGGER `trg_update_inventory_after_room_insert` AFTER INSERT ON `Room` FOR EACH ROW BEGIN
    UPDATE `Inventory` 
    SET `total_count` = `total_count` + 1,
        `available_count` = `available_count` + IF(NEW.`room_status` = 'available', 1, 0)
    WHERE `type_id` = NEW.`type_id`;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_update_inventory_after_room_update`;
DELIMITER $$
CREATE TRIGGER `trg_update_inventory_after_room_update` AFTER UPDATE ON `Room` FOR EACH ROW BEGIN
    -- 如果房间状态变化
    IF OLD.`room_status` != NEW.`room_status` THEN
        -- 旧状态为available则减1，新状态为available则加1
        UPDATE `Inventory` 
        SET `available_count` = `available_count` 
            - IF(OLD.`room_status` = 'available', 1, 0)
            + IF(NEW.`room_status` = 'available', 1, 0)
        WHERE `type_id` = NEW.`type_id`;
    END IF;
    
    -- 如果房型改变（罕见情况）
    IF OLD.`type_id` != NEW.`type_id` THEN
        -- 从旧房型减少
        UPDATE `Inventory` 
        SET `total_count` = `total_count` - 1,
            `available_count` = `available_count` - IF(OLD.`room_status` = 'available', 1, 0)
        WHERE `type_id` = OLD.`type_id`;
        -- 向新房型增加
        UPDATE `Inventory` 
        SET `total_count` = `total_count` + 1,
            `available_count` = `available_count` + IF(NEW.`room_status` = 'available', 1, 0)
        WHERE `type_id` = NEW.`type_id`;
    END IF;
END
$$
DELIMITER ;

DROP TABLE IF EXISTS `Room_Type`;
CREATE TABLE `Room_Type` (
  `type_id` varchar(50) NOT NULL COMMENT '房型ID',
  `type_name` varchar(100) NOT NULL COMMENT '房型名称',
  `capacity` int(11) NOT NULL DEFAULT 1 COMMENT '可容纳人数',
  `basic_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '基础价格',
  `score` decimal(3,2) DEFAULT 0.00 COMMENT '房型评分(0-5)',
  `description` text DEFAULT NULL COMMENT '房型描述',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='房型表';

DROP TABLE IF EXISTS `User`;
CREATE TABLE `User` (
  `user_id` varchar(50) NOT NULL COMMENT '用户ID',
  `user_name` varchar(100) NOT NULL COMMENT '用户名',
  `user_password` varchar(255) NOT NULL COMMENT '用户密码(加密)',
  `user_phone` varchar(20) DEFAULT NULL COMMENT '用户电话',
  `user_email` varchar(100) DEFAULT NULL COMMENT '用户邮箱',
  `user_type` enum('customer','staff','cleaner','manager') NOT NULL DEFAULT 'customer' COMMENT '用户类型',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';
DROP VIEW IF EXISTS `v_order_details`;
CREATE TABLE `v_order_details` (
`order_id` int(11)
,`check_in` date
,`check_out` date
,`order_status` enum('Pending','Checked In','Completed','Cancelled')
,`actual_capacity` int(11)
,`total_amount` decimal(10,2)
,`created_at` datetime
,`user_id` varchar(50)
,`user_name` varchar(100)
,`user_phone` varchar(20)
,`room_number` varchar(20)
,`floor` varchar(10)
,`type_name` varchar(100)
,`staff_name` varchar(100)
);
DROP VIEW IF EXISTS `v_room_details`;
CREATE TABLE `v_room_details` (
`room_id` varchar(50)
,`room_number` varchar(20)
,`floor` varchar(10)
,`room_status` enum('available','unavailable','maintenance','cleaning')
,`type_id` varchar(50)
,`type_name` varchar(100)
,`capacity` int(11)
,`basic_amount` decimal(10,2)
);
DROP VIEW IF EXISTS `v_task_details`;
CREATE TABLE `v_task_details` (
`task_id` int(11)
,`task_status` enum('pending','in-progress','completed','cancelled')
,`wish_time` datetime
,`actual_start_time` datetime
,`actual_end_time` datetime
,`notes` text
,`created_at` datetime
,`room_number` varchar(20)
,`floor` varchar(10)
,`type_name` varchar(100)
,`customer_name` varchar(100)
,`cleaner_name` varchar(100)
);
DROP TABLE IF EXISTS `v_order_details`;

DROP VIEW IF EXISTS `v_order_details`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_order_details`  AS SELECT `o`.`order_id` AS `order_id`, `o`.`check_in` AS `check_in`, `o`.`check_out` AS `check_out`, `o`.`order_status` AS `order_status`, `o`.`actual_capacity` AS `actual_capacity`, `o`.`total_amount` AS `total_amount`, `o`.`created_at` AS `created_at`, `u`.`user_id` AS `user_id`, `u`.`user_name` AS `user_name`, `u`.`user_phone` AS `user_phone`, `r`.`room_number` AS `room_number`, `r`.`floor` AS `floor`, `rt`.`type_name` AS `type_name`, `s`.`user_name` AS `staff_name` FROM ((((`order` `o` join `user` `u` on(`o`.`user_id` = `u`.`user_id`)) left join `room` `r` on(`o`.`room_id` = `r`.`room_id`)) left join `room_type` `rt` on(`r`.`type_id` = `rt`.`type_id`)) left join `user` `s` on(`o`.`staff_id` = `s`.`user_id`)) ;
DROP TABLE IF EXISTS `v_room_details`;

DROP VIEW IF EXISTS `v_room_details`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_room_details`  AS SELECT `r`.`room_id` AS `room_id`, `r`.`room_number` AS `room_number`, `r`.`floor` AS `floor`, `r`.`room_status` AS `room_status`, `rt`.`type_id` AS `type_id`, `rt`.`type_name` AS `type_name`, `rt`.`capacity` AS `capacity`, `rt`.`basic_amount` AS `basic_amount` FROM (`room` `r` join `room_type` `rt` on(`r`.`type_id` = `rt`.`type_id`)) ;
DROP TABLE IF EXISTS `v_task_details`;

DROP VIEW IF EXISTS `v_task_details`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_task_details`  AS SELECT `t`.`task_id` AS `task_id`, `t`.`task_status` AS `task_status`, `t`.`wish_time` AS `wish_time`, `t`.`actual_start_time` AS `actual_start_time`, `t`.`actual_end_time` AS `actual_end_time`, `t`.`notes` AS `notes`, `t`.`created_at` AS `created_at`, `r`.`room_number` AS `room_number`, `r`.`floor` AS `floor`, `rt`.`type_name` AS `type_name`, `customer`.`user_name` AS `customer_name`, `cleaner`.`user_name` AS `cleaner_name` FROM ((((`cleaning_task` `t` join `room` `r` on(`t`.`room_id` = `r`.`room_id`)) join `room_type` `rt` on(`r`.`type_id` = `rt`.`type_id`)) left join `user` `customer` on(`t`.`customer_id` = `customer`.`user_id`)) left join `user` `cleaner` on(`t`.`cleaner_id` = `cleaner`.`user_id`)) ;


ALTER TABLE `Cleaning_task`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_cleaner_id` (`cleaner_id`),
  ADD KEY `idx_status` (`task_status`),
  ADD KEY `idx_wish_time` (`wish_time`);

ALTER TABLE `comment`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `idx_type_id` (`type_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_score` (`score`);

ALTER TABLE `Hotel`
  ADD PRIMARY KEY (`hotel_id`),
  ADD KEY `idx_score` (`score`);

ALTER TABLE `Inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD UNIQUE KEY `uk_type_id` (`type_id`);

ALTER TABLE `Order`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_status` (`order_status`),
  ADD KEY `idx_check_in` (`check_in`),
  ADD KEY `idx_check_out` (`check_out`);

ALTER TABLE `Room`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `uk_room_number` (`room_number`),
  ADD KEY `idx_type_id` (`type_id`),
  ADD KEY `idx_status` (`room_status`);

ALTER TABLE `Room_Type`
  ADD PRIMARY KEY (`type_id`),
  ADD KEY `idx_capacity` (`capacity`),
  ADD KEY `idx_score` (`score`);

ALTER TABLE `User`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uk_user_name` (`user_name`),
  ADD KEY `idx_user_type` (`user_type`);


ALTER TABLE `Cleaning_task`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '任务ID';

ALTER TABLE `comment`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '评论ID';

ALTER TABLE `Inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '库存ID';

ALTER TABLE `Order`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '订单ID';


ALTER TABLE `Cleaning_task`
  ADD CONSTRAINT `fk_task_cleaner` FOREIGN KEY (`cleaner_id`) REFERENCES `User` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_task_customer` FOREIGN KEY (`customer_id`) REFERENCES `User` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_task_room` FOREIGN KEY (`room_id`) REFERENCES `Room` (`room_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `comment`
  ADD CONSTRAINT `fk_comment_type` FOREIGN KEY (`type_id`) REFERENCES `Room_Type` (`type_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `User` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `Inventory`
  ADD CONSTRAINT `fk_inventory_type` FOREIGN KEY (`type_id`) REFERENCES `Room_Type` (`type_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `Order`
  ADD CONSTRAINT `fk_order_room` FOREIGN KEY (`room_id`) REFERENCES `Room` (`room_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `User` (`user_id`) ON UPDATE CASCADE;

ALTER TABLE `Room`
  ADD CONSTRAINT `fk_room_type` FOREIGN KEY (`type_id`) REFERENCES `Room_Type` (`type_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
