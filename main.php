<?php
/**
 * Plugin Name: Sunny's Wordpress Optimizer
 * Description: เปิด API Calls ที่ใช้เวลานาน ลบ User ที่มีคำสแปมใน Display Name เช่น cash, money, bonus และล้างข้อมูลสถิติที่เป็นขยะ (ไม่มีความสัมพันธ์กับตารางอื่น)
 * Version: 1.0
 * Author: Jirakit Pawnsakunrungrot
 * Author URI: https://www.linkedin.com/in/sunny-jirakit
 * Plugin URI: https://github.com/sunny420x/wordpress-optimizer
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// เพิ่มเมนูในหน้า Admin เพื่อกดรัน
add_action( 'admin_menu', 'sunny_wordpress_cleaner_menu' );

function sunny_wordpress_cleaner_menu() {
    add_menu_page(
        'Sunny\'s WordPress Optimizer', // Title ของหน้า
        'WordPress Optimizer', // ชื่อเมนูที่โชว์ในแถบข้าง
        'manage_options', //สิทธิ์การเข้าถึง (Admin)
        'wordpress-optimizer', // Slug ของหน้า
        'sunny_wordpress_optimizer_page', // ฟังก์ชันที่ใช้พ่น HTML หน้า Setting
        'dashicons-admin-tools', // ไอคอน
        '80' // ตำแหน่งเมนู
    );
}

function sunny_wordpress_optimizer_page() {
    global $wpdb;

    $table_relationships = $wpdb->prefix . 'statistics_visitor_relationships';
    $table_visitor = $wpdb->prefix . 'statistics_visitor';
    $table_pages_visitor = $wpdb->prefix . 'statistics_pages';

    if ( isset($_POST['clean_stats']) ) {
        check_admin_referer('wcc_clean_stats');

        $deleted = $wpdb->query(
            "DELETE r FROM $table_relationships r
             LEFT JOIN $table_visitor v ON r.visitor_id = v.ID
             WHERE v.ID IS NULL"
        );

        // สั่ง Optimize ตารางเพื่อคืนพื้นที่ทันที
        $wpdb->query("OPTIMIZE TABLE $table_relationships");

        echo '<div class="updated"><p>กวาดขยะสถิติออกไปได้ <strong>' . number_format($deleted) . '</strong> แถว และคืนพื้นที่ฐานข้อมูลเรียบร้อย!</p></div>';
        
        //ลบแคช
        delete_transient('sunny_wordpress_optimizer_health_stats');    
    }

    if ( isset($_POST['clean_pages_stats']) ) {
        check_admin_referer('do_clean_stats');

        // คำนวณวันแรกของปีปัจจุบัน (เช่น 2026-01-01)
        $first_day_of_year = date('Y') . '-01-01';

        // ลบโดยใช้ Index (เร็วกว่าการใช้ฟังก์ชัน YEAR() ครอบ column)
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_pages_visitor WHERE date < %s",
                $first_day_of_year
            )
        );

        // Optimize ตาราง
        $wpdb->query("OPTIMIZE TABLE $table_pages_visitor");

        echo '<div class="updated"><p>กวาดสถิติเก่าก่อนปี ' . date('Y') . ' ออกไปได้ <strong>' . number_format($deleted) . '</strong> แถวเรียบร้อยแล้วครับพี่!</p></div>';

        //ลบแคช
        delete_transient('sunny_wordpress_optimizer_health_stats');
    }
    ?>
    <style>
        .leftside {
            width: 350px;
            background: #f8f8f8;
            height: max-content;
        }
        .leftside h1 {
            background: #009FE3;
            color: #fff;
            font-size: 16px;
            padding: 10px 20px;
            margin: 0;
        }
        .leftside a {
            padding: 10px 20px;
            font-size: 14px;
            background: #f8f8f8;
            color: #000;
            transition: .2s ease-in-out;
            display: block;
            width: 100%;
            text-decoration: none;
        }
        .leftside a:hover {
            background: #fff;
            cursor: pointer;
        }
        .leftside a.active {
            background: #fff;
        }
        .container {
            width: 1200px;
            background: #fff; 
        }
        .container h1 {
            background: #555;
            color: #fff;
            font-size: 16px;
            padding: 10px 20px;
            margin: 0;
        }
        .container p {
            padding: 0;
        }
        .white-label-zone {
            width: calc(100% + 20px);
            height: auto;
            background: #fff;
            display: flex;
            margin: 0 0 0 -20px;
        }
        .white-label-zone h1,p {
            padding: 0 20px;
        }
    </style>
    <div class="white-label-zone no-print">
        <span style="padding: 40px 10px 40px 40px;float: left;font-size: 60px;">🚀</span>
        <div style="padding: 20px 0;">
            <h1>Sunny's WordPress Optimizer</h1>
            <p>ระบบเพิ่มความเร็ว WordPress โดยการลบข้อมูลขยะ ผู้ใช้สแปมในระบบ
                <br>
                <strong>Github Repository:</strong> <a href="https://github.com/sunny420x/wordpress-optimizer" target="_blank">https://github.com/sunny420x/wordpress-optimizer</a>
            </p>
        </div>
    </div>
    <div class="wrap">
    <div style="display: flex;">
        <div class="leftside">
            <h1>🚀 Optimizer</h1>
            <a href="/wp-admin/admin.php?page=wordpress-optimizer&option=database_junk" <?php if(isset($_GET['option']) && $_GET['option'] == "database_junk") { echo "class='active'"; } ?>>🗃️ ขยะฐานข้อมูล</a>
            <a href="/wp-admin/admin.php?page=wordpress-optimizer&option=spam_user" <?php if(isset($_GET['option']) && $_GET['option'] == "spam_user") { echo "class='active'"; } ?>>👥 ผู้ใช้สแปม</a>
            <a href="/wp-admin/admin.php?page=wordpress-optimizer&option=inactive_user" <?php if(isset($_GET['option']) && $_GET['option'] == "inactive_user") { echo "class='active'"; } ?>>👥 ผู้ใช้ที่ไม่เคลื่อนไหว</a>
            <a href="/wp-admin/admin.php?page=wordpress-optimizer&option=user_blacklist" <?php if(isset($_GET['option']) && $_GET['option'] == "user_blacklist") { echo "class='active'"; } ?>>🔒 User Blacklist</a>
            <h1>⚙️ ตั้งค่า</h1>
            <a href="/wp-admin/admin.php?page=wordpress-optimizer&option=api_blacklist" <?php if(isset($_GET['option']) && $_GET['option'] == "api_blacklist") { echo "class='active'"; } ?>>⛔ API Blacklist</a>
            <a href="/wp-admin/admin.php?page=wordpress-optimizer&option=settings" <?php if(isset($_GET['option']) && $_GET['option'] == "settings") { echo "class='active'"; } ?>>⚙️ ตั้งค่าปลั้กอิน</a>
        </div>
        <?php
        if(isset($_GET['option']) && $_GET['option'] == "database_junk") {
        ?>
        <div class="container">
            <h1>📊 สถิติฐานข้อมูล (Database Maintenance)</h1>
            <div style="padding: 0 25px 25px 25px;">
                <p>ลบข้อมูลความสัมพันธ์ในตาราง <code><?= $table_relationships ?></code> ที่ไม่มีข้อมูลผู้เข้าชมตัวจริง</p>
                
                <?php
                $junk_count = $wpdb->get_var(
                    "SELECT COUNT(*) FROM $table_relationships r
                     LEFT JOIN $table_visitor v ON r.visitor_id = v.ID
                     WHERE v.ID IS NULL"
                );
        
                $junk_visit_count = $wpdb->get_var(
                    $wpdb->prepare("SELECT COUNT(*) FROM $table_pages_visitor WHERE date < %s", date('Y') . '-01-01')
                );
                ?>
                
                <p>ตรวจพบข้อมูลขยะ: <strong style="color:red; font-size: 1.2em;"><?= number_format($junk_count) ?></strong> แถว</p>
                <form method="post">
                    <?php wp_nonce_field('wcc_clean_stats'); ?>
                    <input type="submit" name="clean_stats" class="button button-secondary" 
                           value="ล้างขยะสถิติและ Optimize ตาราง" 
                           onclick="return confirm('ล้างข้อมูลเลยไหม ?');"
                           <?= ($junk_count == 0) ? 'disabled' : '' ?>>
                </form>
        
                <p>ลบข้อมูลสถิติการเข้าชมในตาราง <code><?= $table_pages_visitor ?></code> ที่เก่ากว่าปี <?=date('Y')?></p>
        
                <p>ตรวจพบข้อมูลการเข้าชมที่เก่ากว่าปี <?=date('Y')?>: <strong style="color:red; font-size: 1.2em;"><?= number_format($junk_visit_count) ?></strong> แถว</p>
                <form method="post">
                    <?php wp_nonce_field('do_clean_stats'); ?>
                    <input type="submit" name="clean_pages_stats" class="button button-secondary" 
                           value="ล้างขยะสถิติการเข้าชมและ Optimize ตาราง" 
                           onclick="return confirm('ล้างข้อมูลเลยไหม ?');"
                           <?= ($junk_visit_count == 0) ? 'disabled' : '' ?>>
                </form>
            </div>
        </div>
        <?php
        } elseif(isset($_GET['option']) && $_GET['option'] == "spam_user") {
        ?>
        <div class="container">
            <h1>👥 ผู้ใช้ที่เข้าข่ายสแปม (Spam Users)</h1>
            <div style="padding: 0 25px 25px 25px;">
                <p>ตรวจสอบผู้ใช้ที่เข้าข่ายสแปม เช่น ผู้ใช้ที่ตั้งชื่อเพื่อโปรโมทเว็บไซต์ภายนอก สามารถจัดการคำที่เข้าข่ายได้ใน Blacklist</p>
                <?php
                $spam_words = explode("\n", get_option('sunny_cleanner_blacklist', "cash\nmoney\nbonus\noffer\nprize\nblogspot"));
                $spam_words = array_map('trim', $spam_words);

                // ขั้นตอนการลบ (เมื่อกดปุ่ม Confirm Delete)
                if ( isset($_POST['confirm_delete']) ) {
                    check_admin_referer('wcc_confirm_delete');
                    $ids_to_delete = explode(',', $_POST['user_ids']);
                    $count = 0;

                    require_once( ABSPATH . 'wp-admin/includes/user.php' );
                    foreach ( $ids_to_delete as $user_id ) {
                        if ( get_current_user_id() == $user_id ) continue;
                        wp_delete_user( intval($user_id) );
                        $count++;
                    }
                    echo '<div class="updated"><p>กำจัดสแปมออกไปแล้ว <strong>' . $count . '</strong> บัญชี!</p></div>';

                    //ลบแคช
                    delete_transient('sunny_wordpress_optimizer_health_stats');
                }

                $found_users = array();
                foreach ( $spam_words as $word ) {
                    $results = $wpdb->get_results( $wpdb->prepare(
                        "SELECT ID, user_login, display_name, user_email FROM $wpdb->users WHERE display_name LIKE %s",
                        '%' . $wpdb->esc_like($word) . '%'
                    ) );
                    if ($results) {
                        $found_users = array_merge($found_users, $results);
                    }
                }

                $found_users = array_unique($found_users, SORT_REGULAR);

                if ( !empty($found_users) ) {
                ?>
                <h3>พบ User ที่เข้าข่ายสแปม <?=count($found_users);?> รายชื่อ</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Login Name</th>
                            <th>Display Name</th>
                            <th>Email</th>
                        </tr>
                        
                    </thead>
                    <tbody>
                        <?php
                        $ids_array = array();
                        foreach ( $found_users as $user ) {
                            $ids_array[] = $user->ID;
                        ?>
                            <tr>
                                <td><?=$user->ID;?></td>
                                <td><strong><?=$user->user_login;?></strong></td>
                                <td><span style='color:red;'><?=$user->display_name;?></span></td>
                                <td><?=$user->user_email;?></td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
                <form method="post" style="margin-top:20px;">
                    <?php wp_nonce_field('wcc_confirm_delete'); ?>
                    <input type="hidden" name="user_ids" value="<?= implode(',', $ids_array); ?>">
                    <input type="submit" name="confirm_delete" class="button button-primary" 
                            value="ลบรายชื่อข้างต้นทั้งหมด" 
                            onclick="return confirm('ลบผู้ใช้ที่เข้าข่ายสแปมทั้งหมดเลยหรือไม่ ?');">
                </form>
                <?php
                    } else {
                        echo '<h2>✅ ยินดีด้วย! ไม่พบ User สแปมในระบบแล้ว</h2>';
                    }
                ?>
            </div>
        </div>
        <?php
        } elseif(isset($_GET['option']) && $_GET['option'] == "spam_user") {
        ?>
        <div class="container">
            <h1>👥 ผู้ใช้ที่เข้าข่ายสแปม (Spam Users)</h1>
            <div style="padding: 0 25px 25px 25px;">
                <p>ตรวจสอบผู้ใช้ที่เข้าข่ายสแปม เช่น ผู้ใช้ที่ตั้งชื่อเพื่อโปรโมทเว็บไซต์ภายนอก สามารถจัดการคำที่เข้าข่ายได้ใน Blacklist</p>
                <?php
                $spam_words = explode("\n", get_option('sunny_cleanner_blacklist', "cash\nmoney\nbonus\noffer\nprize\nblogspot"));
                $spam_words = array_map('trim', $spam_words);

                // ขั้นตอนการลบ (เมื่อกดปุ่ม Confirm Delete)
                if ( isset($_POST['confirm_delete']) ) {
                    check_admin_referer('wcc_confirm_delete');
                    $ids_to_delete = explode(',', $_POST['user_ids']);
                    $count = 0;

                    require_once( ABSPATH . 'wp-admin/includes/user.php' );
                    foreach ( $ids_to_delete as $user_id ) {
                        if ( get_current_user_id() == $user_id ) continue;
                        wp_delete_user( intval($user_id) );
                        $count++;
                    }
                    echo '<div class="updated"><p>กำจัดสแปมออกไปแล้ว <strong>' . $count . '</strong> บัญชี!</p></div>';

                    //ลบแคช
                    delete_transient('sunny_wordpress_optimizer_health_stats');
                }

                $found_users = array();
                foreach ( $spam_words as $word ) {
                    $results = $wpdb->get_results( $wpdb->prepare(
                        "SELECT ID, user_login, display_name, user_email FROM $wpdb->users WHERE display_name LIKE %s",
                        '%' . $wpdb->esc_like($word) . '%'
                    ) );
                    if ($results) {
                        $found_users = array_merge($found_users, $results);
                    }
                }

                $found_users = array_unique($found_users, SORT_REGULAR);

                if ( !empty($found_users) ) {
                ?>
                <h3>พบ User ที่เข้าข่ายสแปม <?=number_format(count($found_users));?> รายชื่อ</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Login Name</th>
                            <th>Display Name</th>
                            <th>Email</th>
                        </tr>
                        
                    </thead>
                    <tbody>
                        <?php
                        $ids_array = array();
                        foreach ( $found_users as $user ) {
                            $ids_array[] = $user->ID;
                        ?>
                            <tr>
                                <td><?=$user->ID;?></td>
                                <td><strong><?=$user->user_login;?></strong></td>
                                <td><span style='color:red;'><?=$user->display_name;?></span></td>
                                <td><?=$user->user_email;?></td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
                <form method="post" style="margin-top:20px;">
                    <?php wp_nonce_field('wcc_confirm_delete'); ?>
                    <input type="hidden" name="user_ids" value="<?= implode(',', $ids_array); ?>">
                    <input type="submit" name="confirm_delete" class="button button-primary" 
                            value="ลบรายชื่อข้างต้นทั้งหมด" 
                            onclick="return confirm('ลบผู้ใช้ที่เข้าข่ายสแปมทั้งหมดเลยหรือไม่ ?');">
                </form>
                <?php
                    } else {
                        echo '<h2>✅ ยินดีด้วย! ไม่พบ User สแปมในระบบแล้ว</h2>';
                    }
                ?>
            </div>
        </div>
        <?php
        } elseif(isset($_GET['option']) && $_GET['option'] == "inactive_user") {
        ?>
        <div class="container">
            <h1>👥 ผู้ใช้ที่ไม่มีการเคลื่อนไหวในระบบ (Inactive Users)</h1>
            <div style="padding: 0 25px 25px 25px;">
                <p>ตรวจสอบผู้ใช้ที่ไม่มีการเคลื่อนไหวในระบบ โดยมีเงื่อนไขได้แก่ ไม่มีชื่อนาม-สกุล ไม่มีข้อมูลการสั่งซื้อ ทั้งที่สมัครสมาชิกแล้วไม่ต่ำกว่า 1 ปี</p>
                <div style="align-items: center; display: flex;">
                    <select name="filter" id="filter_type">
                        <option value="email" <?php if(isset($_GET['filter'])) { selected($_GET['filter']); } ?>>Email</option>
                    </select>
                    <input type="text" name="value" id="filter_value" value="<?php if(isset($_GET['value'])) { echo $_GET['value']; }?>">
                    <button class="button button-outline-primary" onclick="window.location.href=`admin.php?page=wordpress-optimizer&option=inactive_user&filter=${document.getElementById('filter_type').value}&value=${document.getElementById('filter_value').value}`">กรอง</button>
                </div>
                <?php
                if ( isset($_POST['confirm_delete']) ) {
                    check_admin_referer('wcc_confirm_delete');
                    $ids_to_delete = explode(',', $_POST['user_ids']);
                    $count = 0;

                    require_once( ABSPATH . 'wp-admin/includes/user.php' );
                    foreach ( $ids_to_delete as $user_id ) {
                        if ( get_current_user_id() == $user_id ) continue;
                        $user = get_userdata(intval($user_id));
                        if ( !$user || get_current_user_id() == $user_id || in_array('administrator', $user->roles) ) {
                            continue;
                        }
                        wp_delete_user( intval($user_id) );
                        $count++;
                    }
                    echo '<div class="updated"><p>ลบผู้ใช้ที่ไม่เคลื่อนไหวออกแล้ว <strong>' . $count . '</strong> บัญชี!</p></div>';
                }

                $found_users = array();

                global $wpdb;

                $query = "
                    SELECT u.ID, u.user_login, u.display_name, u.user_email 
                    FROM {$wpdb->users} AS u
                    LEFT JOIN {$wpdb->prefix}posts AS p ON (p.post_author = u.ID AND p.post_type = 'shop_order')
                    LEFT JOIN {$wpdb->usermeta} AS meta_first ON (u.ID = meta_first.user_id AND meta_first.meta_key = 'first_name')
                    LEFT JOIN {$wpdb->usermeta} AS meta_last ON (u.ID = meta_last.user_id AND meta_last.meta_key = 'last_name')
                    
                    WHERE p.ID IS NULL
                    AND (
                        (meta_first.meta_value IS NULL OR meta_first.meta_value = '') 
                        OR 
                        (meta_last.meta_value IS NULL OR meta_last.meta_value = '')
                    )
                    AND u.user_registered < DATE_SUB(NOW(), INTERVAL 1 YEAR)
                    AND u.user_login NOT LIKE '%admin%'
                    AND u.user_login NOT LIKE '%seo%'
                    AND u.user_login NOT LIKE '%editor%'
                ";

                if ( isset($_GET['filter']) && $_GET['filter'] == "email" ) {
                    if ( !empty($_GET['value']) ) {
                        $email_val = '%' . $wpdb->esc_like($_GET['value']) . '%';
                        $query .= $wpdb->prepare(" AND u.user_email LIKE %s ", $email_val);
                    }
                }

                $found_users = $wpdb->get_results($query);

                if ( !empty($found_users) ) {
                ?>
                <h3>พบ User ไม่มีการเคลื่อนไหวในระบบ <?=number_format(count($found_users));?> รายชื่อ</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Login Name</th>
                            <th>Display Name</th>
                            <th>Email</th>
                        </tr>  
                    </thead>
                    <tbody>
                        <?php
                        $ids_array = array();
                        foreach ( $found_users as $user ) {
                            $ids_array[] = $user->ID;
                        ?>
                            <tr>
                                <td><?=$user->ID;?></td>
                                <td><strong><?=$user->user_login;?></strong></td>
                                <td><span style='color:red;'><?=$user->display_name;?></span></td>
                                <td><?=$user->user_email;?></td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
                <form method="post" style="margin-top:20px;">
                    <?php wp_nonce_field('wcc_confirm_delete'); ?>
                    <input type="hidden" name="user_ids" value="<?= implode(',', $ids_array); ?>">
                    <input type="submit" name="confirm_delete" class="button button-primary" 
                            value="ลบรายชื่อข้างต้นทั้งหมด" 
                            onclick="return confirm('ลบผู้ใช้ที่เข้าข่ายไม่เคลื่อนไหวทั้งหมดเลยหรือไม่ ?');">
                </form>
                <?php
                    } else {
                        echo '<h2>✅ ยินดีด้วย! ไม่พบ User ตามที่เลือกในระบบแล้ว</h2>';
                    }
                ?>
            </div>
        </div>
        <?php
        } elseif(isset($_GET['option']) && $_GET['option'] == "user_blacklist") {
        ?>
        <div class="container">
            <h1>🔒 User Blacklist - บล็อคการเข้าสู่ระบบ</h1>
            <div style="padding: 0 25px 25px 25px;">
                <p>ระบบ Blacklist เพื่อบล็อคการเข้าสู่ระบบ หากชื่อ Display Name ตรงกับรายชื่อในรายการบล็อก หากผู้ใช้พยายามเข้าสู่ระบบ จะถูกบล็อกทันที</p>
                <?php
                // Handle form submission to save blacklist
                if ( isset($_POST['save_user_blacklist']) ) {
                    check_admin_referer('wcc_save_user_blacklist');
                    $blacklist_text = isset($_POST['user_blacklist_names']) ? sanitize_textarea_field(wp_unslash($_POST['user_blacklist_names'])) : '';
                    update_option('sunny_user_blacklist', $blacklist_text);
                    echo '<div class="updated"><p>บันทึกรายชื่อ Blacklist เรียบร้อยแล้ว!</p></div>';
                    delete_transient('sunny_wordpress_optimizer_health_stats');
                }

                $blacklist_text = get_option('sunny_user_blacklist', '');
                $blacklist_names = array_filter(array_map('trim', explode("\n", $blacklist_text)));
                ?>
                <h3>📝 เพิ่ม/แก้ไข Display Name Blacklist</h3>
                <form method="post" style="margin: 20px 0;">
                    <?php wp_nonce_field('wcc_save_user_blacklist'); ?>
                    <p><strong>ป้อนชื่อ Display Name (บรรทัดละ 1 ชื่อ)</strong></p>
                    <textarea name="user_blacklist_names" style="width: 100%; height: 300px; font-family: monospace;"><?php echo esc_textarea($blacklist_text); ?></textarea>
                    <br><br>
                    <input type="submit" name="save_user_blacklist" class="button button-primary" value="บันทึก Blacklist">
                </form>

                <h3>📊 รายชื่อที่ถูกบล็อก (<?php echo count($blacklist_names); ?> รายชื่อ)</h3>
                <?php if ( !empty($blacklist_names) ) { ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ลำดับ</th>
                            <th>Display Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $number = 1;
                        foreach ( $blacklist_names as $name ) {
                        ?>
                            <tr>
                                <td><?php echo $number++; ?></td>
                                <td><span style="color: red; font-weight: bold;"><?php echo esc_html($name); ?></span></td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
                <?php
                    } else {
                        echo '<p style="color: green;"><strong>✅ ยังไม่มีชื่อใดถูกบล็อก</strong></p>';
                    }
                ?>

                <h3>🔍 ตรวจสอบผู้ใช้ที่อาจถูกบล็อก</h3>
                <?php
                global $wpdb;
                $blocked_users = array();
                
                if ( !empty($blacklist_names) ) {
                    $users = $wpdb->get_results("SELECT ID, user_login, display_name, user_email FROM $wpdb->users");
                    foreach ( $users as $user ) {
                        foreach ( $blacklist_names as $blacklist_name ) {
                            // Case-insensitive exact or partial match
                            if ( strtolower($user->display_name) === strtolower($blacklist_name) || 
                                 stripos($user->display_name, $blacklist_name) !== false ) {
                                $blocked_users[] = $user;
                                break;
                            }
                        }
                    }
                }

                if ( !empty($blocked_users) ) {
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Login Name</th>
                            <th>Display Name</th>
                            <th>Email</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ( $blocked_users as $user ) {
                        ?>
                            <tr>
                                <td><?php echo $user->ID; ?></td>
                                <td><strong><?php echo esc_html($user->user_login); ?></strong></td>
                                <td><span style="color: red; font-weight: bold;"><?php echo esc_html($user->display_name); ?></span></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td><span style="background: #ffcccc; padding: 5px 10px; border-radius: 3px;">🚫 ถูกบล็อก</span></td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
                <p style="color: #d32f2f; margin-top: 20px;"><strong>⚠️ ผู้ใช้ข้างต้นจะไม่สามารถเข้าสู่ระบบได้</strong></p>
                <?php
                    } else {
                        echo '<p style="color: green;"><strong>✅ ไม่พบผู้ใช้ที่ถูกบล็อกในระบบ</strong></p>';
                    }
                ?>
            </div>
        </div>
        <?php
        } elseif(isset($_GET['option']) && $_GET['option'] == "settings") {
        ?>
        <div class="container">
            <h1>⚙️ ตั้งค่าปลั้กอิน (Plugin Setting)</h1>
            <div style="padding: 0 25px 25px 25px;">
                <form action="options.php" method="post">
                    <?php
                    settings_fields('sunny_optimizer_settings_group');
                    ?>
                    <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                        <thead>
                            <tr>
                                <th>หมวดหมู่</th>
                                <th>ตั้งค่า</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Spam Word Blacklist</strong></td>
                                <td>
                                    <textarea name="sunny_cleanner_blacklist" style="width: 500px; height: 200px;"><?php echo esc_attr(get_option('sunny_cleanner_blacklist', "cash\nmoney\nbonus\noffer\nprize\nblogspot")); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>ปิดใช้งานการติดต่อ WordPress API</strong> *ต้องปิดใช้งานฟีเจอร์นี้ชั่วคราว จึงจะสามารถติดตั้งปลั้กอินใหม่ได้</td>
                                <td>
                                    <select name="sunny_cleanner_disable_wordpress_external_api" id="">
                                        <option value="yes" <?php if(get_option('sunny_cleanner_disable_wordpress_external_api', 'no') == "yes") { echo "selected";} ?>>ป้องกันติดต่อ WordPress API</option>
                                        <option value="no" <?php if(get_option('sunny_cleanner_disable_wordpress_external_api', 'no') == "no") { echo "selected";} ?>>ยอมเปิดติดต่อ WordPress API</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>ปิดใช้งานการติดต่อ API ภายนอกจาก Blacklist</strong></td>
                                <td>
                                    <select name="sunny_cleanner_disable_external_api" id="">
                                        <option value="yes" <?php if(get_option('sunny_cleanner_disable_external_api', 'no') == "yes") { echo "selected";} ?>>ป้องกันติดต่อ API ภายนอก</option>
                                        <option value="no" <?php if(get_option('sunny_cleanner_disable_external_api', 'no') == "no") { echo "selected";} ?>>ยอมเปิดติดต่อ API ภายนอก</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php submit_button('บันทึกการเปลี่ยนแปลง'); ?>
                </form>
            </div>
        </div>
        <?php
        } elseif(isset($_GET['option']) && $_GET['option'] == "api_blacklist") {
        ?>
        <div class="container">
            <h1>⛔ API Blacklist</h1>
            <div style="padding: 0 25px 25px 25px;">
                <form action="options.php" method="post">
                    <?php
                    settings_fields('sunny_optimizer_api_blacklist_group');
                    ?>
                    <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                        <thead>
                            <tr>
                                <th>หมวดหมู่</th>
                                <th>ตั้งค่า</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>API Blacklist</strong></td>
                                <td>
                                    <textarea name="sunny_cleanner_api_blacklist" style="width: 500px; height: 200px;"><?php echo esc_attr(get_option('sunny_cleanner_api_blacklist')); ?></textarea>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php submit_button('บันทึกการเปลี่ยนแปลง'); ?>
                </form>
            </div>
        </div>
    <?php
        } else {
        ?>
        <div class="container">
            <h1>ยินดีต้อนรับเข้าสู่ Sunny's WordPress Optimizer</h1>
            <div style="padding: 0px 25px 25px 25px;">
                <h2>ปลั้กอินนี้ทำอะไร ?</h2>
                <p>ปลั้กอิน Sunny's WordPress Optimizer เป็นปลั้กอินที่รวบรวมคำสั่งที่ช่วยในการเพิ่มความเร็วเว็บไซต์ WordPress ได้จริง โดยเป็นการแก้ไขปัญหาจาก Case-Study จากเว็บไซต์จริงที่มีปัญหาเรื่องความเร็วที่มี
                    สาเหตุมาจากข้อมูลขยะที่สะสมในระบบ เช่น ข้อมูลความความสัมพันธ์ของผู้ใช้กับเว็บไซต์ที่ไม่มีความสัมพันธ์กับตารางอื่น หมายความว่าข้อมูลดังกล่าวไม่มีประโยชน์เลย รวมถึงผู้ใช้สแปมในระบบ เป็นต้น
                </p>
                <h2>คำแนะนำจากผู้พัฒนา</h2>
                <p>ใน <strong>Real-world scenario</strong> เว็บไซต์เว็บหนึ่ง ๆ อาจมีคำขอ (Requests) ที่ไม่มีประโยชน์เป็นจำนวนมาก เช่น Crawler หาช่องโหว่ Crawler จัดทำ Index หรือ Direactory Scanner เป็นต้น</p>
                <h3>WordFence</h3>
                <p>ปลั้กอิน WordFence เป็นปลั้กอินที่ผู้พัฒนาแนะนำให้ติดตั้งควบคู่ไปกับปลั้กอินนี้ เนื่องจาก WordFence ช่วยเป็น Application Firewall ในการกรองและ Block ผู้ใช้ที่ใช้ทรัพยากรของ Web Server โดยไม่เกิดประโยชน์ได้</p>
                <h3>การ Pruning WordPress Statistic</h3>
                <p>ให้ไปที่ <a href="/wp-admin/admin.php?page=wps_optimization_page&tab=purging" target="_blank">?page=wps_optimization_page&tab=purging</a> เพื่อทำการ Optimize ข้อมูลบ่อย ๆ โดยการลบข้อมูลสถิติที่เก่ากว่าหนึ่งปี</p>
            </div>
        </div>
        <?php
        }
        ?>
    </div>
<?php
}

function sunny_optimizer_get_blacklist_words() {
    $raw_blacklist = get_option('sunny_cleanner_blacklist', "cash\nmoney\nbonus\noffer\nprize\nblogspot");

    if ( ! is_string($raw_blacklist) ) {
        return array();
    }

    $words = preg_split('/\r\n|\r|\n/', $raw_blacklist);
    $clean_words = array();

    if ( empty($words) ) {
        return $clean_words;
    }

    foreach ( $words as $word ) {
        $word = trim($word);
        if ( $word !== '' ) {
            $clean_words[] = strtolower($word);
        }
    }

    return array_values(array_unique($clean_words));
}

function sunny_optimizer_contains_blacklisted_text( $text ) {
    if ( ! is_string($text) || $text === '' ) {
        return false;
    }

    $text = strtolower($text);
    $words = sunny_optimizer_get_blacklist_words();

    foreach ( $words as $word ) {
        if ( strpos($text, $word) !== false ) {
            return true;
        }
    }

    return false;
}

function sunny_optimizer_block_blacklisted_registration( $errors, $sanitized_user_login, $user_email ) {
    if ( ! is_wp_error($errors) ) {
        $errors = new WP_Error();
    }

    if ( ! empty($errors->errors) ) {
        return $errors;
    }

    $candidate_fields = array();

    if ( isset($_POST['user_login']) ) {
        $candidate_fields[] = sanitize_text_field( wp_unslash($_POST['user_login']) );
    }

    if ( isset($_POST['display_name']) ) {
        $candidate_fields[] = sanitize_text_field( wp_unslash($_POST['display_name']) );
    }

    if ( isset($_POST['first_name']) ) {
        $candidate_fields[] = sanitize_text_field( wp_unslash($_POST['first_name']) );
    }

    if ( isset($_POST['last_name']) ) {
        $candidate_fields[] = sanitize_text_field( wp_unslash($_POST['last_name']) );
    }

    if ( isset($_POST['nickname']) ) {
        $candidate_fields[] = sanitize_text_field( wp_unslash($_POST['nickname']) );
    }

    $candidate_fields[] = $sanitized_user_login;
    $candidate_fields[] = $user_email;

    foreach ( $candidate_fields as $field ) {
        if ( sunny_optimizer_contains_blacklisted_text($field) ) {
            $errors->add('sunny_blacklist_blocked', __('การสมัครสมาชิกถูกบล็อคเนื่องจากข้อมูลมีคำที่อยู่ใน blacklist ของปลั้กอิน', 'sunny-wordpress-optimizer'));
            break;
        }
    }

    return $errors;
}

add_filter('registration_errors', 'sunny_optimizer_block_blacklisted_registration', 10, 3);
add_filter('woocommerce_registration_errors', 'sunny_optimizer_block_blacklisted_registration', 10, 3);

/**
 * Block login if user's display name is in User Blacklist
*/
/**
 * Block login if user's Username (user_login) is in User Blacklist
 * ปรับปรุงให้เช็คจาก Username แทน
 */
function sunny_optimizer_block_blacklisted_login( $user, $password ) {
    // ป้องกัน Error หากไม่มี Object
    if ( ! is_object($user) || ! is_a($user, 'WP_User') ) {
        return $user;
    }

    // ดึง Blacklist 
    $raw_blacklist = get_option('sunny_user_blacklist', '');
    if ( empty($raw_blacklist) ) {
        return $user;
    }

    // แยกรายชื่อและทำให้สะอาด
    $blacklist_names = array_filter(array_map('trim', explode("\n", $raw_blacklist)));
    if ( empty($blacklist_names) ) {
        return $user;
    }

    // เปลี่ยนมาดึงค่าจาก user_login แทน display_name
    $user_login_name = isset($user->user_login) ? strtolower(trim($user->user_login)) : '';
    if ( empty($user_login_name) ) {
        return $user;
    }

    // ตรวจสอบรายชื่อแต่ละอันในรายการ Blacklist
    foreach ( $blacklist_names as $blacklist_name ) {
        $blacklist_name_lower = strtolower(trim($blacklist_name));
        if ( empty($blacklist_name_lower) ) {
            continue;
        }
        
        // ตรวจสอบการตรงกันโดยใช้ $user_login_name
        if ( $user_login_name === $blacklist_name_lower || 
             strpos($user_login_name, $blacklist_name_lower) !== false ) {
            
            return new WP_Error(
                'sunny_user_blacklist_blocked',
                __('บัญชีผู้ใช้นี้ถูกบล็อคจากการเข้าสู่ระบบ (User account has been blocked)', 'sunny-wordpress-optimizer')
            );
        }
    }

    return $user;
}

add_filter('wp_authenticate_user', 'sunny_optimizer_block_blacklisted_login', 10, 2);

// จุดสำคัญ: ต้องเปลี่ยนเลข 3 ด้านหลังเป็นเลข 2 
add_filter('wp_authenticate_user', 'sunny_optimizer_block_blacklisted_login', 10, 2);
add_action('admin_init', 'sunny_optimizer_settings_init');

function sunny_optimizer_settings_init() {
    register_setting('sunny_optimizer_settings_group', 'sunny_cleanner_blacklist');
    register_setting('sunny_optimizer_settings_group', 'sunny_cleanner_disable_external_api');
    register_setting('sunny_optimizer_settings_group', 'sunny_cleanner_disable_wordpress_external_api');
    register_setting('sunny_optimizer_api_blacklist_group', 'sunny_cleanner_api_blacklist');
    register_setting('sunny_optimizer_user_blacklist_group', 'sunny_user_blacklist');
}

/**
 * Block External API Requests to WordPress.org for WooCommerce Info
 */
add_filter( 'pre_http_request', function( $pre, $args, $url ) {
    if ( is_admin() ) {
        global $pagenow;
        $allowed_pages = array(
            'plugin-install.php', 
            'update-core.php', 
            'plugins.php', 
            'theme-install.php'
        );

        if ( in_array( $pagenow, $allowed_pages ) ||  get_option('sunny_cleanner_disable_wordpress_external_api', 'no') == "no") {
            return $pre;
        }
    }

    if ( strpos( $url, 'api.wordpress.org' ) !== false || strpos( $url, 'woocommerce.json' ) !== false ) {
        return new WP_Error( 'http_request_failed', 'Blocked for speed optimization!' );
    }

    return $pre;
}, 10, 3 );

/**
 * Block External API Requests
 */
add_filter( 'pre_http_request', function( $pre, $args, $url ) {
    if(get_option('sunny_cleanner_disable_external_api') == "no") {
        return $pre;
    }
    if ( ! is_string( $url ) ) {
        return $pre;
    }
    if ( strpos( $url, site_url() ) !== false ) {
        return $pre;
    }
    // ใช้ @ ครอบ get_option เผื่อในจังหวะที่ Database ยังไม่เชื่อมต่อ
    $raw_blacklist = @get_option('sunny_cleanner_api_blacklist', '');
    if ( empty( $raw_blacklist ) && strpos( $url, 'woocommerce.json' ) === false ) {
        return $pre;
    }

    $blacklists = explode("\n", $raw_blacklist);

    foreach( $blacklists as $blacklist ) {
        $blacklist = trim( $blacklist );
        if ( empty( $blacklist ) ) continue;
        if ( strpos( $url, $blacklist ) !== false || strpos( $url, 'woocommerce.json' ) !== false ) {
            return new WP_Error( 'http_request_failed', 'Blocked for speed optimization!' );
        }
    }

    return $pre;
}, 10, 3 );


add_filter( 'pre_http_request', function( $pre, $args, $url ) {
    if( get_option('sunny_cleanner_disable_wordpress_external_api', 'no') == "yes") {
        if ( strpos( $url, 'api.wordpress.org/plugins/info' ) !== false && strpos( $url, 'woocommerce' ) !== false ) {
            return new WP_Error( 'blocked_request', 'Force blocked for speed', array( 'status' => 403 ) );
        }
    }
    return $pre;
}, 10, 3 );

/**
 * Silence the WP 6.7 _load_textdomain_just_in_time doing_it_wrong notice
 * for abandoned plugins and themes.
 */
add_filter( 'doing_it_wrong_trigger_error', function( $trigger, $function_name ) {
    if ( $function_name === '_load_textdomain_just_in_time' ) {
        return false; // Suppresses this specific notice
    }
    return $trigger;
}, 10, 2 );