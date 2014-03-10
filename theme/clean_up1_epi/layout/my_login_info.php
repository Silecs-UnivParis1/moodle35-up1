<?php

    function my_login_info($OUTPUT) {
      $loggedinas = $OUTPUT->login_info();
      if ($loggedinas === '') return '';

      global $my_login_info_counter;
	$my_login_info_counter++;


      if (isloggedin()) {
	$prev = $loggedinas;
	$loggedinas = preg_replace('!<a (href="[^"]*/login/logout.php)!', "<a class='logoutButton' $1", $loggedinas);
	if ($loggedinas === $prev) {
	  $loggedinas = preg_replace('!<a (href="[^"]*/course/view.php[^"]*switchrole=0)!', "<a class='logoutButton' $1", $loggedinas);
	} else if ($my_login_info_counter === 1) {
	  $loggedinas .= <<<EOF
<style>
 .navbar-fixed-top .logininfo { display: none; }
</style>
EOF;
	}

	if (!isguestuser() && $my_login_info_counter === 1) {
	  $loggedinas .= <<<EOF
<script> window.bandeau_ENT = { current: 'moodle-epi', hide_menu: true, no_titlebar: true, url: '/bandeau-ENT', logout: '.logoutButton' } </script>
<script src="https://front-test.univ-paris1.fr/bandeau-ENT/bandeau-ENT-loader.js"></script>
EOF;
	}
      }
      return $loggedinas;
    }

?>
