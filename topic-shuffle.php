<?php
/*
Plugin Name: TopicShuffle
Description: トピックの班分け機能を追加します
Version: 1.0
Author: Keisuke Miura
Author URI: https://next.waterleaper.net/
License: GPLv2
*/

//=================================================
// 管理画面に「とりあえずメニュー」を追加登録する
//=================================================
add_action('admin_menu', function () {

  //---------------------------------
  // メインメニュー①
  //---------------------------------
  add_menu_page(
    'TopicShuffle' // ページのタイトルタグ<title>に表示されるテキスト
    ,
    'TopicShuffle'   // 左メニューとして表示されるテキスト
    ,
    'manage_categories'       // 必要な権限 manage_categories は通常 editor 以上に与えられた権限
    ,
    'topic_shuffle'        // 左メニューのスラッグ名 →URLのパラメータに使われる /wp-admin/admin.php?page=topic_shuffle
    ,
    'topic_shuffle_page_contents' // メニューページを表示する際に実行される関数(サブメニュー①の処理をする時はこの値は空にする)
    ,
    'dashicons-admin-users'       // メニューのアイコンを指定 https://developer.wordpress.org/resource/dashicons/#awards
    ,
    0                             // メニューが表示される位置のインデックス(0が先頭) 5=投稿,10=メディア,20=固定ページ,25=コメント,60=テーマ,65=プラグイン,70=ユーザー,75=ツール,80=設定
  );
});

//実行するよ！
$topic_shuffleObj = new Topic_Shuffle();

class Topic_Shuffle
{

  public function __construct()
  {
    global $topic_shuffle_version;
    $topic_shuffle_version = '1.0';

    //初期化
    $this->admin_init();
  }


  public function admin_init()
  {
    register_activation_hook(__FILE__, array($this, 'topic_shuffle_install')); // プラグイン有効化時にDB作成
    register_activation_hook(__FILE__, array($this, 'topic_shuffle_install_data')); //プラグイン有効化時にDB初期化
    // register_deactivation_hook(__FILE__, array($this, 'topic_shuffle_delete_data')); //プラグイン停止時に実行する関数を登録

    add_action('plugins_loaded', array($this, 'myplugin_update_db_check')); //プラグイン更新時のDB更新チェック
    add_shortcode("NAMES-SHOW", array($this, 'names_start'));
    add_shortcode("NGCOMBOS-SHOW", array($this, 'ngcombos_start'));
    add_shortcode("NAMES-OPTION-SHOW", array($this, 'names_option_start'));
    add_filter('widget_text', 'do_shortcode'); //ウィジェットでショートコードが使用できるようにする。
  }

  // 名簿管理機能 =====================================================

  public function names_start()
  {
    $this->names_show();
  }

  public function ngcombos_start()
  {
    $this->ngcombos_show();
  }
  public function names_option_start()
  {
    $this->names_option_show();
  }

  public function names_show()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'topic_names';

    $sql = "SELECT id,name FROM {$table_name};";
    $results = $wpdb->get_results($sql);
    foreach ($results as $row) {
      echo "
        <form method='post'>
          <tr>
            <input type='hidden' name='id' value='{$row->id}'>
            <td>{$row->id}</td>
            <td>{$row->name}</td>
            <td>
                <button type='submit' name='delete'>削除する</button>
            </td>
          <tr>
        </form>
      ";
    }
  }

  public function names_option_show()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'topic_names';

    $sql = "SELECT id,name FROM {$table_name};";
    $results = $wpdb->get_results($sql);
    foreach ($results as $row) {
      echo "
        <option value='{$row->name}'>{$row->name}</option>
      ";
    }
  }

  public function ngcombos_show()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'topic_ngcombos';

    $sql = "SELECT id,name1,name2 FROM {$table_name};";
    $results = $wpdb->get_results($sql);
    foreach ($results as $row) {
      echo "
        <form method='post'>
          <tr>
            <input type='hidden' name='ng-id' value='{$row->id}'>
            <td>{$row->id}</td>
            <td>{$row->name1}</td>
            <td>{$row->name2}</td>
            <td>
                <button type='submit' name='ng-delete'>削除する</button>
            </td>
          <tr>
        </form>
      ";
    }
  }
  // 名簿管理機能 ここまで =====================================================

  //DBの作成 ※プラグイン有効時
  function topic_shuffle_install()
  {
    $plugin_dir = plugin_dir_path(__FILE__) . '/page-shuffle.php';
    $theme_dir = get_stylesheet_directory() . '/page-shuffle.php';
    copy($plugin_dir, $theme_dir);
    $asset1 = plugin_dir_path(__FILE__) . '/assets/game_kuji_man.png';
    $asset2 = plugin_dir_path(__FILE__) . '/assets/kujibiki_box.png';
    copy($asset1, get_stylesheet_directory() . '/assets/game_kuji_man.png');
    copy($asset2, get_stylesheet_directory() . '/assets/kujibiki_box.png');
    create_pages_and_setting();

    global $wpdb;
    $table_name = $wpdb->prefix . 'topic_names';
    $table_name2 = $wpdb->prefix . 'topic_ngcombos';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(16) NOT NULL,
                UNIQUE KEY id (id),
                PRIMARY KEY (name)
                )
                {$charset_collate}, ENGINE=InnoDB;";

    $sql2 = "CREATE TABLE $table_name2 (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name1 varchar(16) NOT NULL,
                name2 varchar(16) NOT NULL,
                UNIQUE KEY id (id),
                FOREIGN KEY (name1) REFERENCES wp_topic_names(name),
                FOREIGN KEY (name2) REFERENCES wp_topic_names(name)
                )
                {$charset_collate}, ENGINE=InnoDB;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($sql2);

    add_option('topic_shuffle_version', $topic_shuffle_version);
  }

  //DBの初期化 ※プラグイン有効時
  function topic_shuffle_install_data()
  {

    global $wpdb;
    $table_name = $wpdb->prefix . 'topic_names';

    $count_init = 0;

    $wpdb->insert(
      $table_name,
      array(
        'time' => current_time('mysql'),
        'count' => $count_init,
      )
    );
  }


  //DB更新チェック ※プラグイン更新
  function myplugin_update_db_check()
  {
    if (get_site_option('topic_shuffle_version') != $topic_shuffle_version) {
      $this->topic_shuffle_install();
    }
  }


  //DBの削除 ※プラグイン停止時
  function topic_shuffle_delete_data()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'topic_names';
    $table_name2 = $wpdb->prefix . 'topic_ngcombos';

    delete_option('topic_shuffle_version');
    $sql = "DROP TABLE {$table_name};";
    $sql2 = "DROP TABLE {$table_name2};";
    $wpdb->query($sql);
    $wpdb->query($sql2);
  }
} //Topic_Shuffle

function add_name($text)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'topic_names';
  $wpdb->insert(
    $table_name,
    array(
      'name' => $text,
    )
  );
}
function delete_name($id)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'topic_names';
  $wpdb->delete(
    $table_name,
    array(
      'id' => $id,
    )
  );
}
function add_ngcombos($name1, $name2)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'topic_ngcombos';
  $wpdb->insert(
    $table_name,
    array(
      'name1' => $name1,
      'name2' => $name2,
    )
  );
}
function delete_ngcombos($id)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'topic_ngcombos';
  $wpdb->delete(
    $table_name,
    array(
      'id' => $id,
    )
  );
}

function nowUrl()
{
  $url = '';
  if (isset($_SERVER['HTTPS'])) {
    $url .= 'https://';
  } else {
    $url .= 'http://';
  }
  $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  return $url;
}

function get_names_array()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'topic_names';

  $sql = "SELECT id,name FROM {$table_name};";
  $results = $wpdb->get_results($sql);
  $arr = array();
  foreach ($results as $row) {
    array_push($arr, $row->name);
  }
  return $arr;
}
function get_ngcombos_array()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'topic_ngcombos';

  $sql = "SELECT name1,name2 FROM {$table_name};";
  $results = $wpdb->get_results($sql);
  $arr = array();
  foreach ($results as $row) {
    $arr2 = array();
    array_push($arr2, $row->name1);
    array_push($arr2, $row->name2);
    asort($arr2);
    array_push($arr, implode($arr2));
  }
  return $arr;
}
function create_pages_and_setting()
{
  //$pages_array[] = array('title'=>'ページタイトル', 'name'=>'スラッグ', 'parent'=>'親スラッグ');
  //例としてお問い合わせページを入力(親ページなし)
  $pages_array[] = array(
    'title' => 'トピック班分け',
    'name' => 'shuffle',
    'parent' => '',
    'template' => 'page-shuffle.php'
  );
  foreach ($pages_array as $value) {
    setting_pages($value);
  }
}
function setting_pages($val)
{
  //親ページ判別
  if (!empty($val['parent'])) {
    $parent_id = get_page_by_path($val['parent']);
    $parent_id = $parent_id->ID;
    $page_slug = $val['parent'] . "/" . $val['name'];
  } else {
    $parent_id = "";
    $page_slug = $val['name'];
  }
  if (empty(get_page_by_path($page_slug))) {
    //固定ページがなければ作成
    $insert_id = wp_insert_post(
      array(
        'post_title'   => $val['title'],
        'post_name'    => $val['name'],
        'page_template' => $val['template'],
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_parent'  => $parent_id,
      )
    );
  } else {
    //固定ページがすでにあれば更新
    $page_obj = get_page_by_path($page_slug);
    $page_id = $page_obj->ID;
    $base_post = array(
      'ID'           => $page_id,
      'post_title'   => $val['title'],
      'post_name'    => $val['name'],
      'page_template' => $val['template'],
    );
    wp_update_post($base_post);
  }
}

//=================================================
// シャッフル機能
//=================================================

//=================================================
// メインメニューページ内容の表示・更新処理
//=================================================
function topic_shuffle_page_contents()
{
  $names = get_names_array();
  $ngcombos = get_ngcombos_array();
  // ↑ DBの各種設定
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['name']) && in_array($_POST['name'], $names)) {
      echo  '<p style="color:red;">※同じ名前は追加できません。</p>';
    } else if (!empty($_POST['name'])) {
      add_name($_POST['name']);
    } else if (isset($_POST['delete'])) {
      delete_name($_POST['id']);
    } else if (isset($_POST['ng-delete'])) {
      delete_ngcombos($_POST['ng-id']);
    } else if (!empty($_POST['ng-name1']) && !empty($_POST['ng-name2'])) {
      $_arr = array($_POST['ng-name1'], $_POST['ng-name2']);
      asort($_arr);
      $ngcombo = implode($_arr);
      if (in_array($ngcombo, $ngcombos)) {
        echo '<p style="color:red;">※同じ組み合わせは追加できません。</p>';
      } else if ($_POST['ng-name1'] == $_POST['ng-name2']) {
        echo '<p style="color:red;">※同じ名前を組み合わせることはできません。</p>';
      } else {
        add_ngcombos($_POST['ng-name1'], $_POST['ng-name2']);
      }
    } else {
      header("Location: " . nowUrl());
    }
  }
  //---------------------------------
  // HTML表示
  //---------------------------------
  echo <<<EOF
    <div class="wrap">
      <style>
        table {
          margin-top: 10px;
          border-spacing: 0px 0px;
        }
        td, th {
          padding: 3px 6px;
        }
        table, td, th {
          border: 1px #969696 solid;
        }
      </style>
      <h1 style="font-weight: bold;">TopicShuffle</h1>
      <a href="/shuffle">トピック班分けページへ</a>
      <p>
        トピック班分けアプリの管理ページです。
      </p>
      <h3>名前リスト</h3>
      <form method="post" class="name-input-wrapper">
        <input type="text" id="name" name="name" required maxlength="16" size="16">
        <button type="submit">名前を追加</button>
      </form>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>名前</th>
            <th>削除</th>
          </tr>
        </thead>
        <tbody>
  EOF;
  echo do_shortcode('[NAMES-SHOW]');
  echo <<<EOF
        </tbody>
      </table>
      <h3>NG組み合わせリスト</h3>
      <form method="post" class="ngcombos-input-wrapper">
        <select type="text" id="ng-name1" name="ng-name1" required>
  EOF;
  echo do_shortcode('[NAMES-OPTION-SHOW]');
  echo <<<EOF
        </select>
        <select type="text" id="ng-name2" name="ng-name2" required>
  EOF;
  echo do_shortcode('[NAMES-OPTION-SHOW]');
  echo <<<EOF
        </select>
        <button type="submit">組み合わせを追加</button>
      </form>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>名前1</th>
            <th>名前2</th>
            <th>削除</th>
          </tr>
        </thead>
        <tbody>
  EOF;
  echo do_shortcode('[NGCOMBOS-SHOW]');
  echo <<<EOF
    </div>
  EOF;
}

if (!defined('ABSPATH')) exit;
