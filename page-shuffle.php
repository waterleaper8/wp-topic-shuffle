<?php

/**
 * Template Name: トピック班分け
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages and that
 * other "pages" on your WordPress site will use a different template.
 *
 * @package WordPress
 */

?>

<head>
  <meta charset="utf-8" />
  <title>トピック班分け</title>
  <style>
    /*** The new CSS Reset - version 1.2.0 (last updated 23.7.2021) ***/

    /* Remove all the styles of the "User-Agent-Stylesheet", except for the 'display' property */
    *:where(:not(textarea, iframe, canvas, img, svg, video):not(svg *)) {
      all: unset;
      display: revert;
    }

    /* Preferred box-sizing value */
    *,
    *::before,
    *::after {
      box-sizing: border-box;
    }

    /* Remove list styles (bullets/numbers) */
    ol,
    ul {
      list-style: none;
    }

    /* For images to not be able to exceed their container */
    img {
      max-width: 100%;
    }

    /* removes spacing between cells in tables */
    table {
      border-collapse: collapse;
    }

    /* revert the 'white-space' property for textarea elements on Safari */
    textarea {
      white-space: revert;
    }

    body {
      width: 1200px;
      margin: auto;
    }

    #overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.6);
      z-index: 1;
      display: none;
      justify-content: center;
      align-items: center;
    }

    .fadeout {
      animation: fadeOut 1s;
      animation-fill-mode: both;
    }

    .spacer {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 24px 0;
      width: 100%;
    }

    @keyframes fadeOut {
      0% {
        opacity: 1;
      }

      100% {
        opacity: 0;
      }
    }

    p#alert {
      color: red;
      display: none;
    }

    p#alert.show {
      display: block;
    }

    .kuji-box {
      width: 200px;
      height: 200px;
      background-image: url(<?php echo get_template_directory_uri() . '/assets/kujibiki_box.png'; ?>);
      background-size: cover;
      animation-name: rotateBox;
      animation-duration: 1.5s;
      animation-iteration-count: infinite;
      animation-timing-function: step-start;
    }

    @keyframes rotateBox {
      0% {
        transform: rotate(-45deg);
      }

      50% {
        transform: rotate(45deg);
      }

      100% {
        transform: rotate(-45deg);
      }
    }

    .kuji-man {
      background-image: url(<?php echo get_template_directory_uri() . '/assets/game_kuji_man.png'; ?>);
      background-size: cover;
      width: 200px;
      height: 200px;
      position: absolute;
      right: 0px;
      top: 0;
    }

    h1 {
      font-size: 2.4rem;
      font-weight: bold;
      position: relative;
      color: #555;
      margin-top: 48px;
      text-align: center;
    }

    h2 {
      font-weight: bold;
      text-align: center;
      padding-bottom: 6px;
      font-size: 1.8rem;
      color: #555;
    }

    small {
      font-size: 0.8rem;
    }

    textarea {
      font-size: 1.6rem;
      border: none;
      border: 1px solid #ccc;
      border-radius: 6px;
      color: #555;
      resize: vertical;
    }

    .desc {
      margin: 36px 0 24px;
      text-align: center;
    }

    form {
      margin-bottom: 18px;
    }

    button {
      height: 48px;
      padding: 14px 40px;
      border-radius: 6px;
      color: #ffffff;
      background-color: #1a8cb3;
      cursor: pointer;
      transition: all 0.3s;
      margin: auto;
    }

    button:hover {
      background-color: white;
      color: #000;
      border: 1px solid #000;
    }

    input[type="file"] {
      display: none;
    }

    input:hover {
      opacity: 0.7;
    }

    .areas {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
    }

    .area {
      text-align: center;
      margin-right: 24px;
      margin-bottom: 12px;
      padding: 12px 12px 24px;
      border-radius: 12px;
    }

    .area:last-child {
      margin-right: 0;
    }

    .area_a {
      background-color: #f9d6d2;
    }

    .area_b {
      background-color: #d2f2f9;
    }

    .area_c {
      background-color: #f2eca6;
    }

    @media screen and (max-width: 1200px) {
      body {
        width: 100%;
      }
    }
  </style>
</head>

<?php
$user = wp_get_current_user();
$current_role = $user->roles[0];
if ($current_role) :
?>

  <body>
    <div id="overlay">
      <div class="kuji-box"></div>
    </div>
    <h1>
      トピック チーム分け
      <div class="kuji-man"></div>
    </h1>

    <div class="spacer">
      <button>シャッフル</button>
      <p id="alert">シャッフルに失敗しました</p>
    </div>

    <div class="areas">
      <div class="area area_a">
        <h2>A席</h2>
        <textarea name="list_a" rows="12" cols="18" readonly></textarea><br />
      </div>
      <div class="area area_b">
        <h2>B席</h2>
        <textarea name="list_b" rows="12" cols="18" readonly></textarea><br />
      </div>
      <div class="area area_c">
        <h2>C席</h2>
        <textarea name="list_c" rows="12" cols="18" readonly></textarea>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
    <script>
      let namelistSplited
      $('button').click(function() {
        reveal_namelist()
      });

      function reveal_namelist() {
        const endpoint = "<?php echo esc_url(home_url()) . '/wp-json/api/topic_shuffle' ?>";
        let namelistSplited;
        $.ajax({
            type: "GET",
            url: endpoint,
            dataType: "json",
            timeout: 5000,
            error: function(xhr, textStatus, errorThrown) { // エラーと判定された場合
              if (textStatus == "timeout") {
                // リクエストタイムアウトの場合の処理
                alert.classList.add("show");
                namelistSplited = "error";
              } else {
                // その他のエラーの処理
                alert.classList.add("show");
                namelistSplited = "error";
              }
            },
          })
          .done((out) => {
            if (alert.classList.contains("show")) {
              alert.classList.remove("show");
            }
            namelistSplited = out;

            overlay.style.display = "flex";
            document.body.style.overflow = "visible";
            overlay.classList.remove("fadeout");

            setTimeout(() => {
              document.body.style.overflow = "hidden";
              overlay.style.display = "none";
              overlay.classList.add("fadeout");

              const areas = document.querySelectorAll(".area");
              areas[0].querySelector("textarea").value = namelistSplited[0].join('\n');
              areas[1].querySelector("textarea").value = namelistSplited[1].join('\n');
              areas[2].querySelector("textarea").value = namelistSplited[2].join('\n');
            }, 3000);
          })
          .fail(() => {
            alert.classList.add("show");
            namelistSplited = "error";
          });
      }

      let alert = document.getElementById("alert");
    </script>
  </body>
  <?php wp_footer(); ?>
<?php else : ?>
  <p>
    ページを表示するには
    <a style="
        cursor: pointer;
        color: #88e;
        text-decoration: underline;
        font-weight: bold;" href="<?php echo wp_login_url(); ?>">ログイン</a>
    してください
  </p>
<?php endif; ?>

<?php if (!defined('ABSPATH')) exit; ?>
