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

/* ================================ *
  WP REST APIのオリジナルエンドポイント追加
 * ================================ */
function add_rest_original_endpoint()
{

  //エンドポイントを登録
  register_rest_route('api', '/topic_shuffle', array(
    'methods' => 'GET',
    //エンドポイントにアクセスした際に実行される関数
    'callback' => 'shuffle_api',
  ));
}

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

    add_action('rest_api_init', 'add_rest_original_endpoint');
    // add_action('plugins_loaded', array($this, 'myplugin_update_db_check')); //プラグイン更新時のDB更新チェック
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
                <button type='submit' name='delete'>×</button>
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
                <button type='submit' name='ng-delete'>×</button>
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
                FOREIGN KEY (name1) REFERENCES {$table_name}(name),
                FOREIGN KEY (name2) REFERENCES {$table_name}(name)
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
  // function myplugin_update_db_check()
  // {
  //   if (get_site_option('topic_shuffle_version') != $topic_shuffle_version) {
  //     $this->topic_shuffle_install();
  //   }
  // }


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
  $names = array();
  foreach ($results as $row) {
    array_push($names, $row->name);
  }
  return $names;
}
function get_ngcombos_array()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'topic_ngcombos';

  $sql = "SELECT name1,name2 FROM {$table_name};";
  $results = $wpdb->get_results($sql);
  $ngcombos = array();
  foreach ($results as $row) {
    $ngcombo = array();
    array_push($ngcombo, $row->name1);
    array_push($ngcombo, $row->name2);
    asort($ngcombo);
    array_push($ngcombos, $ngcombo);
  }
  return $ngcombos;
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
function shuffle_api()
{
  // 連想配列用意
  $names = get_names_array();
  shuffle($names);
  $divided = array_divide($names, 3);
  $ngcombos = get_ngcombos_array();
  $stopperCount = 0;
  while (check_ng($divided, $ngcombos)) {
    if ($stopperCount > 5000) {
      return;
    }
    $names = get_names_array();
    shuffle($names);
    $divided = array_divide($names, 3);
    $stopperCount++;
  }

  echo json_encode($divided);
}
function check_ng($divided, $ngcombos)
{
  foreach ($divided as $n) {
    foreach ($ngcombos as $ngcombo) {
      $count = 0;
      foreach ($ngcombo as $ng) {
        if (in_array($ng, $n)) {
          $count++;
          if ($count >= 2) {
            return true;
          }
        }
      }
    }
  }
  return false;
}
function shuffle_api_test_echo()
{
  // 連想配列用意
  $names = get_names_array();
  shuffle($names);
  $divided = array_divide($names, 3);
  $ngcombos = get_ngcombos_array();
  $stopperCount = 0;
  while (check_ng($divided, $ngcombos)) {
    if ($stopperCount > 5000) {
      return false;
    }
    $names = get_names_array();
    shuffle($names);
    $divided = array_divide($names, 3);
    $stopperCount++;
  }

  foreach ($divided as $a) {
    foreach ($a as $n) {
      echo $n;
    }
    echo '<br>';
  }
}
function shuffle_api_test_before_add($new_ngcombo)
{
  // 連想配列用意
  $names = get_names_array();
  shuffle($names);
  $divided = array_divide($names, 3);
  $ngcombos = get_ngcombos_array();
  array_push($ngcombos, $new_ngcombo);
  $stopperCount = 0;
  while (check_ng($divided, $ngcombos)) {
    if ($stopperCount > 5000) {
      return false;
    }
    $names = get_names_array();
    shuffle($names);
    $divided = array_divide($names, 3);
    $stopperCount++;
  }
  return true;
}
function array_divide($array, $division)
{
  $base_count = floor(count($array) / $division); // 部分配列1個あたりの要素数
  $remainder  = count($array) % $division;        // 余りになる要素数

  $ret = array();
  $offset = 0;
  for ($i = 0; $i < $division; $i++) {
    /*
       * 余りの要素がある場合は、
       * 先頭の部分配列に1個ずつまぶしていく
       */
    if (empty($remainder)) {
      $length = $base_count;
    } else {
      $length = $base_count + 1;
      $remainder--;
    }
    $ret[] = array_slice($array, $offset, $length);

    $offset += $length;
  }

  return $ret;
}
function topic_shuffle_init_data()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'topic_names';
  $table_name2 = $wpdb->prefix . 'topic_ngcombos';

  delete_option('topic_shuffle_version');
  $sql = "DROP TABLE {$table_name};";
  $sql2 = "DROP TABLE {$table_name2};";
  $wpdb->query($sql2);
  $wpdb->query($sql);

  $charset_collate = $wpdb->get_charset_collate();
  $sql3 = "CREATE TABLE $table_name (
              id mediumint(9) NOT NULL AUTO_INCREMENT,
              name varchar(16) NOT NULL,
              UNIQUE KEY id (id),
              PRIMARY KEY (name)
              )
              {$charset_collate}, ENGINE=InnoDB;";

  $sql4 = "CREATE TABLE $table_name2 (
              id mediumint(9) NOT NULL AUTO_INCREMENT,
              name1 varchar(16) NOT NULL,
              name2 varchar(16) NOT NULL,
              UNIQUE KEY id (id),
              FOREIGN KEY (name1) REFERENCES {$table_name}(name),
              FOREIGN KEY (name2) REFERENCES {$table_name}(name)
              )
              {$charset_collate}, ENGINE=InnoDB;";

  $wpdb->query($sql3);
  $wpdb->query($sql4);
}

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
    } else if (isset($_POST['delete_db'])) {
      topic_shuffle_init_data();
    } else if (isset($_POST['delete'])) {
      delete_name($_POST['id']);
    } else if (isset($_POST['ng-delete'])) {
      delete_ngcombos($_POST['ng-id']);
    } else if (!empty($_POST['ng-name1']) && !empty($_POST['ng-name2'])) {
      $ngcombo_new = array($_POST['ng-name1'], $_POST['ng-name2']);
      asort($ngcombo_new);
      if (shuffle_api_test_before_add($ngcombo_new)) {
        if (in_array($ngcombo_new, $ngcombos)) {
          echo '<p style="color:red;">※同じ組み合わせは追加できません。</p>';
        } else if ($_POST['ng-name1'] == $_POST['ng-name2']) {
          echo '<p style="color:red;">※同じ名前を組み合わせることはできません。</p>';
        } else {
          add_ngcombos($_POST['ng-name1'], $_POST['ng-name2']);
        }
      } else {
        echo '<p style="color:red;">※この組み合わせを追加すると班分けできなくなります。</p>';
      }
    } else {
      header("Location: " . nowUrl());
    }
  }
?>
  <?php
  //---------------------------------
  // HTML表示
  //---------------------------------
  // shuffle_api_test_echo();
  ?>
  <div class="wrap">
    <style>
      button {
        border: none;
        cursor: pointer;
        outline: none;
        appearance: none;
      }

      .btn-blue {
        color: white;
        background-color: #05A4DE;
        padding: 6px 10px;
        border-radius: 6px;
        transition: opacity .3s;
      }

      .btn-red {
        color: white;
        background-color: #f04949;
        padding: 6px 10px;
        border-radius: 6px;
        transition: opacity .3s;
      }

      .btn-blue:hover {
        opacity: 0.7;
      }

      .btn-red:hover {
        opacity: 0.7;
      }

      table {
        margin-top: 10px;
        border-spacing: 0px 0px;
      }

      td,
      th {
        background-color: white;
        padding: 5px 20px;
        color: #343434;
      }

      tr td:last-child {
        text-align: center;
        width: 50px;
      }

      tr td:last-child button {
        background-color: #f05959;
        border-radius: 9999px;
        color: white;
        padding: 2px 6px 3px;
        transition: opacity .3s;
      }

      tr td:last-child button:hover {
        opacity: 0.7;
      }

      table,
      td,
      th {
        border: 1px #969696 solid;
      }
    </style>
    <h1 style="font-weight: bold;">TopicShuffle</h1>
    <h2><a href="<?php echo esc_url(home_url('/shuffle')); ?>" target="_blank" rel="noopener">トピック班分けページへ</a></h2>
    <p>
      トピック班分けアプリの管理ページです。
    </p>
    <h3>名前リスト</h3>
    <form method="post" class="name-input-wrapper">
      <input type="text" id="name" name="name" required maxlength="16" size="16">
      <button type="submit" class="btn-blue">＋名前を追加</button>
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
        <?php echo do_shortcode('[NAMES-SHOW]'); ?>
      </tbody>
    </table>
    <h3>NG組み合わせリスト</h3>
    <form method="post" class="ngcombos-input-wrapper">
      <select type="text" id="ng-name1" name="ng-name1" required>
        <option value="" selected>選択してください</option>
        <?php echo do_shortcode('[NAMES-OPTION-SHOW]'); ?>
      </select>
      <select type="text" id="ng-name2" name="ng-name2" required>
        <option value="" selected>選択してください</option>
        <?php echo do_shortcode('[NAMES-OPTION-SHOW]'); ?>
      </select>
      <button type="submit" class="btn-blue">＋組み合わせを追加</button>
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
        <?php echo do_shortcode('[NGCOMBOS-SHOW]'); ?>
      </tbody>
    </table>
    <p style="margin-top: 50px;">
    <form method="post">
      <input type="hidden" name="delete_db">
      <button type="submit" class="btn-red">初期化（設定を全て削除）</button>
    </form>
    </p>
  </div>
<?php
}

if (!defined('ABSPATH')) exit;
