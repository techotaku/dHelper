<?php
  $user = $argv[1];
  $sock = $argv[2];
?>
[global]

[travis]
user = <?php echo $user ?> 
group = <?php echo $user ?> 
listen = <?php echo $sock ?> 
pm = static
pm.max_children = 2

php_admin_value[memory_limit] = 128M