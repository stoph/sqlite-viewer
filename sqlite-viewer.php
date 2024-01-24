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
  add_menu_page('SQLite Viewer', 'SQLite Viewer', 'manage_options', 'sqlite-viewer', 'SQLiteViewer\sqlite_viewer');
}

function sqlite_viewer() {
  $db = new \PDO('sqlite:' . FQDB);

  $selectedTable = $_POST['table'] ?? null;
  $ignoreTrashed = isset($_POST['ignoreTrashed']) ? 'checked' : 'checked';
  $onlyPosts = isset($_POST['onlyPosts']) ? 'checked' : 'checked';
  $ignoreAutoDrafts = isset($_POST['ignoreAutoDrafts']) ? 'checked' : 'checked';

  $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table';")->fetchAll(\PDO::FETCH_ASSOC);

  echo '<form method="post" id="dbForm">';
  echo '<select name="table" id="tableSelect">';
  foreach ($tables as $table) {
    $selected = $table['name'] === $selectedTable ? ' selected' : '';
    echo '<option' . $selected . '>' . $table['name'] . '</option>';
  }
  echo '</select>';

  echo '<div id="checkboxes" style="display: none">';
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

  if ($selectedTable) {
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
      if ($conditions) {
        $query .= ' WHERE ' . implode(' AND ', $conditions);
      }
    }

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
        echo '<td>' . $cell . '</td>';
      }
      echo '</tr>';
    }
    echo '</table>';
  }
}