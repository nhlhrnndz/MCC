<?php
session_start();
echo "<h2>Session Debug Information</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Available Session Keys:</h3>";
echo "<ul>";
foreach ($_SESSION as $key => $value) {
    echo "<li><strong>$key:</strong> " . (is_array($value) ? print_r($value, true) : $value) . "</li>";
}
echo "</ul>";
?>