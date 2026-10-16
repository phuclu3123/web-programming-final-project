Thêm một số field cho user:

''' SQL
ALTER TABLE `tbl_users`
    ADD COLUMN `username` VARCHAR(50) NULL AFTER `email`,
    ADD COLUMN `phone` VARCHAR(20) NULL AFTER `username`,
    ADD UNIQUE KEY `uq_username` (`username`);
'''

ch_fin_user_by_login
'''
DELIMITER //

CREATE PROCEDURE ch_find_user_by_login(IN p_account VARCHAR(191))
BEGIN
    SELECT id, email, username, password_hash,
           full_name, phone, role, status, deleted_at
    FROM tbl_users
    WHERE email = p_account OR username = p_account;
END //

DELIMITER ;

'''

'''


'''
