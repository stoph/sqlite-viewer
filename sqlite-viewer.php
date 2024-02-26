<?php

/**
 * Plugin Name:       SQLite Viewer
 * Description:       View your SQLite database in the WordPress admin
 * Version:           0.1
 * Author:            Stoph
 * Text Domain:       sqlite-viewer
 */
namespace SQLiteViewer;

defined('ABSPATH') or exit();

add_action('admin_menu', 'SQLiteViewer\sqlite_menu');

function sqlite_menu() {
  add_menu_page('SQLite Viewer', 'SQLite Viewer', 'manage_options', 'sqlite-viewer', 'SQLiteViewer\sqlite_viewer', 'dashicons-database');
}

function sqlite_viewer() {
  $db               = new \PDO('sqlite:' . FQDB);
  $query            = null;
  $sql              = $_POST['sql'] ?? null;
  $sql              = str_replace('\\', '', $sql);
  $selectedTable    = $_POST['table'] ?? null;
  $ignoreTrashed    = isset($_POST['ignoreTrashed']) ? 'checked' : FALSE;
  $onlyPosts        = isset($_POST['onlyPosts']) ? 'checked' : FALSE;
  $ignoreAutoDrafts = isset($_POST['ignoreAutoDrafts']) ? 'checked' : FALSE;
  $postId           = $_POST['post_id'] ?? null;

  $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table';")->fetchAll(\PDO::FETCH_ASSOC);

  echo '<form method="post" id="dbForm">';
  // allow raw sql input
  echo 'SQL: <input type="text" name="sql" id="sql" value="' . @$sql . '">';
  echo ' or Table:<select name="table" id="tableSelect">';
  foreach ($tables as $table) {
    $selected = $table['name'] === $selectedTable ? ' selected' : '';
    echo '<option' . $selected . '>' . $table['name'] . '</option>';
  }
  echo '</select>';

  echo '<div id="checkboxes" style="display: none">';
  echo '<label>Post ID <input type="text" name="post_id" value="' . $postId . '"></label>';
  echo '<label><input type="checkbox" name="ignoreTrashed"' . $ignoreTrashed . '> Ignore Trashed</label>';
  echo '<label><input type="checkbox" name="onlyPosts"' . $onlyPosts . '> Only Posts</label>';
  echo '<label><input type="checkbox" name="ignoreAutoDrafts"' . $ignoreAutoDrafts . '> Ignore Auto Drafts</label>';
  echo '</div>';

  echo '<button type="submit">Submit</button>';
  echo '</form>';

  echo '<script>
  document.getElementById("tableSelect").addEventListener("change", function() {
    var checkboxes = document.getElementById("checkboxes");
    checkboxes.style.display = this.value === "wp_posts" ? "block" : "none";
  });
  </script>';
  if ($selectedTable === 'wp_posts') {
    echo '<script>document.getElementById("checkboxes").style.display = "block";</script>';
  }

  if ($sql) {
    $query = $sql;
    // if string contains wp_posts, add $sql to the query
    if (strpos($sql, 'wp_posts') !== FALSE) {
      // search for and add $string directly after the "where" clause
      $hide = " post_status != 'trash' AND post_status != 'auto-draft' AND ";
      $query = preg_replace('/(where)/i', '$1 ' . $hide, $query);
    }
    // remove \ from the query
    
  } else if ($selectedTable) {
    $query = "SELECT * FROM " . $selectedTable;

    if ($selectedTable === 'wp_posts') {
      $conditions = [];
      if ($ignoreTrashed) {
        $conditions[] = "post_status != 'trash'";
      }
      if ($onlyPosts) {
        $conditions[] = "post_type = 'post'";
      }
      if ($ignoreAutoDrafts) {
        $conditions[] = "post_status != 'auto-draft'";
      }
      if ($postId) {
        $conditions[] = "ID = $postId";
      }
      if ($conditions) {
        $query .= ' WHERE ' . implode(' AND ', $conditions);
      }
    }
  }
  if ($query) {
    echo "$query<hr>";
    $result = $db->query($query);
    $rows = $result->fetchAll(\PDO::FETCH_ASSOC);

    echo '<table>';

    // Display headers
    echo '<tr>';
    for ($i = 0; $i < $result->columnCount(); $i++) {
      $column = $result->getColumnMeta($i);
      echo '<th>' . $column['name'] . '</th>';
    }
    echo '</tr>';

    // Display rows
    foreach ($rows as $row) {
      echo '<tr>';
      foreach ($row as $cell) {
        echo '<td><xmp>' . $cell . '</xmp></td>';
      }
      echo '</tr>';
    }
    echo '</table>';
  }
}